<?php
require_once('../../../Controlador/CUS13Negocio.php');
header('Content-Type: application/json; charset=utf-8');

try {
    $negocio = new CUS13Negocio();
    $evaluadas = $negocio->listarEvaluaciones();
    echo json_encode(['success' => true, 'data' => $evaluadas]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
