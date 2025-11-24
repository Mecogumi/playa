<?php
require_once __DIR__ . '/../acceder_base_datos.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../config.inc.php';

function login($datos) {
    if (empty($datos['usuario']) || empty($datos['contrasena'])) {
        respuestaError('Usuario y contraseña son requeridos');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $usuario_escapado = mysqli_real_escape_string($conn, $datos['usuario']);
    $sql = "SELECT id_usuario, nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario, activo
            FROM usuarios
            WHERE nombre_usuario = '$usuario_escapado' AND activo = 1";

    $usuario = extraerRegistro($conn, $sql);

    if (empty($usuario)) {
        cerrarConexion($conn);
        respuestaError('Credenciales incorrectas. Por favor verifica tu usuario y contraseña.');
        return;
    }

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

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $usuario_escapado = mysqli_real_escape_string($conn, $datos['usuario']);
    $email_escapado = mysqli_real_escape_string($conn, $datos['email']);
    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = '$usuario_escapado' OR email = '$email_escapado'";

    if (existeRegistro($conn, $sqlVerificar)) {
        cerrarConexion($conn);
        respuestaError('El nombre de usuario o email ya están registrados');
        return;
    }

    $hashContrasena = hashearContrasena($datos['contrasena']);
    $nombre_completo_escapado = mysqli_real_escape_string($conn, $datos['nombre_completo']);
    $hash_escapado = mysqli_real_escape_string($conn, $hashContrasena);
    $telefono_escapado = isset($datos['telefono']) ? mysqli_real_escape_string($conn, $datos['telefono']) : '';

    $sqlInsertar = "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario)
                    VALUES ('$usuario_escapado', '$hash_escapado', '$nombre_completo_escapado', '$email_escapado', '$telefono_escapado', 'huesped')";

    $resultado = insertarDatos($conn, $sqlInsertar);

    if ($resultado) {
        $id_insertado = mysqli_insert_id($conn);
        cerrarConexion($conn);
        respuestaExito([
            'mensaje' => 'Usuario registrado exitosamente',
            'id_usuario' => $id_insertado
        ]);
    } else {
        cerrarConexion($conn);
        respuestaError('Error al registrar usuario');
    }
}
?>
