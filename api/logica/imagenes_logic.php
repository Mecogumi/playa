<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../config.inc.php';

function subirImagenes() {
    if (!isset($_POST['id_habitacion']) || empty($_POST['id_habitacion'])) {
        respuestaError('ID de habitación es requerido');
        return;
    }

    $idHabitacion = intval($_POST['id_habitacion']);

    if (!isset($_FILES['imagenes']) || empty($_FILES['imagenes']['name'][0])) {
        respuestaError('Debes seleccionar al menos una imagen');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlVerificar = "SELECT id_habitacion FROM habitaciones WHERE id_habitacion = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "i", [$idHabitacion]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('La habitación no existe');
        return;
    }

    $directorioDestino = __DIR__ . '/../../uploads/habitaciones/';

    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    $archivosSubidos = [];
    $errores = [];

    $sqlVerificarPrincipal = "SELECT COUNT(*) as total FROM imagenes_habitacion WHERE id_habitacion = ? AND es_principal = 1";
    $resultadoPrincipal = ejecutarConsulta($conn, $sqlVerificarPrincipal, "i", [$idHabitacion]);
    $hayPrincipal = $resultadoPrincipal[0]['total'] > 0;

    $totalArchivos = count($_FILES['imagenes']['name']);

    for ($i = 0; $i < $totalArchivos; $i++) {
        if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
            $nombreArchivo = $_FILES['imagenes']['name'][$i];
            $tmpName = $_FILES['imagenes']['tmp_name'][$i];
            $tamano = $_FILES['imagenes']['size'][$i];

            $extensionArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($extensionArchivo, $extensionesPermitidas)) {
                $errores[] = "El archivo $nombreArchivo no es una imagen válida";
                continue;
            }

            if ($tamano > 5242880) {
                $errores[] = "El archivo $nombreArchivo excede el tamaño máximo permitido (5MB)";
                continue;
            }

            $nombreNuevo = 'hab_' . $idHabitacion . '_' . uniqid() . '.' . $extensionArchivo;
            $rutaCompleta = $directorioDestino . $nombreNuevo;

            if (move_uploaded_file($tmpName, $rutaCompleta)) {
                $rutaRelativa = 'uploads/habitaciones/' . $nombreNuevo;

                $esPrincipal = (!$hayPrincipal && $i === 0) ? 1 : 0;

                $sqlInsertar = "INSERT INTO imagenes_habitacion (id_habitacion, nombre_archivo, ruta_archivo, es_principal, orden_visualizacion)
                                VALUES (?, ?, ?, ?, ?)";

                $resultadoInsertar = ejecutarModificacion($conn, $sqlInsertar, "issii", [
                    $idHabitacion,
                    $nombreNuevo,
                    $rutaRelativa,
                    $esPrincipal,
                    $i
                ]);

                if ($resultadoInsertar['success']) {
                    $archivosSubidos[] = [
                        'id' => $resultadoInsertar['id'],
                        'nombre' => $nombreNuevo,
                        'ruta' => $rutaRelativa,
                        'es_principal' => $esPrincipal
                    ];

                    if ($esPrincipal) {
                        $hayPrincipal = true;
                    }
                } else {
                    $errores[] = "Error al guardar $nombreArchivo en la base de datos";
                    unlink($rutaCompleta);
                }
            } else {
                $errores[] = "Error al subir $nombreArchivo";
            }
        } else {
            $errores[] = "Error en el archivo $nombreArchivo: código " . $_FILES['imagenes']['error'][$i];
        }
    }

    cerrarConexion($conn);

    if (!empty($archivosSubidos)) {
        respuestaExito([
            'mensaje' => count($archivosSubidos) . ' imagen(es) subida(s) exitosamente',
            'imagenes' => $archivosSubidos,
            'errores' => $errores
        ]);
    } else {
        respuestaError('No se pudo subir ninguna imagen: ' . implode(', ', $errores));
    }
}

function eliminarImagen($id) {
    if ($id <= 0) {
        respuestaError('ID de imagen no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlObtener = "SELECT id_imagen, id_habitacion, ruta_archivo, es_principal FROM imagenes_habitacion WHERE id_imagen = ?";
    $resultado = ejecutarConsulta($conn, $sqlObtener, "i", [$id]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Imagen no encontrada', 404);
        return;
    }

    $imagen = $resultado[0];

    $rutaArchivo = __DIR__ . '/../../' . $imagen['ruta_archivo'];
    if (file_exists($rutaArchivo)) {
        unlink($rutaArchivo);
    }

    $sqlEliminar = "DELETE FROM imagenes_habitacion WHERE id_imagen = ?";
    $resultadoEliminar = ejecutarModificacion($conn, $sqlEliminar, "i", [$id]);

    if ($imagen['es_principal'] == 1) {
        $sqlNuevaPrincipal = "UPDATE imagenes_habitacion
                              SET es_principal = 1
                              WHERE id_habitacion = ?
                              ORDER BY orden_visualizacion
                              LIMIT 1";
        ejecutarModificacion($conn, $sqlNuevaPrincipal, "i", [$imagen['id_habitacion']]);
    }

    cerrarConexion($conn);

    if ($resultadoEliminar['success']) {
        respuestaExito(['mensaje' => 'Imagen eliminada exitosamente']);
    } else {
        respuestaError('Error al eliminar imagen');
    }
}

function establecerImagenPrincipal($datos) {
    if (empty($datos['id_imagen']) || empty($datos['id_habitacion'])) {
        respuestaError('ID de imagen e ID de habitación son requeridos');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlQuitar = "UPDATE imagenes_habitacion SET es_principal = 0 WHERE id_habitacion = ?";
    ejecutarModificacion($conn, $sqlQuitar, "i", [$datos['id_habitacion']]);

    $sqlEstablecer = "UPDATE imagenes_habitacion SET es_principal = 1 WHERE id_imagen = ?";
    $resultado = ejecutarModificacion($conn, $sqlEstablecer, "i", [$datos['id_imagen']]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Imagen principal establecida exitosamente']);
    } else {
        respuestaError('Error al establecer imagen principal');
    }
}
?>
