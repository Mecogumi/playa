<?php
require_once __DIR__ . '/../db.php';
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
    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, email, telefono, tipo_usuario, activo, fecha_registro
            FROM usuarios
            ORDER BY fecha_registro DESC";

    $usuarios = ejecutarConsulta($conn, $sql);

    cerrarConexion($conn);

    respuestaExito(['usuarios' => $usuarios]);
}

function obtenerUsuario($id) {
    if ($id <= 0) {
        respuestaError('ID de usuario no válido');
        return;
    }

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sql = "SELECT id_usuario, nombre_usuario, nombre_completo, email, telefono, tipo_usuario, activo, fecha_registro
            FROM usuarios
            WHERE id_usuario = ?";

    $resultado = ejecutarConsulta($conn, $sql, "i", [$id]);

    cerrarConexion($conn);

    if (empty($resultado)) {
        respuestaError('Usuario no encontrado', 404);
        return;
    }

    respuestaExito(['usuario' => $resultado[0]]);
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

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR email = ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "ss", [$datos['nombre_usuario'], $datos['email']]);

    if (!empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('El nombre de usuario o email ya están registrados');
        return;
    }

    $hashContrasena = hashearContrasena($datos['contrasena']);

    $sqlInsertar = "INSERT INTO usuarios (nombre_usuario, contrasena, nombre_completo, email, telefono, tipo_usuario, activo)
                    VALUES (?, ?, ?, ?, ?, ?, 1)";

    $telefono = isset($datos['telefono']) ? $datos['telefono'] : null;

    $resultado = ejecutarModificacion($conn, $sqlInsertar, "ssssss", [
        $datos['nombre_usuario'],
        $hashContrasena,
        $datos['nombre_completo'],
        $datos['email'],
        $telefono,
        $datos['tipo_usuario']
    ]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito([
            'mensaje' => 'Usuario creado exitosamente',
            'id_usuario' => $resultado['id']
        ]);
    } else {
        respuestaError('Error al crear usuario: ' . $resultado['error']);
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

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlVerificar = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
    $resultado = ejecutarConsulta($conn, $sqlVerificar, "si", [$datos['email'], $datos['id_usuario']]);

    if (!empty($resultado)) {
        cerrarConexion($conn);
        respuestaError('El email ya está registrado en otro usuario');
        return;
    }

    if (!empty($datos['contrasena'])) {
        if (strlen($datos['contrasena']) < 6) {
            cerrarConexion($conn);
            respuestaError('La contraseña debe tener al menos 6 caracteres');
            return;
        }

        $hashContrasena = hashearContrasena($datos['contrasena']);

        $sqlActualizar = "UPDATE usuarios SET
                          nombre_completo = ?, email = ?, telefono = ?,
                          tipo_usuario = ?, activo = ?, contrasena = ?
                          WHERE id_usuario = ?";

        $resultado = ejecutarModificacion($conn, $sqlActualizar, "ssssiis", [
            $datos['nombre_completo'],
            $datos['email'],
            isset($datos['telefono']) ? $datos['telefono'] : null,
            $datos['tipo_usuario'],
            $datos['activo'],
            $hashContrasena,
            $datos['id_usuario']
        ]);
    } else {
        $sqlActualizar = "UPDATE usuarios SET
                          nombre_completo = ?, email = ?, telefono = ?,
                          tipo_usuario = ?, activo = ?
                          WHERE id_usuario = ?";

        $resultado = ejecutarModificacion($conn, $sqlActualizar, "ssssii", [
            $datos['nombre_completo'],
            $datos['email'],
            isset($datos['telefono']) ? $datos['telefono'] : null,
            $datos['tipo_usuario'],
            $datos['activo'],
            $datos['id_usuario']
        ]);
    }

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Usuario actualizado exitosamente']);
    } else {
        respuestaError('Error al actualizar usuario: ' . $resultado['error']);
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

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $sqlActualizar = "UPDATE usuarios SET activo = 0 WHERE id_usuario = ?";
    $resultado = ejecutarModificacion($conn, $sqlActualizar, "i", [$id]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Usuario desactivado exitosamente']);
    } else {
        respuestaError('Error al desactivar usuario: ' . $resultado['error']);
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

    $conn = obtenerConexion();
    if (!$conn) {
        respuestaError('Error de conexión a la base de datos');
        return;
    }

    $nuevoEstado = isset($datos['activo']) ? intval($datos['activo']) : 1;

    $sqlActualizar = "UPDATE usuarios SET activo = ? WHERE id_usuario = ?";
    $resultado = ejecutarModificacion($conn, $sqlActualizar, "ii", [$nuevoEstado, $datos['id_usuario']]);

    cerrarConexion($conn);

    if ($resultado['success']) {
        respuestaExito(['mensaje' => 'Estado de usuario actualizado exitosamente']);
    } else {
        respuestaError('Error al actualizar estado: ' . $resultado['error']);
    }
}
?>
