<?php
require_once '../../../Controlador/CUS26Negocio.php';
header('Content-Type: application/json');

$negocio = new CUS26Negocio();
echo json_encode($negocio->listarOSEEmitidas());
?>
