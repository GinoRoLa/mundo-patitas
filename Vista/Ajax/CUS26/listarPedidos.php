<?php
require_once '../../Controlador/CUS26Negocio.php';
header('Content-Type: application/json');

$negocio = new CUS26Negocio();
$datos = $negocio->listarPedidosPagados();
echo json_encode($datos);
