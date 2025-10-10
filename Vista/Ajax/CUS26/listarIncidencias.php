<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS26Negocio.php';

try {
    $obj = new CUS26Negocio();
    $data = $obj->listarIncidencias();

    if (!empty($data)) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay incidencias registradas.']);
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
?>
