<?php
header('Content-Type: application/json');
require_once '../../../Controlador/CUS13Negocio.php';
$neg = new CUS13Negocio();
$data = $neg->listarSolicitudesPendientes();
echo json_encode($data);
