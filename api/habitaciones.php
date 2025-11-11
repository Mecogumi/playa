<?php
/**
 * API de Habitaciones
 * Maneja el enrutamiento de las peticiones de habitaciones
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Importar la lógica de negocio
require_once __DIR__ . '/logica/habitaciones_logic.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$entrada = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($entrada['accion']) ? $entrada['accion'] : '');

// Enrutador de acciones
switch ($accion) {
    case 'listar':
        listarHabitaciones();
        break;

    case 'obtener':
        $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
        obtenerHabitacion($id);
        break;

    case 'crear':
        if (verificarAdmin()) {
            crearHabitacion($entrada);
        }
        break;

    case 'actualizar':
        if (verificarAdmin()) {
            actualizarHabitacion($entrada);
        }
        break;

    case 'eliminar':
        if (verificarAdmin()) {
            $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($entrada['id']) ? intval($entrada['id']) : 0);
            eliminarHabitacion($id);
        }
        break;

    case 'por_categoria':
        $idCategoria = isset($_GET['id_categoria']) ? intval($_GET['id_categoria']) : 0;
        listarPorCategoria($idCategoria);
        break;

    case 'categorias':
        listarCategorias();
        break;

    case 'buscar':
        $termino = isset($_GET['termino']) ? $_GET['termino'] : (isset($entrada['termino']) ? $entrada['termino'] : '');
        buscarHabitaciones($termino);
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}
?>
