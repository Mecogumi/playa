<?php
require_once __DIR__ . '/../../config.inc.php';
require_once __DIR__ . '/../acceder_base_datos.php';
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

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $conn->begin_transaction();

    try {
        $idUsuario = intval($_SESSION['usuario_id']);
        $fechaEntrada = mysqli_real_escape_string($conn, $datos['fecha_entrada']);
        $fechaSalida = mysqli_real_escape_string($conn, $datos['fecha_salida']);

        $entrada = new DateTime($fechaEntrada);
        $salida = new DateTime($fechaSalida);
        $numeroNoches = $entrada->diff($salida)->days;

        if ($numeroNoches <= 0) {
            throw new Exception('La fecha de salida debe ser posterior a la fecha de entrada');
        }

        $subtotal = 0;
        $detalles = [];

        foreach ($habitaciones as $habitacion) {
            $idHabitacion = intval($habitacion['id_habitacion']);
            $cantidad = intval($habitacion['cantidad']);

            $sqlVerificar = "SELECT id_habitacion, cantidad_disponible, precio_noche, nombre
                            FROM habitaciones
                            WHERE id_habitacion = $idHabitacion AND activo = 1";

            $hab = extraerRegistro($conn, $sqlVerificar);

            if (empty($hab)) {
                throw new Exception("La habitación con ID $idHabitacion no existe o no está disponible");
            }

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
                            SET cantidad_disponible = cantidad_disponible - $cantidad
                            WHERE id_habitacion = $idHabitacion";

            $resultadoDescontar = editarDatos($conn, $sqlDescontar);

            if (!$resultadoDescontar) {
                throw new Exception("Error al descontar habitaciones disponibles");
            }
        }

        $impuestos = $subtotal * 0.16;
        $total = $subtotal + $impuestos;

        $notas = isset($datos['notas']) ? mysqli_real_escape_string($conn, $datos['notas']) : '';

        $sqlReservacion = "INSERT INTO reservaciones (id_usuario, fecha_entrada, fecha_salida, numero_noches, subtotal, impuestos, total, estado_reservacion, notas)
                          VALUES ($idUsuario, '$fechaEntrada', '$fechaSalida', $numeroNoches, $subtotal, $impuestos, $total, 'confirmada', '$notas')";

        $resultadoReservacion = insertarDatos($conn, $sqlReservacion);

        if (!$resultadoReservacion) {
            throw new Exception("Error al crear la reservación");
        }

        $idReservacion = mysqli_insert_id($conn);

        foreach ($detalles as $detalle) {
            $sqlDetalle = "INSERT INTO detalles_reservacion (id_reservacion, id_habitacion, cantidad_habitaciones, precio_unitario, subtotal)
                          VALUES ($idReservacion, {$detalle['id_habitacion']}, {$detalle['cantidad']}, {$detalle['precio_unitario']}, {$detalle['subtotal']})";

            $resultadoDetalle = insertarDatos($conn, $sqlDetalle);

            if (!$resultadoDetalle) {
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

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $sql = "SELECT * FROM vista_reservaciones_completa ORDER BY fecha_reservacion DESC";

    // Obtener múltiples registros usando mysqli_query y mysqli_fetch_array
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener las reservaciones');
        return;
    }

    $reservaciones = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $reservaciones[] = $row;
    }
    mysqli_free_result($result);

    foreach ($reservaciones as &$reservacion) {
        $idReservacion = intval($reservacion['id_reservacion']);
        $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                       FROM detalles_reservacion dr
                       INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                       WHERE dr.id_reservacion = $idReservacion";

        $resultDetalles = mysqli_query($conn, $sqlDetalles);
        $detalles = [];
        if ($resultDetalles) {
            while ($detalle = mysqli_fetch_array($resultDetalles, MYSQLI_ASSOC)) {
                $detalles[] = $detalle;
            }
            mysqli_free_result($resultDetalles);
        }
        $reservacion['detalles'] = $detalles;
    }

    cerrarConexion($conn);

    respuestaExito(['reservaciones' => $reservaciones]);
}

function listarMisReservaciones() {
    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $idUsuario = intval($_SESSION['usuario_id']);

    $sql = "SELECT * FROM vista_reservaciones_completa WHERE id_usuario = $idUsuario ORDER BY fecha_reservacion DESC";

    // Obtener múltiples registros usando mysqli_query y mysqli_fetch_array
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener las reservaciones');
        return;
    }

    $reservaciones = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $reservaciones[] = $row;
    }
    mysqli_free_result($result);

    foreach ($reservaciones as &$reservacion) {
        $idReservacion = intval($reservacion['id_reservacion']);
        $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                       FROM detalles_reservacion dr
                       INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                       WHERE dr.id_reservacion = $idReservacion";

        $resultDetalles = mysqli_query($conn, $sqlDetalles);
        $detalles = [];
        if ($resultDetalles) {
            while ($detalle = mysqli_fetch_array($resultDetalles, MYSQLI_ASSOC)) {
                $detalles[] = $detalle;
            }
            mysqli_free_result($resultDetalles);
        }
        $reservacion['detalles'] = $detalles;
    }

    cerrarConexion($conn);

    respuestaExito(['reservaciones' => $reservaciones]);
}

