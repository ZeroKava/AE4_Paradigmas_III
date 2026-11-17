<?php
require __DIR__ . '/../config/http.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../models/CompraModel.php';

manejar(function () {
    $modelo = new CompraModel(Conexion::get());

    if ($_SERVER['REQUEST_METHOD'] === 'GET') { // validar cupón: GET ?cupon=UCP10
        $desc = $modelo->descuento(trim((string)($_GET['cupon'] ?? '')));
        $desc === null ? responder(404, ['error' => 'Código inválido o vencido.'])
                       : responder(200, ['descuento' => $desc]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') //si no es get ni post se rechaza con 405 method not allowed
        responder(405, ['error' => 'Método no permitido.']);
    //lectura y validación de datos para registrar una nueva compra
    $d = entrada();
    $pago = $d['pago'] ?? '';
    if (!in_array($pago, ['efectivo', 'transferencia', 'tarjeta'], true)) throw new InvalidArgumentException('Campo inválido: pago');
    //valida cada item del carrito en el servidor
    $items = [];
    foreach (($d['items'] ?? []) as $it) {
        $pid = filter_var($it['id'] ?? null, FILTER_VALIDATE_INT);
        $q = filter_var($it['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if ($pid === false || $q === false) throw new InvalidArgumentException('Ítem inválido.');
        $items[] = ['id' => $pid, 'cantidad' => $q];
    }
    //impide registrar compras vacías si el arreglo de ítems llega sin productos
    if (!$items) 
        throw new InvalidArgumentException('Elegí al menos un producto o servicio.');
    //valida longitud y formato de todos los datos personales del comprador antes de pasarlos al modelo
    $cliente = [
        ':nombre' => texto($d, 'nombre', 100), ':direccion' => texto($d, 'direccion', 150),
        ':telefono' => texto($d, 'telefono', 30), ':email' => emailValido($d), ':pago' => $pago,
    ];
    //ejecuta la transacción atómica en CompraModel
    $r = $modelo->crear($cliente, $items, texto($d, 'cupon', 30, false));
    //Responde con código semántico 201 Created junto al ID de orden generado y el total real calculado en servidor
    responder(201, ['id' => $r['id'], 'total' => $r['total'], 'mensaje' => 'Compra registrada.']);
});
