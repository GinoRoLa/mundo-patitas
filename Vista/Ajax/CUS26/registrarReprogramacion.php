<?php
header("Content-Type: text/plain; charset=UTF-8");
require_once '../../../Controlador/CUS26Negocio.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) throw new Exception("Datos no válidos");

    $negocio = new CUS26Negocio();

    $resultado = $negocio->registrarReprogramacion(
        $data["idConsolidacion"],
        $data["idPedido"],
        $data["idCliente"],
        $data["nombreCliente"],
        $data["observaciones"],
        $data["fechaReprogramacion"],
        $data["estado"]
    );

    if ($resultado) {
        echo "ÉXITO: Reprogramación registrada correctamente";
    } else {
        echo "ERROR: No se pudo registrar la reprogramación";
    }

} catch (Exception $e) {
    echo "ERROR DEL SERVIDOR: " . $e->getMessage();
}
