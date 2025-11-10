<?php
/**
 * API de Habitaciones
 * Maneja CRUD de habitaciones y sus imágenes
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/db.php';

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

/**
 * Verifica si el usuario es administrador
 */
function verificarAdmin() {
    if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
        respuestaError('No hay sesión activa', 401);
        return false;
    }

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        respuestaError('No tienes permisos para realizar esta acción', 403);
        return false;
    }

    return true;
}

/**
 * Lista todas las habitaciones activas (o todas si es admin)
 */
function listarHabitaciones() {
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Si el usuario es admin, mostrar todas las habitaciones (activas e inactivas)
    // Si no es admin o no hay sesión, mostrar solo las activas
    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa ORDER BY activo DESC, nombre_categoria, precio_noche";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE activo = 1 ORDER BY nombre_categoria, precio_noche";
    }

    $habitaciones = ejecutarConsulta($conn, $sql);

    // Obtener imágenes para cada habitación
    foreach ($habitaciones as &$habitacion) {
        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal, orden_visualizacion
                        FROM imagenes_habitacion
                        WHERE id_habitacion = ?
                        ORDER BY es_principal DESC, orden_visualizacion";
        $habitacion['imagenes'] = ejecutarConsulta($conn, $sqlImagenes, "i", [$habitacion['id_habitacion']]);
    }

    cerrarConexion($conn);

    respuestaExito(['habitaciones' => $habitaciones]);
}

/**
 * Obtiene una habitación específica
 */
