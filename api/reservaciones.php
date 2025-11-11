<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/logica/reservaciones_logic.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$entrada = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($entrada['accion']) ? $entrada['accion'] : '');

switch ($accion) {
    case 'crear':
        if (verificarUsuario()) {
            crearReservacion($entrada);
        }
        break;

    case 'listar':
        if (verificarUsuario()) {
            listarReservaciones();
        }
        break;

    case 'mis_reservaciones':
        if (verificarUsuario()) {
            listarMisReservaciones();
        }
        break;

    case 'obtener':
        if (verificarUsuario()) {
            $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
            obtenerReservacion($id);
        }
        break;

    case 'cancelar':
        if (verificarUsuario()) {
            $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
            cancelarReservacion($id);
        }
        break;

    case 'verificar_disponibilidad':
        verificarDisponibilidad($entrada);
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}
?>
