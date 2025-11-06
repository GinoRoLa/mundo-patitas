<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Id_Requerimiento
    $neg = new CUS13Negocio();
    $data = $neg->obtenerDetalleSolicitud($id); // obtiene productos de la solicitud (no de evaluación)
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
