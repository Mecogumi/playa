<?php
require_once __DIR__ . '/../../config.inc.php';
require_once __DIR__ . '/../acceder_base_datos.php';
require_once __DIR__ . '/helpers.php';

function verificarAdmin() {
    if (!isset($_SESSION['sesion_iniciada']) || $_SESSION['sesion_iniciada'] !== true) {
        return false;
    }

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        return false;
    }

    return true;
}

function listarUsuarios() {
    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, email, telefono, tipo_usuario, activo, fecha_registro
            FROM usuarios
            ORDER BY fecha_registro DESC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        cerrarConexion($conn);
        respuestaError('Error al obtener usuarios');
        return;
    }

    $usuarios = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $row['activo'] = intval($row['activo']);
        $row['id_usuario'] = intval($row['id_usuario']);
        $usuarios[] = $row;
    }

    mysqli_free_result($result);
    cerrarConexion($conn);

    respuestaExito(['usuarios' => $usuarios]);
}

function obtenerUsuario($id) {
    if ($id <= 0) {
        respuestaError('ID de usuario no válido');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $id_escapado = mysqli_real_escape_string($conn, $id);

    $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, email, telefono, tipo_usuario, activo, fecha_registro
            FROM usuarios
            WHERE id_usuario = '$id_escapado'";

    $usuario = extraerRegistro($conn, $sql);

    cerrarConexion($conn);

    if (empty($usuario)) {
        respuestaError('Usuario no encontrado', 404);
        return;
    }

    $usuario['activo'] = intval($usuario['activo']);
    $usuario['id_usuario'] = intval($usuario['id_usuario']);

    respuestaExito(['usuario' => $usuario]);
}

function crearUsuario($datos) {
    if (empty($datos['nombre_usuario']) || empty($datos['contrasena']) ||
        empty($datos['nombre_completo']) || empty($datos['email']) ||
        empty($datos['tipo_usuario'])) {
        respuestaError('Todos los campos obligatorios son requeridos');
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

    if (!in_array($datos['tipo_usuario'], ['admin', 'huesped', 'no_registrado'])) {
        respuestaError('Tipo de usuario no válido');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $nombre_usuario_escapado = mysqli_real_escape_string($conn, $datos['nombre_usuario']);
    $email_escapado = mysqli_real_escape_string($conn, $datos['email']);

    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = '$nombre_usuario_escapado' OR email = '$email_escapado'";

    if (existeRegistro($conn, $sqlVerificar)) {
        cerrarConexion($conn);
        respuestaError('El nombre de usuario o email ya están registrados');
        return;
    }

    $hashContrasena = hashearContrasena($datos['contrasena']);

    $nombre_completo_escapado = mysqli_real_escape_string($conn, $datos['nombre_completo']);
    $hash_escapado = mysqli_real_escape_string($conn, $hashContrasena);
    $tipo_usuario_escapado = mysqli_real_escape_string($conn, $datos['tipo_usuario']);
    $telefono = isset($datos['telefono']) ? "'" . mysqli_real_escape_string($conn, $datos['telefono']) . "'" : "NULL";

    $sqlInsertar = "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario, activo)
                    VALUES ('$nombre_usuario_escapado', '$hash_escapado', '$nombre_completo_escapado', '$email_escapado', $telefono, '$tipo_usuario_escapado', 1)";

    $resultado = insertarDatos($conn, $sqlInsertar);

    if ($resultado) {
        $id_insertado = mysqli_insert_id($conn);
        cerrarConexion($conn);
        respuestaExito([
            'mensaje' => 'Usuario creado exitosamente',
            'id_usuario' => $id_insertado
        ]);
    } else {
        cerrarConexion($conn);
        respuestaError('Error al crear usuario');
    }
}

function actualizarUsuario($datos) {
    if (empty($datos['id_usuario'])) {
        respuestaError('ID de usuario es requerido');
        return;
    }

    if ($datos['id_usuario'] == $_SESSION['usuario_id'] &&
        isset($datos['tipo_usuario']) && $datos['tipo_usuario'] !== 'admin') {
        respuestaError('No puedes cambiar tu propio tipo de usuario');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $email_escapado = mysqli_real_escape_string($conn, $datos['email']);
    $id_usuario_escapado = mysqli_real_escape_string($conn, $datos['id_usuario']);

    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE email = '$email_escapado' AND id_usuario != '$id_usuario_escapado'";

    if (existeRegistro($conn, $sqlVerificar)) {
        cerrarConexion($conn);
        respuestaError('El email ya está registrado en otro usuario');
        return;
    }

    $nombre_completo_escapado = mysqli_real_escape_string($conn, $datos['nombre_completo']);
    $tipo_usuario_escapado = mysqli_real_escape_string($conn, $datos['tipo_usuario']);
    $activo_valor = intval($datos['activo']);
    $telefono = isset($datos['telefono']) ? "'" . mysqli_real_escape_string($conn, $datos['telefono']) . "'" : "NULL";

    if (!empty($datos['contrasena'])) {
        if (strlen($datos['contrasena']) < 6) {
            cerrarConexion($conn);
            respuestaError('La contraseña debe tener al menos 6 caracteres');
            return;
        }

        $hashContrasena = hashearContrasena($datos['contrasena']);
        $hash_escapado = mysqli_real_escape_string($conn, $hashContrasena);

        $sqlActualizar = "UPDATE usuarios SET
                          nombre_completo = '$nombre_completo_escapado',
                          email = '$email_escapado',
                          telefono = $telefono,
                          tipo_usuario = '$tipo_usuario_escapado',
                          activo = $activo_valor,
                          contrasena = '$hash_escapado'
                          WHERE id_usuario = '$id_usuario_escapado'";
    } else {
        $sqlActualizar = "UPDATE usuarios SET
                          nombre_completo = '$nombre_completo_escapado',
                          email = '$email_escapado',
                          telefono = $telefono,
                          tipo_usuario = '$tipo_usuario_escapado',
                          activo = $activo_valor
                          WHERE id_usuario = '$id_usuario_escapado'";
    }

    $resultado = editarDatos($conn, $sqlActualizar);

    cerrarConexion($conn);

    if ($resultado) {
        respuestaExito(['mensaje' => 'Usuario actualizado exitosamente']);
    } else {
        respuestaError('Error al actualizar usuario');
    }
}

function eliminarUsuario($id) {
    if ($id <= 0) {
        respuestaError('ID de usuario no válido');
        return;
    }

    if ($id == $_SESSION['usuario_id']) {
        respuestaError('No puedes eliminar tu propia cuenta');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $id_escapado = mysqli_real_escape_string($conn, $id);

    $sqlActualizar = "UPDATE usuarios SET activo = 0 WHERE id_usuario = '$id_escapado'";

    $resultado = editarDatos($conn, $sqlActualizar);

    cerrarConexion($conn);

    if ($resultado) {
        respuestaExito(['mensaje' => 'Usuario desactivado exitosamente']);
    } else {
        respuestaError('Error al desactivar usuario');
    }
}

function cambiarEstadoUsuario($datos) {
    if (empty($datos['id_usuario'])) {
        respuestaError('ID de usuario es requerido');
        return;
    }

    if ($datos['id_usuario'] == $_SESSION['usuario_id']) {
        respuestaError('No puedes cambiar tu propio estado');
        return;
    }

    $conn = abrirConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    seleccionarBaseDatos($conn);

    $nuevoEstado = isset($datos['activo']) ? intval($datos['activo']) : 1;
    $id_usuario_escapado = mysqli_real_escape_string($conn, $datos['id_usuario']);

    $sqlActualizar = "UPDATE usuarios SET activo = $nuevoEstado WHERE id_usuario = '$id_usuario_escapado'";

    $resultado = editarDatos($conn, $sqlActualizar);

    cerrarConexion($conn);

    if ($resultado) {
        respuestaExito(['mensaje' => 'Estado de usuario actualizado exitosamente']);
    } else {
        respuestaError('Error al actualizar estado');
    }
}
?>
