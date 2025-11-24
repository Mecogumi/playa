<?php
require_once __DIR__ . '/../../config.inc.php';
require_once __DIR__ . '/../acceder_base_datos.php';
require_once __DIR__ . '/helpers.php';

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

function listarHabitaciones() {
    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa ORDER BY numero_habitacion";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE activo = 1 ORDER BY numero_habitacion";
    }

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener habitaciones');
        return;
    }

    $habitaciones = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $row['activo'] = intval($row['activo']);
        $row['id_habitacion'] = intval($row['id_habitacion']);

        $idHabitacion = mysqli_real_escape_string($conn, $row['id_habitacion']);

        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal, orden_visualizacion
                        FROM imagenes_habitacion
                        WHERE id_habitacion = '$idHabitacion'
                        ORDER BY es_principal DESC, orden_visualizacion";

        $resultImagenes = mysqli_query($conn, $sqlImagenes);
        $imagenes = array();

        if ($resultImagenes) {
            while ($imagen = mysqli_fetch_array($resultImagenes, MYSQLI_ASSOC)) {
                $imagen['es_principal'] = intval($imagen['es_principal']);
                $imagen['id_imagen'] = intval($imagen['id_imagen']);
                $imagenes[] = $imagen;
            }
            mysqli_free_result($resultImagenes);
        }

        $row['imagenes'] = $imagenes;
        $habitaciones[] = $row;
    }

    mysqli_free_result($result);
    cerrarConexion($conn);

    respuestaExito(['habitaciones' => $habitaciones]);
}

function obtenerHabitacion($id) {
    if ($id <= 0) {
        respuestaError('ID de habitación no válido');
        return;
    }

    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $idEscapado = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM vista_habitaciones_completa WHERE id_habitacion = '$idEscapado'";

    $habitacion = extraerRegistro($conn, $sql);

    if (empty($habitacion)) {
        cerrarConexion($conn);
        respuestaError('Habitación no encontrada', 404);
        return;
    }

    $habitacion['activo'] = intval($habitacion['activo']);
    $habitacion['id_habitacion'] = intval($habitacion['id_habitacion']);

    $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal, orden_visualizacion
                    FROM imagenes_habitacion
                    WHERE id_habitacion = '$idEscapado'
                    ORDER BY es_principal DESC, orden_visualizacion";

    $resultImagenes = mysqli_query($conn, $sqlImagenes);
    $imagenes = array();

    if ($resultImagenes) {
        while ($imagen = mysqli_fetch_array($resultImagenes, MYSQLI_ASSOC)) {
            $imagen['es_principal'] = intval($imagen['es_principal']);
            $imagen['id_imagen'] = intval($imagen['id_imagen']);
            $imagenes[] = $imagen;
        }
        mysqli_free_result($resultImagenes);
    }

    $habitacion['imagenes'] = $imagenes;
    cerrarConexion($conn);

    respuestaExito(['habitacion' => $habitacion]);
}

function crearHabitacion($datos) {
    if (empty($datos['numero_habitacion']) || empty($datos['id_categoria']) ||
        empty($datos['nombre']) || empty($datos['precio_noche']) ||
        empty($datos['capacidad_personas']) || empty($datos['cantidad_disponible'])) {
        respuestaError('Todos los campos obligatorios deben ser completados');
        return;
    }

    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $numeroHabitacion = mysqli_real_escape_string($conn, $datos['numero_habitacion']);
    $sqlVerificar = "SELECT id_habitacion FROM habitaciones WHERE numero_habitacion = '$numeroHabitacion'";

    if (existeRegistro($conn, $sqlVerificar)) {
        cerrarConexion($conn);
        respuestaError('El número de habitación ya existe');
        return;
    }

    $idCategoria = mysqli_real_escape_string($conn, $datos['id_categoria']);
    $nombre = mysqli_real_escape_string($conn, $datos['nombre']);
    $descripcion = isset($datos['descripcion']) ? mysqli_real_escape_string($conn, $datos['descripcion']) : '';
    $precioNoche = mysqli_real_escape_string($conn, $datos['precio_noche']);
    $capacidadPersonas = mysqli_real_escape_string($conn, $datos['capacidad_personas']);
    $cantidadDisponible = mysqli_real_escape_string($conn, $datos['cantidad_disponible']);
    $caracteristicas = isset($datos['caracteristicas']) ? mysqli_real_escape_string($conn, $datos['caracteristicas']) : '';

    $sqlInsertar = "INSERT INTO habitaciones (numero_habitacion, id_categoria, nombre, descripcion,
                    precio_noche, capacidad_personas, cantidad_disponible, caracteristicas)
                    VALUES ('$numeroHabitacion', '$idCategoria', '$nombre', '$descripcion',
                    '$precioNoche', '$capacidadPersonas', '$cantidadDisponible', '$caracteristicas')";

    $resultado = insertarDatos($conn, $sqlInsertar);

    if ($resultado) {
        $idHabitacion = mysqli_insert_id($conn);
        cerrarConexion($conn);
        respuestaExito([
            'mensaje' => 'Habitación creada exitosamente',
            'id_habitacion' => $idHabitacion
        ]);
    } else {
        cerrarConexion($conn);
        respuestaError('Error al crear habitación');
    }
}

