<?php
//Controlador RESTful para recibir y persistir consultas de contacto
require __DIR__ . '/../config/http.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../models/MensajeModel.php';
// Envuelve el endpoint en manejar() para transformar excepciones de validación en 400 Bad Request y errores internos en 500
manejar(function () {
    //este recurso solo acepta creación mediante POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['error' => 'Método no permitido.']);
    //lee y decodifica el cuerpo json enviado por fetch() desde el formulario
    $d = entrada();
    
    //instancia MensajeModel inyectándole la conexión PDO única y valida/sanea cada campo en el servidor antes de guardar
    (new MensajeModel(Conexion::get()))->guardarConsulta(
        texto($d, 'nombre', 100), emailValido($d), texto($d, 'servicio', 50), texto($d, 'consulta', 2000)
    );
    responder(201, ['mensaje' => '¡Gracias! Recibimos tu consulta y te respondemos pronto.']);
});
