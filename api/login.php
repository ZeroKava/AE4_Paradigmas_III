<?php
require __DIR__ . '/../config/http.php';
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../models/UsuarioModel.php';

manejar(function () {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET': // ¿hay sesión activa?
            responder(200, ['autenticado' => !empty($_SESSION['usuario_id']),
                            'usuario' => $_SESSION['usuario'] ?? null]);

        case 'POST': // iniciar sesión
            $d = entrada();
            $usuario = texto($d, 'usuario', 50);
            $clave = (string)($d['password'] ?? '');
            if ($clave === '') throw new InvalidArgumentException('Campo inválido: password');

            $u = (new UsuarioModel(Conexion::get()))->buscarPorUsuario($usuario);
            if (!$u || !password_verify($clave, $u['password'])) {
                responder(401, ['error' => 'Usuario o contraseña incorrectos.']);
            }
            session_regenerate_id(true); // previene fijación de sesión (OWASP A07)
            $_SESSION['usuario_id'] = $u['id'];
            $_SESSION['usuario'] = $u['usuario'];
            responder(200, ['autenticado' => true, 'usuario' => $u['usuario']]);

        case 'DELETE': // cerrar sesión
            $_SESSION = [];
            session_destroy();
            responder(200, ['autenticado' => false]);

        default:
            responder(405, ['error' => 'Método no permitido.']);
    }
});
