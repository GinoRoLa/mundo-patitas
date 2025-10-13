<?php

header('Content-Type: application/json');
include_once '../../../Controlador/CUS22Negocio.php';

$obj = new CUS22Negocio();

try {
    if (!isset($_POST['dias_limite'])) {
        echo json_encode(['success' => false, 'message' => 'No se recibió el parámetro de días.']);
        exit;
    }

    $diasLimite = intval($_POST['dias_limite']);
    $repartidores = $obj->filtrarRepartidoresPorDias($diasLimite);

    echo json_encode([
        'success' => true,
        'data' => $repartidores
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>