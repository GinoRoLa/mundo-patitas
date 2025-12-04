<?php
// Modelo/IncidenciaModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class IncidenciaModel {
    private $conn;
    public function __construct() {
        $db = new Conexion();
        $this->conn = $db->Conecta();
    }

    public function registrarIncidencia($idEntrega, $tipoIncidencia, $descripcion) {
        $sql = "INSERT INTO t432IncidenciaPedidoDelivery (Id_EntregaPedidoDelivery, TipoIncidencia, Descripcion)
                VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("iss", $idEntrega, $tipoIncidencia, $descripcion);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
