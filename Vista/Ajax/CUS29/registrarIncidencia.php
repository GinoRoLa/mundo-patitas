<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    $p = json_decode(file_get_contents('php://input'), true);
    if (!$p) throw new Exception('Payload inválido.');

    $neg = new CUS29Negocio();
    $ok = $neg->registrarIncidencia($p['idEntrega'] ?? null, $p['tipoIncidencia'] ?? null, $p['descripcion'] ?? null);
    if (!$ok) throw new Exception('No se pudo registrar incidencia.');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
