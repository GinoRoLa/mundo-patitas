<?php
// Vista/Ajax/CUS29/registrarControlVuelto.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    
    $payload = json_decode(file_get_contents('php://input'), true);
    
    if (!$payload) {
        throw new Exception('Payload inválido.');
    }
    
    $idNotaCaja = $payload['idNotaCaja'] ?? null;
    $idEntrega = $payload['idEntrega'] ?? null;
    $montoRecibido = floatval($payload['montoRecibido'] ?? 0);
    $vueltoEntregado = floatval($payload['vueltoEntregado'] ?? 0);
    
    if (!$idNotaCaja || !$idEntrega) {
        throw new Exception('Faltan datos requeridos.');
    }
    
    $negocio = new CUS29Negocio();
    $resultado = $negocio->registrarMovimientosVuelto(
        $idNotaCaja,
        $idEntrega,
        $montoRecibido,
        $vueltoEntregado
    );
    
    if (!$resultado) {
        throw new Exception('No se pudo registrar el control de vuelto.');
    }
    
    // Obtener el nuevo saldo
    $nuevoSaldo = $negocio->obtenerSaldoVueltoActual($idNotaCaja);
    
    echo json_encode([
        'success' => true,
        'nuevoSaldo' => $nuevoSaldo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>