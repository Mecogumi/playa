<?php
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
