<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/logica/imagenes_logic.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
    respuestaError('No hay sesión activa', 401);
}

if ($_SESSION['usuario_tipo'] !== 'admin') {
    respuestaError('No tienes permisos para realizar esta acción', 403);
}

switch ($accion) {
    case 'subir':
        if ($metodo === 'POST') {
            subirImagenes();
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    case 'eliminar':
        if ($metodo === 'POST' || $metodo === 'DELETE') {
            $entrada = json_decode(file_get_contents('php://input'), true);
            $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
            eliminarImagen($id);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    case 'principal':
        if ($metodo === 'POST') {
            $entrada = json_decode(file_get_contents('php://input'), true);
            establecerImagenPrincipal($entrada);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}
?>
