<?php
// Vista/Ajax/CUS29/registrarEntrega.php
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    
    $payload = json_decode(file_get_contents('php://input'), true);
    
    if (!$payload) {
        throw new Exception('Payload inválido.');
    }
    
    $idPedido = $payload['idPedido'] ?? null;
    $idDetalleAsign = $payload['idDetalleAsign'] ?? null;
    $idNotaCaja = $payload['idNotaCaja'] ?? null;
    $idTrabajador = $payload['idTrabajador'] ?? null;
    $estadoEntrega = $payload['estadoEntrega'] ?? 'Entregado';
    $observaciones = $payload['observaciones'] ?? '';
    
    if (!$idPedido || !$idDetalleAsign) {
        throw new Exception('Faltan datos requeridos para registrar la entrega.');
    }
    
    $negocio = new CUS29Negocio();
    $idEntrega = $negocio->registrarEntrega(
        $idPedido, 
        $idDetalleAsign, 
        $idNotaCaja,
        $idTrabajador,
        $estadoEntrega,
        $observaciones
    );
    
    if (!$idEntrega) {
        throw new Exception('No se pudo registrar la entrega.');
    }
    
    echo json_encode([
        'success' => true, 
        'idEntrega' => $idEntrega
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>