<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS26Negocio.php';

try {
    $idDistrito = intval($_POST['idDistrito'] ?? 0);
    if (!$idDistrito) throw new Exception('Distrito no recibido');

    $obj = new CUS26Negocio();
    $pedidos = $obj->listarPedidosPorDistrito($idDistrito);

    $enReparto = array_filter($pedidos, fn($p) => $p['Estado'] === 'En reparto');
    $noEntregado = array_filter($pedidos, fn($p) => $p['Estado'] === 'No entregado');

    echo json_encode([
        'success' => true,
        'enReparto' => array_values($enReparto),
        'noEntregado' => array_values($noEntregado)
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
