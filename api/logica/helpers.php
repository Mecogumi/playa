<?php
/**
 * Funciones helper comunes para todas las APIs
 */

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
