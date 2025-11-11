<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

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

function crearReservacion($datos) {
    if (empty($datos['fecha_entrada']) || empty($datos['fecha_salida'])) {
        respuestaError('Faltan datos requeridos para la reservación');
        return;
    }

    if (!isset($_COOKIE['carrito_habitaciones']) || empty($_COOKIE['carrito_habitaciones'])) {
        respuestaError('No hay habitaciones en el carrito (cookie no encontrada)');
        return;
    }

    $carritoCompleto = json_decode($_COOKIE['carrito_habitaciones'], true);

    if (!is_array($carritoCompleto) || count($carritoCompleto) === 0) {
        respuestaError('El carrito está vacío o tiene un formato inválido');
        return;
    }

    $habitaciones = array_map(function($item) {
        return [
            'id_habitacion' => isset($item['id_habitacion']) ? intval($item['id_habitacion']) : 0,
            'cantidad' => isset($item['cantidad']) ? intval($item['cantidad']) : 0
        ];
    }, $carritoCompleto);

    foreach ($habitaciones as $hab) {
        if ($hab['id_habitacion'] <= 0 || $hab['cantidad'] <= 0) {
            respuestaError('Datos de habitaciones inválidos en el carrito');
            return;
        }
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $conn->begin_transaction();

    try {
        $idUsuario = $_SESSION['usuario_id'];
        $fechaEntrada = $datos['fecha_entrada'];
        $fechaSalida = $datos['fecha_salida'];

        $entrada = new DateTime($fechaEntrada);
        $salida = new DateTime($fechaSalida);
        $numeroNoches = $entrada->diff($salida)->days;

        if ($numeroNoches <= 0) {
            throw new Exception('La fecha de salida debe ser posterior a la fecha de entrada');
        }

        $subtotal = 0;
        $detalles = [];

        foreach ($habitaciones as $habitacion) {
            $idHabitacion = $habitacion['id_habitacion'];
            $cantidad = $habitacion['cantidad'];

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

            $precioUnitario = $hab['precio_noche'] * $numeroNoches;
            $subtotalHabitacion = $precioUnitario * $cantidad;
            $subtotal += $subtotalHabitacion;

            $detalles[] = [
                'id_habitacion' => $idHabitacion,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalHabitacion
            ];

            $sqlDescontar = "UPDATE habitaciones
                            SET cantidad_disponible = cantidad_disponible - ?
                            WHERE id_habitacion = ?";
            $resultadoDescontar = ejecutarModificacion($conn, $sqlDescontar, "ii", [$cantidad, $idHabitacion]);

            if (!$resultadoDescontar['success']) {
                throw new Exception("Error al descontar habitaciones disponibles");
            }
        }

        $impuestos = $subtotal * 0.16;
        $total = $subtotal + $impuestos;

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
        $conn->rollback();
        cerrarConexion($conn);
        respuestaError($e->getMessage());
    }
}

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

function listarMisReservaciones() {
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $idUsuario = $_SESSION['usuario_id'];

    $sql = "SELECT * FROM vista_reservaciones_completa WHERE id_usuario = ? ORDER BY fecha_reservacion DESC";
    $reservaciones = ejecutarConsulta($conn, $sql, "i", [$idUsuario]);

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

    $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                   FROM detalles_reservacion dr
                   INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                   WHERE dr.id_reservacion = ?";
    $reservacion['detalles'] = ejecutarConsulta($conn, $sqlDetalles, "i", [$id]);

    cerrarConexion($conn);

    respuestaExito(['reservacion' => $reservacion]);
}

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

    $conn->begin_transaction();

    try {
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
?>
