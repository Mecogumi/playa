<?php
/**
 * Archivo de utilidades para la base de datos
 * Maneja la conexión y operaciones comunes
 */

require_once __DIR__ . '/../config.inc.php';

/**
 * Obtiene una conexión a la base de datos
 * @return mysqli|null Conexión o null en caso de error
 */
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

/**
 * Cierra una conexión a la base de datos
 * @param mysqli $conn Conexión a cerrar
 */
function cerrarConexion($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

/**
 * Ejecuta una consulta preparada y devuelve los resultados
 * @param mysqli $conn Conexión a la base de datos
 * @param string $sql Consulta SQL con placeholders
 * @param string $tipos Tipos de datos (i, d, s, b)
 * @param array $parametros Parámetros a enlazar
 * @return array|false Array de resultados o false en caso de error
 */
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

/**
 * Ejecuta una consulta de inserción, actualización o eliminación
 * @param mysqli $conn Conexión a la base de datos
 * @param string $sql Consulta SQL con placeholders
 * @param string $tipos Tipos de datos (i, d, s, b)
 * @param array $parametros Parámetros a enlazar
 * @return array Array con 'success' y opcionalmente 'id' o 'affected_rows'
 */
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

/**
 * Sanitiza una cadena para prevenir XSS
 * @param string $cadena Cadena a sanitizar
 * @return string Cadena sanitizada
 */
function sanitizarCadena($cadena) {
    return htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida si un email es válido
 * @param string $email Email a validar
 * @return bool True si es válido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Genera un hash seguro para contraseñas
 * @param string $contrasena Contraseña en texto plano
 * @return string Hash de la contraseña
 */
function hashearContrasena($contrasena) {
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

/**
 * Verifica si una contraseña coincide con un hash
 * @param string $contrasena Contraseña en texto plano
 * @param string $hash Hash almacenado
 * @return bool True si coincide
 */
function verificarContrasena($contrasena, $hash) {
    return password_verify($contrasena, $hash);
}
?>
