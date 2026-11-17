<?php
// Utilidades comunes de la API: cabecera JSON, sesión, respuestas y manejo de errores.
header('Content-Type: application/json; charset=utf-8');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']); //'httponly' impide que javascript acceda a la cookie
session_start();

// Escapa recursivamente todos los strings de la salida (defensa en profundidad contra XSS, OWASP A03)
function escapar($valor) {
    if (is_array($valor)) {
        return array_map('escapar', $valor);
    }
    return is_string($valor) ? htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $valor;
}

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode(escapar($datos), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

function entrada(): array {
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

// Control de acceso (OWASP A01): corta con 401 si no hay sesión activa
function requerirSesion(): void {
    if (empty($_SESSION['usuario_id'])) {
        responder(401, ['error' => 'Tenés que iniciar sesión para hacer esto.']);
    }
}

// Ejecuta un endpoint capturando excepciones sin filtrar datos sensibles al cliente
function manejar(callable $endpoint): void {
    try {
        $endpoint();
    } catch (InvalidArgumentException $e) {
        responder(400, ['error' => $e->getMessage()]);
    } catch (PDOException $e) {
        error_log('PDO: ' . $e->getMessage());
        responder(500, ['error' => 'Error interno del servidor.']);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        responder(500, ['error' => 'Error interno del servidor.']);
    }
}

function texto(array $d, string $campo, int $max, bool $obligatorio = true): string {
    $v = trim((string)($d[$campo] ?? ''));
    if (($obligatorio && $v === '') || mb_strlen($v) > $max) {
        throw new InvalidArgumentException("Campo inválido: $campo");
    }
    return $v;
}

function emailValido(array $d): string {
    $e = texto($d, 'email', 100);
    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Campo inválido: email');
    }
    return $e;
}
