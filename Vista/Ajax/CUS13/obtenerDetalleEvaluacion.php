<?php
require_once '../../../Controlador/CUS13Negocio.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("ID de evaluación no especificado");
    }

    $idEval = intval($_GET['id']);
    $negocio = new CUS13Negocio();
    $data = $negocio->obtenerDetalleEvaluacion($idEval);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
