<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['idRequerimiento'])) {
        throw new Exception('ID de requerimiento no proporcionado.');
    }

    $idReq = intval($input['idRequerimiento']);

    // Puedes recibir el idPartida si lo manejas desde el financiamiento actual
    $idPartida = isset($input['idPartida']) ? intval($input['idPartida']) : 1001;

    $neg = new CUS13Negocio();
    $resultado = $neg->evaluarYRegistrar($idReq, $idPartida, 'Precio+Rotacion+Proporcionalidad');

    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
