<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['idRequerimiento']) || empty($data['resultado'])) {
        throw new Exception('Datos incompletos para registrar la evaluación.');
    }

    $idReq = intval($data['idRequerimiento']);
    $idPartida = isset($data['idPartida']) ? intval($data['idPartida']) : 1001;
    $criterio = isset($data['criterio']) ? trim($data['criterio']) : 'Precio';
    $resultadoSimulado = $data['resultado'];

    $neg = new CUS13Negocio();
    $resultado = $neg->registrarEvaluacion($idReq, $idPartida, $resultadoSimulado, $criterio);

    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
