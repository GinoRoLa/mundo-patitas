<?php
// Modelo/DenominacionPagoModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class DenominacionPagoModel {
    private $conn;
    public function __construct() {
        $db = new Conexion();
        $this->conn = $db->Conecta();
    }

    public function registrarDenominacionPago($idPago, $tipo, $denominacion, $cantidad) {
        $sql = "INSERT INTO t433PagoDenominacion (Id_PagoEntrega, Tipo, Denominacion, Cantidad)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("isdi", $idPago, $tipo, $denominacion, $cantidad);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
