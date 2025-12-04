<?php
// Modelo/EntregaModel.php
require_once __DIR__ . '/../Controlador/Conexion.php';

class EntregaModel {
    private $conn;
    
    public function __construct() {
        date_default_timezone_set('America/Lima'); // ← AGREGAR ESTO
        $db = new Conexion();
        $this->conn = $db->conecta(); // MySQLi connection
    }

    public function registrarEntrega($idPedido, $idDetalleAsignacion, $idNotaCaja, $idTrabajador, $fechaEntrega, $horaEntrega, $estadoEntrega, $observacion) {
        $sql = "INSERT INTO t430EntregaPedidoDelivery
                (Id_OrdenPedido, Id_DetalleAsignacion, IDNotaCaja, Id_Trabajador, 
                 FechaEntrega, HoraEntrega, EstadoEntrega, Observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare entrega: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("iiiissss", 
            $idPedido, 
            $idDetalleAsignacion, 
            $idNotaCaja, 
            $idTrabajador, 
            $fechaEntrega, 
            $horaEntrega, 
            $estadoEntrega, 
            $observacion
        );
        
        if (!$stmt->execute()) {
            error_log("Error execute entrega: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function obtenerPorId($idEntrega) {
        $sql = "SELECT * FROM t430EntregaPedidoDelivery WHERE Id_EntregaPedidoDelivery = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $idEntrega);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc() ?: null;
        $stmt->close();
        return $row;
    }

    /**
     * Obtener información del pedido para entrega
     * Retorna: Id_DetalleAsignacion, IDNotaCaja, IDRepartidor, Total (monto esperado), 
     *          TotalContraEntrega, VueltoTotal
     */
    public function obtenerInfoPedidoParaEntrega($idPedido) {
        $sql = "SELECT 
                    t401.Id_DetalleAsignacion,
                    t40.Id_OrdenAsignacion,
                    t28.IDNotaCaja,
                    t28.IDRepartidor,
                    t28.TotalContraEntrega,
                    t28.VueltoTotal,
                    t02.Total AS MontoEsperado
                FROM t02OrdenPedido t02
                    INNER JOIN t59OrdenServicioEntrega t59
                        ON t02.Id_OrdenPedido = t59.Id_OrdenPedido
                    INNER JOIN t401DetalleAsignacionReparto t401
                        ON t401.Id_OSE = t59.Id_OSE
                    INNER JOIN t40OrdenAsignacionReparto t40
                        ON t40.Id_OrdenAsignacion = t401.Id_OrdenAsignacion
                    LEFT JOIN t28Nota_caja t28
                        ON t28.IDAsignacionReparto = t40.Id_OrdenAsignacion
                WHERE t02.Id_OrdenPedido = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare info pedido: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            return null;
        }
        
        return [
            "Id_DetalleAsignacion" => $row["Id_DetalleAsignacion"],
            "Id_OrdenAsignacion"   => $row["Id_OrdenAsignacion"],
            "IDNotaCaja"           => $row["IDNotaCaja"],
            "IDRepartidor"         => $row["IDRepartidor"],
            "TotalContraEntrega"   => $row["TotalContraEntrega"],
            "VueltoTotal"          => $row["VueltoTotal"],
            "Total"                => $row["MontoEsperado"]
        ];
    }
}
?>