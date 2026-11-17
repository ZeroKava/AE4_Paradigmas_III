<?php
//controlador RESTful para validar y registrar respuestas de satisfacción
require __DIR__ . '/../config/http.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../models/MensajeModel.php';

manejar(function () {
    //solo se permite creación mediante POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['error' => 'Método no permitido.']);
    //lee y decodifica el cuerpo JSON enviado por fetch() desde el formulario
    $d = entrada();
    //se valida de que la satisfacción sea un entero estricto entre 1 y 5
    $sat = filter_var($d['satisfaccion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    if ($sat === false) throw new InvalidArgumentException('Campo inválido: satisfaccion');
    $rec = $d['recomienda'] ?? '';
    if (!in_array($rec, ['Sí', 'Tal vez', 'No'], true)) throw new InvalidArgumentException('Campo inválido: recomienda');

    (new MensajeModel(Conexion::get()))->guardarEncuesta($sat, $rec, texto($d, 'comentario', 200, false));
    responder(201, ['mensaje' => '¡Gracias por tu opinión!']);
});
