<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/logica/auth_logic.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$entrada = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($entrada['accion']) ? $entrada['accion'] : '');

switch ($accion) {
    case 'login':
        if ($metodo === 'POST') {
            login($entrada);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    case 'logout':
        logout();
        break;

    case 'verificar':
        verificarSesion();
        break;

    case 'registro':
        if ($metodo === 'POST') {
            registrarUsuario($entrada);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}
?>
