<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include_once '../../../Controlador/CUS26Negocio.php';
require_once('../../../Controlador/Conexion.php');

$conn = new Conexion();
$conexion = $conn->Conecta();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

try {
    $negocio = new CUS26Negocio();
    $resultado = $negocio->registrarReprogramacion($data);
    echo json_encode(['success' => $resultado]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
