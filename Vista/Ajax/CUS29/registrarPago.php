<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload) throw new Exception('Payload inválido.');

    $neg = new CUS29Negocio();
    // usa registrarPago del negocio: devuelve idPago o false
    $idPago = $neg->registrarPago(
      $payload['idEntrega'] ?? null,
      $payload['montoEsperado'] ?? 0,
      $payload['montoRecibido'] ?? 0,
      $payload['montoVuelto'] ?? 0
    );
    if (!$idPago) throw new Exception('No se pudo registrar pago.');
    echo json_encode(['success' => true, 'idPago' => $idPago]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
