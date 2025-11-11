<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';

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

    if (!verificarContrasena($datos['contrasena'], $usuario['contrasena'])) {
        cerrarConexion($conn);
        respuestaError('Credenciales incorrectas. Por favor verifica tu usuario y contraseña.');
        return;
    }

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

function logout() {
    @session_start();
    session_unset();
    session_destroy();

    respuestaExito(['mensaje' => 'Sesión cerrada exitosamente']);
}

function verificarSesion() {
    if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
        respuestaError('No hay sesión activa', 401);
        return;
    }

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

function registrarUsuario($datos) {
    if (empty($datos['usuario']) || empty($datos['contrasena']) ||
        empty($datos['nombre_completo']) || empty($datos['email'])) {
        respuestaError('Todos los campos son requeridos');
        return;
    }

    if (!validarEmail($datos['email'])) {
        respuestaError('El email no es válido');
        return;
    }

    if (strlen($datos['contrasena']) < 6) {
        respuestaError('La contraseña debe tener al menos 6 caracteres');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR email = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "ss", [$datos['usuario'], $datos['email']]);

    if (!empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('El nombre de usuario o email ya están registrados');
        return;
    }

    $hashContrasena = hashearContrasena($datos['contrasena']);

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
?>
