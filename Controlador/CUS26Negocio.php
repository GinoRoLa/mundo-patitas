<?php
require_once __DIR__ . '/../Modelo/GestionNoEntregado.php';

class CUS26Negocio {
    private $model;

    public function __construct(){
        $this->model = new GestionNoEntregados();
    }

    // Listados (mantengo las firmas que ya tenías)
    public function listarNoEntregados(){
        return $this->model->listarNoEntregados();
    }

    public function listarGestionNoEntregados(){
        return $this->model->listarGestionNoEntregados();
    }

    public function listarPedidosPagados(){
        return $this->model->listarPedidosPagados();
    }

    public function listarOSEEmitidas(){
        return $this->model->listarOSEEmitidas();
    }

    public function listarIngresoAlmacen(){
        return $this->model->listarIngresoAlmacen();
    }

    public function listarConsolidacion(){
        return $this->model->listarConsolidacion();
    }

    /**
     * registrarReprogramacion mantiene la misma firma que usabas.
     * Internamente arma $data y llama a registrarGestion() del modelo.
     */
    public function registrarReprogramacion($idConsolidacion, $idPedido, $idCliente, $nombreCliente, $observaciones, $fecha, $estado) {
        include_once 'Conexion.php';
        $conn = new Conexion();
        $conexion = $conn->Conecta();

        try {
            // 1️⃣ Ver cuántas veces ya se reprogramó el pedido
            $consulta = "SELECT COUNT(*) AS veces 
                        FROM t172GestionNoEntregados 
                        WHERE Id_OrdenPedido = ? AND Decision = 'Reprogramación'";
            $stmt = $conexion->prepare($consulta);
            $stmt->bind_param("i", $idPedido);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();
            $veces = $fila['veces'] ?? 0;
            $stmt->close();

            if ($veces >= 2) {
                // Si ya se reprogramó 2 veces, no se permite más
                return false;
            }

            // 2️⃣ Insertar registro en t172GestionNoEntregados
            $sql = "INSERT INTO t172GestionNoEntregados 
                    (Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Decision, Observaciones, FechaGestion, Estado)
                    VALUES (?, ?, ?, 'Reprogramación', ?, NOW(), 'Registrado')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iiis", $idConsolidacion, $idPedido, $idCliente, $observaciones);
            $stmt->execute();
            $stmt->close();

            // 3️⃣ Actualizar t02OrdenPedido
            $sql = "UPDATE t02OrdenPedido 
                    SET Fecha = NOW(), Estado = 'Pagado' 
                    WHERE Id_OrdenPedido = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $idPedido);
            $stmt->execute();
            $stmt->close();

            // 4️⃣ Actualizar t59OrdenServicioEntrega
            $sql = "UPDATE t59OrdenServicioEntrega 
                    SET FecCreacion = NOW(), Estado = 'Emitido' 
                    WHERE Id_OrdenPedido = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $idPedido);
            $stmt->execute();
            $stmt->close();

            // 5 Actualizar t171Consolidacion_Entrega 
            $sql = "UPDATE t171Consolidacion_Entrega  
                    SET Estado = 'Reprogramado' 
                    WHERE ID_Consolidacion = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $idConsolidacion);
            $stmt->execute();
            $stmt->close();

            return true;

        } catch (Exception $e) {
            error_log("❌ Error en registrarReprogramacion: " . $e->getMessage());
            return false;
        }
    }

    public function registrarDevolucion($idConsolidacion, $idPedido, $idCliente, $observaciones) {
        include_once 'Conexion.php';
        $conn = new Conexion();
        $conexion = $conn->Conecta();

        try {
            // 1️⃣ Insertar registro en t172GestionNoEntregados
            $sql = "INSERT INTO t172GestionNoEntregados 
                    (Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Decision, Observaciones, FechaGestion, Estado)
                    VALUES (?, ?, ?, 'Devolución', ?, NOW(), 'Registrado')";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) throw new Exception("Error al preparar INSERT: " . $conexion->error);
            $stmt->bind_param("iiis", $idConsolidacion, $idPedido, $idCliente, $observaciones);
            $stmt->execute();

            // 2️⃣ Actualizar estado en t171Consolidacion_Entrega
            $sql = "UPDATE t171Consolidacion_Entrega 
                    SET Estado = 'Devuelto' 
                    WHERE ID_Consolidacion = ?";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) throw new Exception("Error al preparar UPDATE: " . $conexion->error);
            $stmt->bind_param("i", $idConsolidacion);
            $stmt->execute();

            return true;

        } catch (Exception $e) {
            error_log("❌ Error en registrarDevolucion: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerPedidosNoEntregados() {
        include_once 'Conexion.php';
        $conn = new Conexion();
        $conexion = $conn->Conecta();
        $sql = "
        SELECT 
            t171Consolidacion_Entrega.ID_Consolidacion,
            t02OrdenPedido.Id_OrdenPedido,
            t02OrdenPedido.Id_Cliente,
            CONCAT(t20Cliente.des_nombreCliente, ' ', t20Cliente.des_apepatCliente) AS NombreCliente,
            t171Consolidacion_Entrega.Observaciones,
            t171Consolidacion_Entrega.Fecha AS Fecha,
            t171Consolidacion_Entrega.Estado,
            t59OrdenServicioEntrega.Id_OSE,
            t59OrdenServicioEntrega.Estado AS EstadoOSE,
            t59OrdenServicioEntrega.FecCreacion AS FecCreacionOSE
        FROM t171Consolidacion_Entrega
        JOIN t02OrdenPedido ON t171Consolidacion_Entrega.Id_OrdenPedido = t02OrdenPedido.Id_OrdenPedido
        JOIN t20Cliente ON t02OrdenPedido.Id_Cliente = t20Cliente.Id_Cliente
        LEFT JOIN t59OrdenServicioEntrega ON t59OrdenServicioEntrega.Id_OrdenPedido = t02OrdenPedido.Id_OrdenPedido
        WHERE t171Consolidacion_Entrega.Estado = 'No Entregado'
        ";
        // Ejecutas la consulta y devuelves los resultados

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            die("Error en la consulta SQL: " . $conexion->error);
        }

        $pedidos = [];
        if ($resultado->num_rows > 0) {
            while($row = $resultado->fetch_assoc()) {
                $pedidos[] = $row;
            }
        }
        return $pedidos;


    }

}
?>
