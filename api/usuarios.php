<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/logica/usuarios_logic.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$entrada = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($entrada['accion']) ? $entrada['accion'] : '');

if (!verificarAdmin()) {
    respuestaError('No tienes permisos para realizar esta acción', 403);
    exit;
}

switch ($accion) {
    case 'listar':
        listarUsuarios();
        break;

    case 'obtener':
        $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
        obtenerUsuario($id);
        break;

    case 'crear':
        crearUsuario($entrada);
        break;

    case 'actualizar':
        actualizarUsuario($entrada);
        break;

    case 'eliminar':
        $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
        eliminarUsuario($id);
        break;

    case 'cambiar_estado':
        cambiarEstadoUsuario($entrada);
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}
?>
