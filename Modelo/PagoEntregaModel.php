<?php
// Modelo/PagoEntregaModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class PagoEntregaModel {
    private $conn;
    public function __construct() {
        $db = new Conexion();
        $this->conn = $db->Conecta();
    }

    public function registrarPago($idEntrega, $montoEsperado, $montoRecibido, $montoVuelto) {
        $sql = "INSERT INTO t431PagoEntrega (Id_EntregaPedidoDelivery, MontoEsperado, MontoRecibido, MontoVuelto)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("iddd", $idEntrega, $montoEsperado, $montoRecibido, $montoVuelto);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function obtenerPorEntrega($idEntrega) {
        $sql = "SELECT * FROM t431PagoEntrega WHERE Id_EntregaPedidoDelivery = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idEntrega);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }
}
