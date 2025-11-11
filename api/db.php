<?php
require_once __DIR__ . '/../config.inc.php';

function obtenerConexion() {
    $conn = new mysqli(
        $GLOBALS["servidor"],
        $GLOBALS["usuario"],
        $GLOBALS["contrasena"],
        $GLOBALS["base_datos"]
    );

    if ($conn->connect_error) {
        error_log("Error de conexión: " . $conn->connect_error);
        return null;
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

function cerrarConexion($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

function ejecutarConsulta($conn, $sql, $tipos = "", $parametros = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error al preparar consulta: " . $conn->error);
        return false;
    }

    if (!empty($tipos) && !empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $resultado = $stmt->get_result();
    $datos = [];

    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
    }

    $stmt->close();
    return $datos;
}

function ejecutarModificacion($conn, $sql, $tipos = "", $parametros = []) {
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error al preparar consulta: " . $conn->error);
        return ['success' => false, 'error' => $conn->error];
    }

    if (!empty($tipos) && !empty($parametros)) {
        $stmt->bind_param($tipos, ...$parametros);
    }

    if (!$stmt->execute()) {
        error_log("Error al ejecutar consulta: " . $stmt->error);
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }

    $resultado = [
        'success' => true,
        'affected_rows' => $stmt->affected_rows
    ];

    if ($stmt->insert_id > 0) {
        $resultado['id'] = $stmt->insert_id;
    }

    $stmt->close();
    return $resultado;
}

function sanitizarCadena($cadena) {
    return htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashearContrasena($contrasena) {
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

function verificarContrasena($contrasena, $hash) {
    return password_verify($contrasena, $hash);
}
?>
