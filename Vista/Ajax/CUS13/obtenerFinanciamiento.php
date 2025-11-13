<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';

try {
    $mes = $_GET['mes'] ?? date('Y-m');
    $neg = new CUS13Negocio();
    $data = $neg->obtenerFinanciamientoPeriodo($mes);

    // 👇 Agrega este echo para debug
    echo json_encode(['success' => true, 'mes' => $mes, 'data' => $data], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
