<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS26Negocio.php';

try {
  $obj = new CUS26Negocio();
  $idDistrito = $_POST['idDistrito'] ?? null;

  if (!$idDistrito) {
    echo json_encode(['success' => false, 'message' => 'Debe ingresar un código de distrito.']);
    exit;
  }

  $pedidos = $obj->listarPedidosPorDistrito($idDistrito);
  echo json_encode(['success' => true, 'pedidos' => $pedidos]);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
