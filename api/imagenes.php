<?php
/**
 * API de Imágenes
 * Maneja la subida, actualización y eliminación de imágenes de habitaciones
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.inc.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
    respuestaError('No hay sesión activa', 401);
}

if ($_SESSION['usuario_tipo'] !== 'admin') {
    respuestaError('No tienes permisos para realizar esta acción', 403);
}

// Enrutador de acciones
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

/**
 * Sube una o más imágenes para una habitación
 */
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

    // Verificar que la habitación existe
    $sqlVerificar = "SELECT id_habitacion FROM habitaciones WHERE id_habitacion = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "i", [$idHabitacion]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('La habitación no existe');
        return;
    }

    // Directorio de destino
    $directorioDestino = __DIR__ . '/../uploads/habitaciones/';

    // Crear directorio si no existe
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    $archivosSubidos = [];
    $errores = [];

    // Verificar si ya hay imágenes principales
    $sqlVerificarPrincipal = "SELECT COUNT(*) as total FROM imagenes_habitacion WHERE id_habitacion = ? AND es_principal = 1";
    $resultadoPrincipal = ejecutarConsulta($conn, $sqlVerificarPrincipal, "i", [$idHabitacion]);
    $hayPrincipal = $resultadoPrincipal[0]['total'] > 0;

    // Procesar cada archivo
    $totalArchivos = count($_FILES['imagenes']['name']);

    for ($i = 0; $i < $totalArchivos; $i++) {
        if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
            $nombreArchivo = $_FILES['imagenes']['name'][$i];
            $tmpName = $_FILES['imagenes']['tmp_name'][$i];
            $tamano = $_FILES['imagenes']['size'][$i];

            // Validar tipo de archivo
            $extensionArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($extensionArchivo, $extensionesPermitidas)) {
                $errores[] = "El archivo $nombreArchivo no es una imagen válida";
                continue;
            }

            // Validar tamaño (máximo 5MB)
            if ($tamano > 5242880) {
                $errores[] = "El archivo $nombreArchivo excede el tamaño máximo permitido (5MB)";
                continue;
            }

            // Generar nombre único
            $nombreNuevo = 'hab_' . $idHabitacion . '_' . uniqid() . '.' . $extensionArchivo;
            $rutaCompleta = $directorioDestino . $nombreNuevo;

            // Mover archivo
            if (move_uploaded_file($tmpName, $rutaCompleta)) {
                // Guardar en base de datos
                $rutaRelativa = 'uploads/habitaciones/' . $nombreNuevo;

                // La primera imagen será la principal si no hay ninguna
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
                    unlink($rutaCompleta); // Eliminar archivo si no se guardó en BD
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

/**
 * Elimina una imagen
 */
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

    // Obtener información de la imagen
    $sqlObtener = "SELECT id_imagen, id_habitacion, ruta_archivo, es_principal FROM imagenes_habitacion WHERE id_imagen = ?";
    $resultado = ejecutarConsulta($conn, $sqlObtener, "i", [$id]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Imagen no encontrada', 404);
        return;
    }

    $imagen = $resultado[0];

    // Eliminar archivo físico
    $rutaArchivo = __DIR__ . '/../' . $imagen['ruta_archivo'];
    if (file_exists($rutaArchivo)) {
        unlink($rutaArchivo);
    }

    // Eliminar de base de datos
    $sqlEliminar = "DELETE FROM imagenes_habitacion WHERE id_imagen = ?";
    $resultadoEliminar = ejecutarModificacion($conn, $sqlEliminar, "i", [$id]);

    // Si era la imagen principal, establecer otra como principal
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

/**
 * Establece una imagen como principal
 */
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

    // Quitar el flag de principal a todas las imágenes de la habitación
    $sqlQuitar = "UPDATE imagenes_habitacion SET es_principal = 0 WHERE id_habitacion = ?";
    ejecutarModificacion($conn, $sqlQuitar, "i", [$datos['id_habitacion']]);

    // Establecer la nueva imagen principal
    $sqlEstablecer = "UPDATE imagenes_habitacion SET es_principal = 1 WHERE id_imagen = ?";
    $resultado = ejecutarModificacion($conn, $sqlEstablecer, "i", [$datos['id_imagen']]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Imagen principal establecida exitosamente']);
    } else {
        respuestaError('Error al establecer imagen principal');
    }
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