function actualizarHabitacion($datos) {
    if (empty($datos['id_habitacion'])) {
        respuestaError('ID de habitación es requerido');
        return;
    }

    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $idHabitacion = mysqli_real_escape_string($conn, $datos['id_habitacion']);
    $idCategoria = mysqli_real_escape_string($conn, $datos['id_categoria']);
    $nombre = mysqli_real_escape_string($conn, $datos['nombre']);
    $descripcion = isset($datos['descripcion']) ? mysqli_real_escape_string($conn, $datos['descripcion']) : '';
    $precioNoche = mysqli_real_escape_string($conn, $datos['precio_noche']);
    $capacidadPersonas = mysqli_real_escape_string($conn, $datos['capacidad_personas']);
    $cantidadDisponible = mysqli_real_escape_string($conn, $datos['cantidad_disponible']);
    $caracteristicas = isset($datos['caracteristicas']) ? mysqli_real_escape_string($conn, $datos['caracteristicas']) : '';
    $activo = isset($datos['activo']) ? intval($datos['activo']) : 1;

    $sqlActualizar = "UPDATE habitaciones SET
                      id_categoria = '$idCategoria',
                      nombre = '$nombre',
                      descripcion = '$descripcion',
                      precio_noche = '$precioNoche',
                      capacidad_personas = '$capacidadPersonas',
                      cantidad_disponible = '$cantidadDisponible',
                      caracteristicas = '$caracteristicas',
                      activo = $activo
                      WHERE id_habitacion = '$idHabitacion'";

    $resultado = editarDatos($conn, $sqlActualizar);
    cerrarConexion($conn);

    if ($resultado) {
        respuestaExito(['mensaje' => 'Habitación actualizada exitosamente']);
    } else {
        respuestaError('Error al actualizar habitación');
    }
}


function eliminarHabitacion($id) {
    if ($id <= 0) {
        respuestaError('ID de habitación no válido');
        return;
    }

    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $idEscapado = mysqli_real_escape_string($conn, $id);
    $sqlActualizar = "UPDATE habitaciones SET activo = 0 WHERE id_habitacion = '$idEscapado'";

    $resultado = editarDatos($conn, $sqlActualizar);
    cerrarConexion($conn);

    if ($resultado) {
        respuestaExito(['mensaje' => 'Habitación eliminada exitosamente']);
    } else {
        respuestaError('Error al eliminar habitación');
    }
}