function obtenerReservacion($id) {
    if ($id <= 0) {
        respuestaError('ID de reservación no válido');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $id = intval($id);
    $sql = "SELECT * FROM vista_reservaciones_completa WHERE id_reservacion = $id";

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $idUsuario = intval($_SESSION['usuario_id']);
        $sql .= " AND id_usuario = $idUsuario";
    }

    $reservacion = extraerRegistro($conn, $sql);

    if (empty($reservacion)) {
        cerrarConexion($conn);
        respuestaError('Reservación no encontrada', 404);
        return;
    }

    $idReservacion = intval($reservacion['id_reservacion']);
    $sqlDetalles = "SELECT dr.*, h.nombre, h.numero_habitacion
                   FROM detalles_reservacion dr
                   INNER JOIN habitaciones h ON dr.id_habitacion = h.id_habitacion
                   WHERE dr.id_reservacion = $idReservacion";

    $resultDetalles = mysqli_query($conn, $sqlDetalles);
    $detalles = [];
    if ($resultDetalles) {
        while ($detalle = mysqli_fetch_array($resultDetalles, MYSQLI_ASSOC)) {
            $detalles[] = $detalle;
        }
        mysqli_free_result($resultDetalles);
    }
    $reservacion['detalles'] = $detalles;

    cerrarConexion($conn);

    respuestaExito(['reservacion' => $reservacion]);
}

function cancelarReservacion($id) {
    if ($id <= 0) {
        respuestaError('ID de reservación no válido');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $id = intval($id);
    $sqlVerificar = "SELECT id_reservacion, id_usuario, estado_reservacion
                    FROM reservaciones
                    WHERE id_reservacion = $id";

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        $idUsuario = intval($_SESSION['usuario_id']);
        $sqlVerificar .= " AND id_usuario = $idUsuario";
    }

    $reservacion = extraerRegistro($conn, $sqlVerificar);

    if (empty($reservacion)) {
        cerrarConexion($conn);
        respuestaError('Reservación no encontrada', 404);
        return;
    }

    if ($reservacion['estado_reservacion'] === 'cancelada') {
        cerrarConexion($conn);
        respuestaError('La reservación ya está cancelada');
        return;
    }

    $conn->begin_transaction();

    try {
        $idReservacion = intval($reservacion['id_reservacion']);
        $sqlDetalles = "SELECT id_habitacion, cantidad_habitaciones
                       FROM detalles_reservacion
                       WHERE id_reservacion = $idReservacion";

        $resultDetalles = mysqli_query($conn, $sqlDetalles);
        if (!$resultDetalles) {
            throw new Exception("Error al obtener los detalles de la reservación");
        }

        $detalles = [];
        while ($detalle = mysqli_fetch_array($resultDetalles, MYSQLI_ASSOC)) {
            $detalles[] = $detalle;
        }
        mysqli_free_result($resultDetalles);

        foreach ($detalles as $detalle) {
            $idHabitacion = intval($detalle['id_habitacion']);
            $cantidadHabitaciones = intval($detalle['cantidad_habitaciones']);

            $sqlDevolver = "UPDATE habitaciones
                          SET cantidad_disponible = cantidad_disponible + $cantidadHabitaciones
                          WHERE id_habitacion = $idHabitacion";

            $resultadoDevolver = editarDatos($conn, $sqlDevolver);
            if (!$resultadoDevolver) {
                throw new Exception("Error al devolver las habitaciones disponibles");
            }
        }

        $sqlCancelar = "UPDATE reservaciones SET estado_reservacion = 'cancelada' WHERE id_reservacion = $idReservacion";
        $resultadoCancelar = editarDatos($conn, $sqlCancelar);

        if (!$resultadoCancelar) {
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

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }
    seleccionarBaseDatos($conn);

    $sql = "SELECT id_habitacion, numero_habitacion, nombre, cantidad_disponible, precio_noche
            FROM habitaciones
            WHERE activo = 1 AND cantidad_disponible > 0";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener las habitaciones disponibles');
        return;
    }

    $habitaciones = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $habitaciones[] = $row;
    }
    mysqli_free_result($result);

    cerrarConexion($conn);

    respuestaExito([
        'habitaciones_disponibles' => $habitaciones,
        'fecha_entrada' => $datos['fecha_entrada'],
        'fecha_salida' => $datos['fecha_salida']
    ]);
}
?>
