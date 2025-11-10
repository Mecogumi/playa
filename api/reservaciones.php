<?php
/**
 * API de Reservaciones
 * Maneja el proceso de reservación de habitaciones
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

/**
 * Verifica si hay un usuario autenticado
 */
function verificarUsuario() {
    if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
        respuestaError('Debes iniciar sesión para realizar reservaciones', 401);
        return false;
    }

    if ($_SESSION['usuario_tipo'] === 'no_registrado') {
        respuestaError('Los usuarios no registrados no pueden realizar reservaciones', 403);
        return false;
    }

    return true;
}

/**
 * Crea una nueva reservación
 */
function crearReservacion($datos) {
    // Validar datos requeridos
    if (empty($datos['fecha_entrada']) || empty($datos['fecha_salida']) || empty($datos['habitaciones'])) {
        respuestaError('Faltan datos requeridos para la reservación');
        return;
    }

    // Validar que hay habitaciones en el carrito
    if (!is_array($datos['habitaciones']) || count($datos['habitaciones']) === 0) {
        respuestaError('No hay habitaciones seleccionadas');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        $idUsuario = $_SESSION['usuario_id'];
        $fechaEntrada = $datos['fecha_entrada'];
        $fechaSalida = $datos['fecha_salida'];

        // Calcular número de noches
        $entrada = new DateTime($fechaEntrada);
        $salida = new DateTime($fechaSalida);
        $numeroNoches = $entrada->diff($salida)->days;

        if ($numeroNoches <= 0) {
            throw new Exception('La fecha de salida debe ser posterior a la fecha de entrada');
        }

        $subtotal = 0;
        $detalles = [];

        // Procesar cada habitación del carrito
        foreach ($datos['habitaciones'] as $habitacion) {
            $idHabitacion = $habitacion['id_habitacion'];
            $cantidad = $habitacion['cantidad'];

            // Verificar disponibilidad
            $sqlVerificar = "SELECT id_habitacion, cantidad_disponible, precio_noche, nombre
                            FROM habitaciones
                            WHERE id_habitacion = ? AND activo = 1";
            $resultado = ejecutarConsulta($conn, $sqlVerificar, "i", [$idHabitacion]);

            if (empty($resultado)) {
                throw new Exception("La habitación con ID $idHabitacion no existe o no está disponible");
            }

            $hab = $resultado[0];

            if ($hab['cantidad_disponible'] < $cantidad) {
                throw new Exception("No hay suficientes habitaciones disponibles de tipo '{$hab['nombre']}'. Disponibles: {$hab['cantidad_disponible']}, Solicitadas: $cantidad");
            }

            // Calcular subtotal de este tipo de habitación
            $precioUnitario = $hab['precio_noche'] * $numeroNoches;
            $subtotalHabitacion = $precioUnitario * $cantidad;
            $subtotal += $subtotalHabitacion;

            // Guardar detalles para insertar después
            $detalles[] = [
                'id_habitacion' => $idHabitacion,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalHabitacion
            ];

            // Descontar habitaciones disponibles
            $sqlDescontar = "UPDATE habitaciones
                            SET cantidad_disponible = cantidad_disponible - ?
                            WHERE id_habitacion = ?";
            $resultadoDescontar = ejecutarModificacion($conn, $sqlDescontar, "ii", [$cantidad, $idHabitacion]);

            if (!$resultadoDescontar['success']) {
                throw new Exception("Error al descontar habitaciones disponibles");
            }
        }

        // Calcular impuestos (16%)
        $impuestos = $subtotal * 0.16;
        $total = $subtotal + $impuestos;

        // Insertar reservación
        $sqlReservacion = "INSERT INTO reservaciones (id_usuario, fecha_entrada, fecha_salida, numero_noches, subtotal, impuestos, total, estado_reservacion, notas)
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada', ?)";

        $notas = isset($datos['notas']) ? $datos['notas'] : '';

        $resultadoReservacion = ejecutarModificacion($conn, $sqlReservacion, "issiddds", [
            $idUsuario,
            $fechaEntrada,
            $fechaSalida,
            $numeroNoches,
            $subtotal,
            $impuestos,
            $total,
            $notas
        ]);

        if (!$resultadoReservacion['success']) {
            throw new Exception("Error al crear la reservación");
        }

        $idReservacion = $resultadoReservacion['id'];

        // Insertar detalles de la reservación
        foreach ($detalles as $detalle) {
            $sqlDetalle = "INSERT INTO detalles_reservacion (id_reservacion, id_habitacion, cantidad_habitaciones, precio_unitario, subtotal)
                          VALUES (?, ?, ?, ?, ?)";

            $resultadoDetalle = ejecutarModificacion($conn, $sqlDetalle, "iiidd", [
                $idReservacion,
                $detalle['id_habitacion'],
                $detalle['cantidad'],
                $detalle['precio_unitario'],
                $detalle['subtotal']
            ]);

            if (!$resultadoDetalle['success']) {
                throw new Exception("Error al guardar los detalles de la reservación");
            }
        }

        // Confirmar transacción
        $conn->commit();
        cerrarConexion($conn);

        respuestaExito([
            'mensaje' => 'Reservación creada exitosamente',
            'id_reservacion' => $idReservacion,
            'total' => $total,
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'numero_noches' => $numeroNoches
        ]);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        cerrarConexion($conn);
        respuestaError($e->getMessage());
    }
}

/**
 * Lista todas las reservaciones (solo admin)
 */
