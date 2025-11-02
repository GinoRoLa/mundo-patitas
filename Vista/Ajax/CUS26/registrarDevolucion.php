<?php
header("Content-Type: text/plain; charset=UTF-8");
require_once '../../../Controlador/CUS26Negocio.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) throw new Exception("Datos no válidos");

    $idConsolidacion = $data["idConsolidacion"] ?? null;
    $idPedido       = $data["idPedido"] ?? null;
    $idCliente      = $data["idCliente"] ?? null;
    $observaciones  = $data["observaciones"] ?? "";

    if (!$idConsolidacion || !$idPedido || !$idCliente) {
        throw new Exception("Faltan datos obligatorios para registrar la devolución");
    }

    $negocio = new CUS26Negocio();
    $resultado = $negocio->registrarDevolucion($idConsolidacion, $idPedido, $idCliente, $observaciones);

    echo $resultado ? "ÉXITO: Devolución registrada" : "ERROR: No se pudo registrar devolución";

} catch (Exception $e) {
    echo "ERROR DEL SERVIDOR: " . $e->getMessage();
}
