<?php
// Controlador RESTful para registrar nuevos administradores con hash BCRYPT
require __DIR__ . '/../config/http.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../models/UsuarioModel.php';

manejar(function () {
    requerirSesion(); // Solo un administrador autenticado puede crear otro usuario (OWASP A01)

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(405, ['error' => 'Método no permitido.']);
    }

    $d = entrada();
    $usuario = texto($d, 'usuario', 50);
    $clave = (string)($d['password'] ?? '');

    if (mb_strlen($clave) < 8) {
        throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
    }

    $modelo = new UsuarioModel(Conexion::get());
    if ($modelo->buscarPorUsuario($usuario)) {
        responder(400, ['error' => 'Ese nombre de usuario ya existe.']);
    }

    $id = $modelo->crear($usuario, $clave);
    responder(201, ['id' => $id, 'mensaje' => 'Administrador registrado con BCRYPT.']);
});