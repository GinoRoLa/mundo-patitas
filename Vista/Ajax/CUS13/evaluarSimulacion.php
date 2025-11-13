<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['idRequerimiento'])) {
        throw new Exception('ID de requerimiento no proporcionado.');
    }

    $idReq = intval($data['idRequerimiento']);
    $idPartida = isset($data['idPartida']) ? intval($data['idPartida']) : 1001;
    $criterio = isset($data['criterio']) ? trim($data['criterio']) : 'Precio';

    $neg = new CUS13Negocio();
    $resultado = $neg->evaluarSimulacion($idReq, $idPartida, $criterio);

    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
