<?php
// Modelo/ControlVueltoModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class ControlVueltoModel {
    private $conn;
    
    public function __construct() {
        date_default_timezone_set('America/Lima');
        $db = new Conexion();
        $this->conn = $db->conecta();
    }
    
    /**
     * Registrar movimiento de vuelto
     * @param int $idNotaCaja
     * @param string $tipoMovimiento ENTREGA_INICIAL | USO_VUELTO | RECEPCION_COBRO
     * @param float $montoMovimiento
     * @param float $saldoActual
     * @param int|null $idEntrega
     * @param string $descripcion
     * @return int|false ID del registro o false
     */
    public function registrarMovimiento($idNotaCaja, $tipoMovimiento, $montoMovimiento, $saldoActual, $idEntrega = null, $descripcion = '') {
        $sql = "INSERT INTO t435ControlVueltoRepartidor 
                (IDNotaCaja, FechaHoraMovimiento, TipoMovimiento, MontoMovimiento, 
                 SaldoActual, Id_EntregaPedidoDelivery, Descripcion)
                VALUES (?, NOW(), ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare control vuelto: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("isddis", 
            $idNotaCaja, 
            $tipoMovimiento, 
            $montoMovimiento, 
            $saldoActual, 
            $idEntrega, 
            $descripcion
        );
        
        if (!$stmt->execute()) {
            error_log("Error execute control vuelto: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
    
    /**
     * Obtener saldo actual del repartidor
     * @param int $idNotaCaja
     * @return float|null Saldo actual o null si no existe
     */
    public function obtenerSaldoActual($idNotaCaja) {
        $sql = "SELECT SaldoActual 
                FROM t435ControlVueltoRepartidor 
                WHERE IDNotaCaja = ?
                ORDER BY Id_ControlVuelto DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        
        $stmt->bind_param("i", $idNotaCaja);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        return $row ? (float)$row['SaldoActual'] : null;
    }
    
    /**
     * Obtener historial de movimientos de una nota de caja
     * @param int $idNotaCaja
     * @return array
     */
    public function obtenerHistorialMovimientos($idNotaCaja) {
        $sql = "SELECT * 
                FROM t435ControlVueltoRepartidor 
                WHERE IDNotaCaja = ?
                ORDER BY FechaHoraMovimiento ASC, Id_ControlVuelto ASC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        
        $stmt->bind_param("i", $idNotaCaja);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $movimientos = [];
        while ($row = $res->fetch_assoc()) {
            $movimientos[] = $row;
        }
        
        $stmt->close();
        return $movimientos;
    }
    
    /**
     * Inicializar control de vuelto (primer registro)
     * Se hace cuando el repartidor recibe el dinero de caja
     * @param int $idNotaCaja
     * @param float $montoInicial
     * @return int|false
     */
    public function inicializarControlVuelto($idNotaCaja, $montoInicial) {
        return $this->registrarMovimiento(
            $idNotaCaja,
            'ENTREGA_INICIAL',
            $montoInicial,
            $montoInicial,
            null,
            'Entrega inicial de vuelto por caja'
        );
    }
    
    /**
     * Verificar si ya existe un control de vuelto para esta nota de caja
     * @param int $idNotaCaja
     * @return bool
     */
    public function existeControlVuelto($idNotaCaja) {
        $sql = "SELECT COUNT(*) as total FROM t435ControlVueltoRepartidor WHERE IDNotaCaja = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idNotaCaja);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        return $row['total'] > 0;
    }
}
?>