function listarPorCategoria($idCategoria) {
    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE 1=1";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa WHERE activo = 1";
    }

    if ($idCategoria > 0) {
        $idCategoriaEscapado = mysqli_real_escape_string($conn, $idCategoria);
        $sql .= " AND id_categoria = '$idCategoriaEscapado'";
    }

    $sql .= " ORDER BY numero_habitacion";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener habitaciones');
        return;
    }

    $habitaciones = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $row['activo'] = intval($row['activo']);
        $row['id_habitacion'] = intval($row['id_habitacion']);

        $idHabitacion = mysqli_real_escape_string($conn, $row['id_habitacion']);

        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal
                        FROM imagenes_habitacion
                        WHERE id_habitacion = '$idHabitacion'
                        ORDER BY es_principal DESC, orden_visualizacion";

        $resultImagenes = mysqli_query($conn, $sqlImagenes);
        $imagenes = array();

        if ($resultImagenes) {
            while ($imagen = mysqli_fetch_array($resultImagenes, MYSQLI_ASSOC)) {
                $imagen['es_principal'] = intval($imagen['es_principal']);
                $imagen['id_imagen'] = intval($imagen['id_imagen']);
                $imagenes[] = $imagen;
            }
            mysqli_free_result($resultImagenes);
        }

        $row['imagenes'] = $imagenes;
        $habitaciones[] = $row;
    }

    mysqli_free_result($result);
    cerrarConexion($conn);

    respuestaExito(['habitaciones' => $habitaciones]);
}

function listarCategorias() {
    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $sql = "SELECT c.*, COUNT(h.id_habitacion) as total_habitaciones
            FROM categorias_habitacion c
            LEFT JOIN habitaciones h ON c.id_categoria = h.id_categoria AND h.activo = 1
            GROUP BY c.id_categoria
            ORDER BY c.nombre_categoria";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener categorías');
        return;
    }

    $categorias = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $categorias[] = $row;
    }

    mysqli_free_result($result);
    cerrarConexion($conn);

    respuestaExito(['categorias' => $categorias]);
}

function buscarHabitaciones($termino) {
    if (empty($termino)) {
        listarHabitaciones();
        return;
    }

    $conn = abrirConexion();
    seleccionarBaseDatos($conn);

    $terminoBusqueda = mysqli_real_escape_string($conn, '%' . $termino . '%');

    $mostrarTodas = isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';

    if ($mostrarTodas) {
        $sql = "SELECT * FROM vista_habitaciones_completa
                WHERE (
                    nombre LIKE '$terminoBusqueda' OR
                    descripcion LIKE '$terminoBusqueda' OR
                    caracteristicas LIKE '$terminoBusqueda' OR
                    nombre_categoria LIKE '$terminoBusqueda' OR
                    numero_habitacion LIKE '$terminoBusqueda'
                )
                ORDER BY numero_habitacion";
    } else {
        $sql = "SELECT * FROM vista_habitaciones_completa
                WHERE activo = 1 AND (
                    nombre LIKE '$terminoBusqueda' OR
                    descripcion LIKE '$terminoBusqueda' OR
                    caracteristicas LIKE '$terminoBusqueda' OR
                    nombre_categoria LIKE '$terminoBusqueda' OR
                    numero_habitacion LIKE '$terminoBusqueda'
                )
                ORDER BY numero_habitacion";
    }

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al buscar habitaciones');
        return;
    }

    $habitaciones = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $row['activo'] = intval($row['activo']);
        $row['id_habitacion'] = intval($row['id_habitacion']);

        $idHabitacion = mysqli_real_escape_string($conn, $row['id_habitacion']);

        $sqlImagenes = "SELECT id_imagen, nombre_archivo, ruta_archivo, es_principal
                        FROM imagenes_habitacion
                        WHERE id_habitacion = '$idHabitacion'
                        ORDER BY es_principal DESC, orden_visualizacion";

        $resultImagenes = mysqli_query($conn, $sqlImagenes);
        $imagenes = array();

        if ($resultImagenes) {
            while ($imagen = mysqli_fetch_array($resultImagenes, MYSQLI_ASSOC)) {
                $imagen['es_principal'] = intval($imagen['es_principal']);
                $imagen['id_imagen'] = intval($imagen['id_imagen']);
                $imagenes[] = $imagen;
            }
            mysqli_free_result($resultImagenes);
        }

        $row['imagenes'] = $imagenes;
        $habitaciones[] = $row;
    }

    mysqli_free_result($result);
    cerrarConexion($conn);

    respuestaExito([
        'habitaciones' => $habitaciones,
        'total_resultados' => count($habitaciones),
        'termino_busqueda' => $termino
    ]);
}
?>
