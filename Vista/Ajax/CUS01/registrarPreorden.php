<?php

header('Content-Type: application/json');
include_once '../../../Controlador/CUS01Negocio.php';
$obj = new CUS01Negocio();

if (isset($_POST['productos'], $_POST['idCliente'])) {

    $idCliente = intval($_POST['idCliente']);
    $productosJson = $_POST['productos'];
    try {
        $idPreorden = $obj->generarPreorden($idCliente, $productosJson);
        if ($idPreorden) {
            echo json_encode([
                'success' => true,
                'message' => "PreOrden #$idPreorden creada correctamente",
                'idPreorden' => $idPreorden
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo obtener el ID de la PreOrden']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
}
?>
