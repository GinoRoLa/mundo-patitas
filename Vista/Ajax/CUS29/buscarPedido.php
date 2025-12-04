<?php
// Vista/Ajax/CUS29/buscarPedido.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    
    $idPedido = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$idPedido) {
        throw new Exception('ID de pedido inválido.');
    }
    
    $negocio = new CUS29Negocio();
    $data = $negocio->obtenerInfoPedidoParaEntrega($idPedido);
    
    if (!$data) {
        throw new Exception('Pedido no encontrado o no tiene asignación de reparto.');
    }
    
    echo json_encode([
        'success' => true,
        'idDetalleAsignacion' => $data['Id_DetalleAsignacion'] ?? null,
        'idNotaCaja'          => $data['IDNotaCaja'] ?? null,
        'idRepartidor'        => $data['IDRepartidor'] ?? null,
        'totalContraEntrega'  => floatval($data['TotalContraEntrega'] ?? 0),
        'vueltoTotal'         => floatval($data['VueltoTotal'] ?? 0),
        'montoEsperado'       => floatval($data['Total'] ?? 0)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>