function listarReservaciones() {
    if ($_SESSION['usuario_tipo'] !== 'admin') {
        respuestaError('No tienes permisos para ver todas las reservaciones', 403);
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT * FROM vista_reservaciones_completa ORDER BY fecha_reservacion DESC";
    $reservaciones = ejecutarConsulta($conn, $sql);

    // Obtener detalles de cada reservación
    foreach ($reservaciones as &$reservacion) {
        $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                       FROM detalles_reservacion dr
                       INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                       WHERE dr.id_reservacion = ?";
        $reservacion['detalles'] = ejecutarConsulta($conn, $sqlDetalles, "i", [$reservacion['id_reservacion']]);
    }

    cerrarConexion($conn);

    respuestaExito(['reservaciones' => $reservaciones]);
}

/**
 * Lista las reservaciones del usuario actual
 */
function listarMisReservaciones() {
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $idUsuario = $_SESSION['usuario_id'];

    $sql = "SELECT * FROM vista_reservaciones_completa WHERE id_usuario = ? ORDER BY fecha_reservacion DESC";
    $reservaciones = ejecutarConsulta($conn, $sql, "i", [$idUsuario]);

    // Obtener detalles de cada reservación
    foreach ($reservaciones as &$reservacion) {
        $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                       FROM detalles_reservacion dr
                       INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                       WHERE dr.id_reservacion = ?";
        $reservacion['detalles'] = ejecutarConsulta($conn, $sqlDetalles, "i", [$reservacion['id_reservacion']]);
    }

    cerrarConexion($conn);

    respuestaExito(['reservaciones' => $reservaciones]);
}

/**
 * Obtiene una reservación específica
 */
function obtenerReservacion($id) {
    if ($id <= 0) {
        respuestaError('ID de reservación no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT * FROM vista_reservaciones_completa WHERE id_reservacion = ?";

    // Si no es admin, solo puede ver sus propias reservaciones
    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $sql .= " AND id_usuario = " . $_SESSION['usuario_id'];
    }

    $resultado = ejecutarConsulta($conn, $sql, "i", [$id]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Reservación no encontrada', 404);
        return;
    }

    $reservacion = $resultado[0];

    // Obtener detalles
    $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                   FROM detalles_reservacion dr
                   INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                   WHERE dr.id_reservacion = ?";
    $reservacion['detalles'] = ejecutarConsulta($conn, $sqlDetalles, "i", [$id]);

    cerrarConexion($conn);

    respuestaExito(['reservacion' => $reservacion]);
}

/**
 * Cancela una reservación
 */
function cancelarReservacion($id) {
    if ($id <= 0) {
        respuestaError('ID de reservación no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Verificar que la reservación existe y pertenece al usuario (o es admin)
    $sqlVerificar = "SELECT id_reservacion, id_usuario, estado_reservacion
                    FROM reservaciones
                    WHERE id_reservacion = ?";

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $sqlVerificar .= " AND id_usuario = " . $_SESSION['usuario_id'];
    }

    $resultado = ejecutarConsulta($conn, $sqlVerificar, "i", [$id]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Reservación no encontrada', 404);
        return;
    }

    $reservacion = $resultado[0];

    if ($reservacion['estado_reservacion'] === 'cancelada') {
        cerrarConexion($conn);
        respuestaError('La reservación ya está cancelada');
        return;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Devolver las habitaciones al inventario
        $sqlDetalles = "SELECT id_habitacion, cantidad_habitaciones
                       FROM detalles_reservacion
                       WHERE id_reservacion = ?";
        $detalles = ejecutarConsulta($conn, $sqlDetalles, "i", [$id]);

        foreach ($detalles as $detalle) {
            $sqlDevolver = "UPDATE habitaciones
                          SET cantidad_disponible = cantidad_disponible + ?
                          WHERE id_habitacion = ?";
            ejecutarModificacion($conn, $sqlDevolver, "ii", [
                $detalle['cantidad_habitaciones'],
                $detalle['id_habitacion']
            ]);
        }

        // Actualizar estado de reservación
        $sqlCancelar = "UPDATE reservaciones SET estado_reservacion = 'cancelada' WHERE id_reservacion = ?";
        $resultadoCancelar = ejecutarModificacion($conn, $sqlCancelar, "i", [$id]);

        if (!$resultadoCancelar['success']) {
            throw new Exception("Error al cancelar la reservación");
        }

        $conn->commit();
        cerrarConexion($conn);

        respuestaExito(['mensaje' => 'Reservación cancelada exitosamente']);

    } catch (Exception $e) {
        $conn->rollback();
        cerrarConexion($conn);
        respuestaError($e->getMessage());
    }
}

/**
 * Verifica la disponibilidad de habitaciones para unas fechas
 */
function verificarDisponibilidad($datos) {
    if (empty($datos['fecha_entrada']) || empty($datos['fecha_salida'])) {
        respuestaError('Fechas de entrada y salida son requeridas');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Por ahora, simplemente retornamos la disponibilidad actual
    // En una implementación más avanzada, se verificaría contra reservaciones existentes
    $sql = "SELECT id_habitacion, numero_habitacion, nombre, cantidad_disponible, precio_noche
            FROM habitaciones
            WHERE activo = 1 AND cantidad_disponible > 0";

    $habitaciones = ejecutarConsulta($conn, $sql);

    cerrarConexion($conn);

    respuestaExito([
        'habitaciones_disponibles' => $habitaciones,
        'fecha_entrada' => $datos['fecha_entrada'],
        'fecha_salida' => $datos['fecha_salida']
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
