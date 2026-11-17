<!-- controlador de productos
<?php
require __DIR__ . '/../config/http.php'; //require incluye y ejecuta el contenido de otro php
//__DIR__ devulve la ruta de la carpeta donde se encuentra el archivo actual y ../ sube un nivel en la estructura de carpetas
require __DIR__ . '/../config/conexion.php'; // . concatena
require __DIR__ . '/../models/ProductoModel.php';

function validarProducto(array $d): array {
    $tipo = $d['tipo'] ?? ''; //si es null lo convierte a ''
    if (!in_array($tipo, ['impresora', 'servicio'], true)) throw new InvalidArgumentException('Campo inválido: tipo');//valida el tipo, !inarray, osea que no esta
    $precio = $d['precio'] ?? null; 
    if (!is_numeric($precio) || $precio < 0 || $precio > 99999999) throw new InvalidArgumentException('Campo inválido: precio');//valida el precio
    $imagen = texto($d, 'imagen', 100, false); // 100 es la longitud máxima y false que no es obligatorio
    if ($imagen !== '' && !preg_match('/^[\w.\-]+$/u', $imagen)) throw new InvalidArgumentException('Campo inválido: imagen');//preg macth es para ver si una cadena coincide con una expresion, es para evitar rutas a los archivos
    return [
        ':nombre' => texto($d, 'nombre', 100), ':tipo' => $tipo,
        ':categoria' => texto($d, 'categoria', 50, false),
        ':descripcion' => texto($d, 'descripcion', 500, false),
        ':precio' => $precio, ':imagen' => $imagen,
    ];
}

manejar(function () {
    $modelo = new ProductoModel(Conexion::get()); //llama al metodo get de la clase conexion
    $metodo = $_SERVER['REQUEST_METHOD']; //contiene el metodo HTTP utilizado por el cliente
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);// obtiene y validad el parametro id enviado mediante url

    if ($metodo === 'GET') { // público
        if ($id) {//si tiene un valor verdadero
            $p = $modelo->obtener($id); //buscamos producto por su id
            $p ? responder(200, $p) : responder(404, ['error' => 'Producto no encontrado.']); //http responses
        }
        $tipo = $_GET['tipo'] ?? null;
        if ($tipo !== null && !in_array($tipo, ['impresora', 'servicio'], true)) {
            throw new InvalidArgumentException('Campo inválido: tipo');
        }
        responder(200, $modelo->listar($tipo));
    }

    requerirSesion(); // POST, PUT y DELETE son solo para el administrador

    if ($metodo === 'POST') {
        responder(201, ['id' => $modelo->crear(validarProducto(entrada()))]);
    }
    if ($metodo === 'PUT' || $metodo === 'DELETE') {
        if (!$id) throw new InvalidArgumentException('Falta el parámetro id.');
        if ($metodo === 'PUT') {
            $modelo->actualizar($id, validarProducto(entrada()))
                ? responder(200, ['id' => $id]) : responder(404, ['error' => 'Producto no encontrado.']);
        }
        try {
            $modelo->eliminar($id) ? responder(200, ['id' => $id]) : responder(404, ['error' => 'Producto no encontrado.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') responder(400, ['error' => 'No se puede borrar: el producto tiene compras asociadas.']);
            throw $e;
        }
    }
    responder(405, ['error' => 'Método no permitido.']);
});
