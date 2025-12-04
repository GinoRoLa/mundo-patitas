<?php
// Vista/Ajax/CUS29/validarVueltoDisponible.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/Conexion.php';
    require_once __DIR__ . '/../../../Controlador/CUS29Negocio.php';
    
    $idNotaCaja = isset($_GET['idNotaCaja']) ? intval($_GET['idNotaCaja']) : 0;
    
    if (!$idNotaCaja) {
        throw new Exception('ID de nota de caja inválido.');
    }
    
    $negocio = new CUS29Negocio();
    
    // Obtener VueltoTotal de la nota de caja (lo que le dieron inicialmente)
    $db = new Conexion();
    $conn = $db->conecta();
    
    $sqlNota = "SELECT VueltoTotal FROM t28Nota_caja WHERE IDNotaCaja = ?";
    $stmtNota = $conn->prepare($sqlNota);
    if (!$stmtNota) {
        throw new Exception('Error al preparar consulta: ' . $conn->error);
    }
    
    $stmtNota->bind_param("i", $idNotaCaja);
    $stmtNota->execute();
    $resNota = $stmtNota->get_result();
    $nota = $resNota->fetch_assoc();
    $stmtNota->close();
    
    if (!$nota) {
        throw new Exception('Nota de caja no encontrada.');
    }
    
    $vueltoTotal = floatval($nota['VueltoTotal']);
    
    // Obtener saldo ACTUAL del control de vuelto (incluye cobros)
    $saldoActual = $negocio->obtenerSaldoVueltoActual($idNotaCaja);
    
    // Calcular cuánto vuelto ya se usó
    $vueltoUsado = $vueltoTotal - $saldoActual;
    if ($vueltoUsado < 0) $vueltoUsado = 0; // No puede ser negativo
    
    echo json_encode([
        'success' => true,
        'vueltoTotal' => $vueltoTotal,
        'vueltoUsado' => $vueltoUsado,
        'vueltoDisponible' => $saldoActual
    ]);
    
} catch (Exception $e) {
    error_log("Error en validarVueltoDisponible.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>