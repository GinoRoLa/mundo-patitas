<?php
// Modelo/DenominacionVueltoModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class DenominacionVueltoModel {
    private $conn;
    public function __construct() {
        $db = new Conexion();
        $this->conn = $db->Conecta();
    }

    public function registrarDenominacionVuelto($idPago, $tipo, $denominacion, $cantidad) {
        $sql = "INSERT INTO t434VueltoDenominacion (Id_PagoEntrega, Tipo, Denominacion, Cantidad)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("isdi", $idPago, $tipo, $denominacion, $cantidad);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
