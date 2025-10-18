<?php
require_once __DIR__ . '/../Controlador/Conexion.php';

class GestionNoEntregados {
    private $cn;

    public function __construct() {
        $this->cn = (new Conexion())->conecta(); // mysqli
        if (!$this->cn) {
            die("Error de conexión: " . mysqli_connect_error());
        }
    }

    public function getConexion() {
        return $this->cn;
    }


    // Listar consolidaciones no entregadas
    public function listarNoEntregadas(): array {
        $sql = "SELECT c.ID_Consolidacion,
                     c.Id_OrdenPedido,
                     c.Fecha,
                     c.Estado,
                     c.Observaciones,
                     o.Id_Cliente,
                     d.NombreContactoSnap AS NombreCliente
              FROM t171Consolidacion_Entrega c
              INNER JOIN t02OrdenPedido o ON c.Id_OrdenPedido = o.Id_OrdenPedido
              INNER JOIN t71OrdenDirecEnvio d ON c.Id_OrdenPedido = d.Id_OrdenPedido
              WHERE c.Estado = 'No Entregado'";

        $resultado = $this->cn->query($sql);
        if(!$resultado) {
            echo "Error en SQL: " . $this->cn->error;
            return [];
        }
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Registrar reprogramación: cambia la fecha de la orden de pedido a hoy y estado a Pagado
    public function registrarReprogramacion(array $data): bool {
        // Validar que todos los datos sean numéricos
        if (!isset($data['ID_Consolidacion'], $data['Id_OrdenPedido'], $data['Id_Cliente']) ||
            !is_numeric($data['ID_Consolidacion']) ||
            !is_numeric($data['Id_OrdenPedido']) ||
            !is_numeric($data['Id_Cliente'])) {
            echo "Error: Datos inválidos.";
            return false;
        }

        $idConsolidacion = (int)$data['ID_Consolidacion'];
        $idPedido = (int)$data['Id_OrdenPedido'];
        $idCliente = (int)$data['Id_Cliente'];
        $fechaHoy = date('Y-m-d');
        $estadoRep = "Reprogramacion";

        // 1. Actualizar fecha de orden de pedido a hoy y estado a "Pagado"
        $hoy = date('YYYY-MM-DD');
        $sql1 = "UPDATE t02OrdenPedido SET Fecha = ?, Estado = 'Pagado' WHERE Id_OrdenPedido = ?";
        $stmt1 = $this->cn->prepare($sql1);
        $stmt1->bind_param("si", $fechaHoy, $idPedido);
        if(!$stmt1->execute()) {
            die("Error al actualizar t02OrdenPedido: " . $stmt1->error);
            return false;
        }

        // 2. Actualizar estado de orden de servicio de entrega asociada a "Emitido"
        $sql2 = "UPDATE t59OrdenServicioEntrega SET Estado = 'Emitido' WHERE Id_OrdenPedido = ?";
        $stmt2 = $this->cn->prepare($sql2);
        $stmt2->bind_param("i", $idPedido);
        if(!$stmt2->execute()) {
            die("Error al actualizar t59OrdenServicioEntrega: " . $stmt2->error);
            return false;
        }

        // 3. Registrar en t172GestionNoEntregados

        $sql3 = "INSERT INTO t172GestionNoEntregados 
                    (Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Estado, FechaGestion) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt3 = $this->cn->prepare($sql3);
        $stmt3->bind_param("iiiss", $idConsolidacion, $idPedido, $idCliente, $estadoRep, $fechaHoy);
        if(!$stmt3->execute()) {
            echo "Error insertando en t172GestionNoEntregados: " . $stmt3->error;
            return false;
        }

        return true;

        
    }
}

