<?php
/**
 * API de Autenticación
 * Maneja login, logout y verificación de sesión
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/db.php';

// Obtener el método HTTP y la acción
$metodo = $_SERVER['REQUEST_METHOD'];
$entrada = json_decode(file_get_contents('php://input'), true);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($entrada['accion']) ? $entrada['accion'] : '');

// Enrutador de acciones
switch ($accion) {
    case 'login':
        if ($metodo === 'POST') {
            login($entrada);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    case 'logout':
        logout();
        break;

    case 'verificar':
        verificarSesion();
        break;

    case 'registro':
        if ($metodo === 'POST') {
            registrarUsuario($entrada);
        } else {
            respuestaError('Método no permitido', 405);
        }
        break;

    default:
        respuestaError('Acción no válida', 400);
        break;
}

/**
 * Procesa el login de usuario
 */
function login($datos) {
    if (empty($datos['usuario']) || empty($datos['contrasena'])) {
        respuestaError('Usuario y contraseña son requeridos');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT id_usuario, nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario, activo
            FROM usuarios
            WHERE nombre_usuario = ? AND activo = 1";

    $resultado = ejecutarConsulta($conn, $sql, "s", [$datos['usuario']]);

    if (empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('Credenciales incorrectas. Por favor verifica tu usuario y contraseña.');
        return;
    }

    $usuario = $resultado[0];

    // Verificar contraseña
    if (!verificarContrasena($datos['contrasena'], $usuario['contrasena'])) {
        cerrarConexion($conn);
        respuestaError('Credenciales incorrectas. Por favor verifica tu usuario y contraseña.');
        return;
    }

    // Crear sesión
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['usuario_nombre'] = $usuario['nombre_usuario'];
    $_SESSION['usuario_nombre_completo'] = $usuario['nombre_completo'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
    $_SESSION['sesion_iniciada'] = true;
    $_SESSION['ultima_actividad'] = time();

    cerrarConexion($conn);

    respuestaExito([
        'mensaje' => 'Inicio de sesión exitoso',
        'usuario' => [
            'id' => $usuario['id_usuario'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'nombre_completo' => $usuario['nombre_completo'],
            'email' => $usuario['email'],
            'telefono' => $usuario['telefono'],
            'tipo' => $usuario['tipo_usuario']
        ]
    ]);
}

/**
 * Cierra la sesión del usuario
 */
function logout() {
    @session_start();
    session_unset();
    session_destroy();

    respuestaExito(['mensaje' => 'Sesión cerrada exitosamente']);
}

/**
 * Verifica si hay una sesión activa
 */
function verificarSesion() {
    if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
        respuestaError('No hay sesión activa', 401);
        return;
    }

    // Verificar timeout de sesión (30 minutos)
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 1800)) {
        session_unset();
        session_destroy();
        respuestaError('La sesión ha expirado', 401);
        return;
    }

    $_SESSION['ultima_actividad'] = time();

    respuestaExito([
        'mensaje' => 'Sesión activa',
        'usuario' => [
            'id' => $_SESSION['usuario_id'],
            'nombre_usuario' => $_SESSION['usuario_nombre'],
            'nombre_completo' => $_SESSION['usuario_nombre_completo'],
            'email' => $_SESSION['usuario_email'],
            'tipo' => $_SESSION['usuario_tipo']
        ]
    ]);
}

/**
 * Registra un nuevo usuario (huésped)
 */
function registrarUsuario($datos) {
    // Validar datos requeridos
    if (empty($datos['usuario']) || empty($datos['contrasena']) ||
        empty($datos['nombre_completo']) || empty($datos['email'])) {
        respuestaError('Todos los campos son requeridos');
        return;
    }

    // Validar email
    if (!validarEmail($datos['email'])) {
        respuestaError('El email no es válido');
        return;
    }

    // Validar longitud de contraseña
    if (strlen($datos['contrasena']) < 6) {
        respuestaError('La contraseña debe tener al menos 6 caracteres');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    // Verificar si el usuario ya existe
    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR email = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "ss", [$datos['usuario'], $datos['email']]);

    if (!empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('El nombre de usuario o email ya están registrados');
        return;
    }

    // Hashear contraseña
    $hashContrasena = hashearContrasena($datos['contrasena']);

    // Insertar usuario
    $sqlInsertar = "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario)
                    VALUES (?, ?, ?, ?, ?, 'huesped')";

    $telefono = isset($datos['telefono']) ? $datos['telefono'] : null;

    $resultado = ejecutarModificacion($conn, $sqlInsertar, "sssss", [
        $datos['usuario'],
        $hashContrasena,
        $datos['nombre_completo'],
        $datos['email'],
        $telefono
    ]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito([
            'mensaje' => 'Usuario registrado exitosamente',
            'id_usuario' => $resultado['id']
        ]);
    } else {
        respuestaError('Error al registrar usuario: ' . $resultado['error']);
    }
}

/**
 * Envía una respuesta de éxito
 */
function respuestaExito($datos) {
    echo json_encode([
        'success' => true,
        'data' => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envía una respuesta de error
 */
function respuestaError($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode([
        'success' => false,
        'error' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
