<?php

header('Content-Type: application/json');
include_once '../../../Controlador/CUS01Negocio.php';
$obj = new CUS01Negocio();

if (isset($_POST['productos'], $_POST['idCliente'])) {

    $idCliente = intval($_POST['idCliente']);
    $productosJson = $_POST['productos'];
    try {
        $obj->generarPreorden($idCliente, $productosJson);
        echo json_encode(['success' => true, 'message' => 'PreOrden creada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
}
?>