function obtenerHabitacion($id) {
    if ($id <= 0) {
        respuestaError('ID de habitación no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT * FROM vista_habitaciones_completa WHERE id_habitacion = ?";
    $resultado = ejecutarConsulta($conn, $sql, "i", [$id]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Habitación no encontrada', 404);
        return;
    }

    $habitacion = $resultado[0];

    // Obtener imágenes
    $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal, orden_visualizacion
                    FROM imagenes_habitacion
                    WHERE id_habitacion = ?
                    ORDER BY es_principal DESC, orden_visualizacion";
    $habitacion['imagenes'] = ejecutarConsulta($conn, $sqlImagenes, "i", [$id]);

    cerrarConexion($conn);

    respuestaExito(['habitacion' => $habitacion]);
}

/**
 * Crea una nueva habitación
 */
function crearHabitacion($datos) {
    // Validar datos requeridos
    if (empty($datos['numero_habitacion']) || empty($datos['id_categoria']) ||
        empty($datos['nombre']) || empty($datos['precio_noche']) ||
        empty($datos['capacidad_personas']) || empty($datos['cantidad_disponible'])) {
        respuestaError('Todos los campos obligatorios deben ser completados');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Verificar si el número de habitación ya existe
    $sqlVerificar = "SELECT id_habitacion FROM habitaciones WHERE numero_habitacion = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "s", [$datos['numero_habitacion']]);

    if (!empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('El número de habitación ya existe');
        return;
    }

    $sqlInsertar = "INSERT INTO habitaciones (numero_habitacion, id_categoria, nombre, descripcion,
                    precio_noche, capacidad_personas, cantidad_disponible, caracteristicas)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $resultado = ejecutarModificacion($conn, $sqlInsertar, "sissdiis", [
        $datos['numero_habitacion'],
        $datos['id_categoria'],
        $datos['nombre'],
        isset($datos['descripcion']) ? $datos['descripcion'] : '',
        $datos['precio_noche'],
        $datos['capacidad_personas'],
        $datos['cantidad_disponible'],
        isset($datos['caracteristicas']) ? $datos['caracteristicas'] : ''
    ]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito([
            'mensaje' => 'Habitación creada exitosamente',
            'id_habitacion' => $resultado['id']
        ]);
    } else {
        respuestaError('Error al crear habitación: ' . $resultado['error']);
    }
}

/**
 * Actualiza una habitación existente
 */
function actualizarHabitacion($datos) {
    if (empty($datos['id_habitacion'])) {
        respuestaError('ID de habitación es requerido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlActualizar = "UPDATE habitaciones SET
                      id_categoria = ?, nombre = ?, descripcion = ?,
                      precio_noche = ?, capacidad_personas = ?,
                      cantidad_disponible = ?, caracteristicas = ?, activo = ?
                      WHERE id_habitacion = ?";

    $activo = isset($datos['activo']) ? $datos['activo'] : 1;

    $resultado = ejecutarModificacion($conn, $sqlActualizar, "issdiisii", [
        $datos['id_categoria'],
        $datos['nombre'],
        isset($datos['descripcion']) ? $datos['descripcion'] : '',
        $datos['precio_noche'],
        $datos['capacidad_personas'],
        $datos['cantidad_disponible'],
        isset($datos['caracteristicas']) ? $datos['caracteristicas'] : '',
        $activo,
        $datos['id_habitacion']
    ]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Habitación actualizada exitosamente']);
    } else {
        respuestaError('Error al actualizar habitación: ' . $resultado['error']);
    }
}

/**
 * Elimina (desactiva) una habitación
 */
function eliminarHabitacion($id) {
    if ($id <= 0) {
        respuestaError('ID de habitación no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlActualizar = "UPDATE habitaciones SET activo = 0 WHERE id_habitacion = ?";
    $resultado = ejecutarModificacion($conn, $sqlActualizar, "i", [$id]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Habitación eliminada exitosamente']);
    } else {
        respuestaError('Error al eliminar habitación: ' . $resultado['error']);
    }
}

/**
 * Lista habitaciones por categoría
 */
function listarPorCategoria($idCategoria) {
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Si el usuario es admin, mostrar todas las habitaciones
    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE 1=1";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE activo = 1";
    }

    if ($idCategoria > 0) {
        $sql .= " AND id_categoria = ?";
        $habitaciones = ejecutarConsulta($conn, $sql, "i", [$idCategoria]);
    } else {
        $sql .= " ORDER BY nombre_categoria, precio_noche";
        $habitaciones = ejecutarConsulta($conn, $sql);
    }

    // Obtener imágenes para cada habitación
    foreach ($habitaciones as &$habitacion) {
        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal
                        FROM imagenes_habitacion
                        WHERE id_habitacion = ?
                        ORDER BY es_principal DESC, orden_visualizacion";
        $habitacion['imagenes'] = ejecutarConsulta($conn, $sqlImagenes, "i", [$habitacion['id_habitacion']]);
    }

    cerrarConexion($conn);

    respuestaExito(['habitaciones' => $habitaciones]);
}

/**
 * Lista todas las categorías
 */
function listarCategorias() {
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT c.*, COUNT(h.id_habitacion) as total_habitaciones
            FROM categorias_habitacion c
            LEFT JOIN habitaciones h ON c.id_categoria = h.id_categoria AND h.activo = 1
            GROUP BY c.id_categoria
            ORDER BY c.nombre_categoria";

    $categorias = ejecutarConsulta($conn, $sql);

    cerrarConexion($conn);

    respuestaExito(['categorias' => $categorias]);
}

/**
 * Busca habitaciones por término
 */
function buscarHabitaciones($termino) {
    if (empty($termino)) {
        listarHabitaciones();
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $terminoBusqueda = '%' . $termino . '%';

    // Si el usuario es admin, buscar en todas las habitaciones
    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa
                WHERE (
                    nombre LIKE ? OR
                    descripcion LIKE ? OR
                    caracteristicas LIKE ? OR
                    nombre_categoria LIKE ? OR
                    numero_habitacion LIKE ?
                )
                ORDER BY activo DESC, nombre_categoria, precio_noche";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa
                WHERE activo = 1 AND (
                    nombre LIKE ? OR
                    descripcion LIKE ? OR
                    caracteristicas LIKE ? OR
                    nombre_categoria LIKE ? OR
                    numero_habitacion LIKE ?
                )
                ORDER BY nombre_categoria, precio_noche";
    }

    $habitaciones = ejecutarConsulta($conn, $sql, "sssss", [
        $terminoBusqueda, $terminoBusqueda, $terminoBusqueda,
        $terminoBusqueda, $terminoBusqueda
    ]);

    // Obtener imágenes para cada habitación
    foreach ($habitaciones as &$habitacion) {
        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal
                        FROM imagenes_habitacion
                        WHERE id_habitacion = ?
                        ORDER BY es_principal DESC, orden_visualizacion";
        $habitacion['imagenes'] = ejecutarConsulta($conn, $sqlImagenes, "i", [$habitacion['id_habitacion']]);
    }

    cerrarConexion($conn);

    respuestaExito([
        'habitaciones' => $habitaciones,
        'total_resultados' => count($habitaciones),
        'termino_busqueda' => $termino
    ]);
}

function respuestaExito($datos) {
    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function respuestaError($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode([
        'success' => false,
        'error' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
