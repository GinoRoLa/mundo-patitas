<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS04Negocio.php';
$obj = new CUS04Negocio();

if (isset($_POST['idOrden'])) {
    $idOrden = intval($_POST['idOrden']);
    $estado = 'Salida';

    try {
        $resultado = $obj->generarSalidaAlmacen($idOrden, $estado);

        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Salida creada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la salida.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
}
