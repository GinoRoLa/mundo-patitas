<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS26Negocio.php';

$obj = new CUS26Negocio();
$data = $obj->listarIncidencias();
echo json_encode(['success' => true, 'data' => $data]);
?>
