<?php
require_once __DIR__ . '/../Controlador/Conexion.php';

class GestionNoEntregados {
    private $conn;

    public function __construct() {
        $this->conn = (new Conexion())->conecta(); // mysqli connection
    }

    // -----------------------
    // LISTADOS (simples)
    // -----------------------
    public function listarNoEntregados() {
        $sql = "SELECT c.ID_Consolidacion,
                       c.Id_OrdenPedido,
                       c.Fecha,
                       c.Estado,
                       c.Observaciones,
                       o.Id_Cliente,
                       d.NombreContactoSnap AS NombreCliente
                FROM t171Consolidacion_Entrega c
                LEFT JOIN t02OrdenPedido o ON c.Id_OrdenPedido = o.Id_OrdenPedido
                LEFT JOIN t71OrdenDirecEnvio d ON c.Id_OrdenPedido = d.Id_OrdenPedido
                WHERE c.Estado = 'No Entregado'";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) {
                $filas[] = $fila;
            }
        }
        return $filas;
    }

    public function listarGestionNoEntregados() {
        $sql = "SELECT g.Id_Gestion, g.Id_Consolidacion, g.Id_OrdenPedido, g.Id_Cliente,
                       g.Decision, g.Observaciones, g.FechaGestion, g.Estado
                FROM t172GestionNoEntregados g
                ORDER BY g.FechaGestion DESC";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) $filas[] = $fila;
        }
        return $filas;
    }

    public function listarPedidosPagados() {
        $sql = "SELECT Id_OrdenPedido, Id_Cliente, Fecha, Estado FROM t02OrdenPedido WHERE Estado = 'Pagada' ORDER BY Fecha DESC";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) $filas[] = $fila;
        }
        return $filas;
    }

    public function listarOSEEmitidas() {
        $sql = "SELECT Id_OSE AS Id_OrdenServicio, Id_OrdenPedido, Fecha, Estado FROM t59OrdenServicioEntrega WHERE Estado = 'Emitida' ORDER BY Id_OSE DESC";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) $filas[] = $fila;
        }
        return $filas;
    }

    public function listarIngresoAlmacen() {
        $sql = "SELECT Id_OrdenIngreso AS Id_OrdenIngreso, Id_OrdenPedido, FechaIngreso, Estado FROM t09OrdenIngresoAlmacen ORDER BY Id_OrdenIngreso DESC";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) $filas[] = $fila;
        }
        return $filas;
    }

    public function listarConsolidacion() {
        $sql = "SELECT * FROM t171Consolidacion_Entrega ORDER BY Fecha DESC";
        $res = $this->conn->query($sql);
        $filas = [];
        if ($res) {
            while ($fila = $res->fetch_assoc()) $filas[] = $fila;
        }
        return $filas;
    }

    // -----------------------
    // REGISTRO DE GESTIÓN (REPROGRAMACIÓN / DEVOLUCIÓN)
    // -----------------------
    /**
     * $data: array con idConsolidacion, idPedido, idCliente, observaciones
     * Retorna array asociativo con keys: success (bool), decision (string), message (string), redirect (opcional)
     */
    public function registrarGestion($data) {
        $this->conn->autocommit(false);
        try {
            $idConsolidacion = (int)$data['idConsolidacion'];
            $idPedido = (int)$data['idPedido'];
            $idCliente = (int)$data['idCliente'];
            $observaciones = isset($data['observaciones']) ? $data['observaciones'] : '';

            // 1) Obtener Observaciones desde t171Consolidacion_Entrega (campo Observaciones)
            $observacionT171 = '';
            $sqlObs = "SELECT Observaciones FROM t171Consolidacion_Entrega WHERE ID_Consolidacion = ?";
            $stmtObs = $this->conn->prepare($sqlObs);
            if ($stmtObs === false) throw new Exception("Prepare obs: " . $this->conn->error);
            $stmtObs->bind_param("i", $idConsolidacion);
            $stmtObs->execute();
            $stmtObs->bind_result($observacionT171);
            $stmtObs->fetch();
            $stmtObs->close();
            $observacionT171 = $observacionT171 ?: '';

            // 2) Determinar decisión por regla de negocio (motivos definitivos -> Devolución)
            $motivosDevolucion = ["Rechazo del pedido", "La dirección no existe", "Otros"];
            $decision = in_array($observacionT171, $motivosDevolucion) ? "Devolución" : "Reprogramación";

            // 3) Contar reprogramaciones previas para este pedido
            $sqlCount = "SELECT COUNT(*) FROM t172GestionNoEntregados WHERE Id_OrdenPedido = ? AND Decision = 'Reprogramación'";
            $stmtCount = $this->conn->prepare($sqlCount);
            if ($stmtCount === false) throw new Exception("Prepare count: " . $this->conn->error);
            $stmtCount->bind_param("i", $idPedido);
            $stmtCount->execute();
            $stmtCount->bind_result($numRepro);
            $stmtCount->fetch();
            $stmtCount->close();
            $numRepro = (int)$numRepro;

            // Si ya tiene >= 2 reprogramaciones previas -> forzar Devolución
            if ($numRepro >= 2) {
                $decision = "Devolución";
            }

            // 4) Insertar en t172 (siempre insertamos)
            $sqlInsert = "INSERT INTO t172GestionNoEntregados 
                          (Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Decision, Observaciones, FechaGestion, Estado)
                          VALUES (?, ?, ?, ?, ?, NOW(), 'Registrado')";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            if ($stmtInsert === false) throw new Exception("Prepare insert: " . $this->conn->error);
            $stmtInsert->bind_param("iiiss", $idConsolidacion, $idPedido, $idCliente, $decision, $observaciones);
            if (!$stmtInsert->execute()) {
                $err = $stmtInsert->error;
                $stmtInsert->close();
                throw new Exception("Execute insert: " . $err);
            }
            // Capturamos id del registro nuevo para poder actualizar su Estado si procesamos
            $idGestionInsertada = $this->conn->insert_id;
            $stmtInsert->close();

            // 5) Si es Reprogramación -> actualizar t02, t59 y marcar registro t172 como 'Procesado'
            if ($decision === "Reprogramación") {
                $fechaNow = date('Y-m-d H:i:s');

                // Update t02OrdenPedido: Fecha = now, Estado = 'Pagada'
                $sqlUpdPedido = "UPDATE t02OrdenPedido SET Fecha = ?, Estado = 'Pagada' WHERE Id_OrdenPedido = ?";
                $stmtUpdPedido = $this->conn->prepare($sqlUpdPedido);
                if ($stmtUpdPedido === false) throw new Exception("Prepare upd pedido: " . $this->conn->error);
                $stmtUpdPedido->bind_param("si", $fechaNow, $idPedido);
                if (!$stmtUpdPedido->execute()) {
                    $err = $stmtUpdPedido->error;
                    $stmtUpdPedido->close();
                    throw new Exception("Execute upd pedido: " . $err);
                }
                $stmtUpdPedido->close();

                // Update t59OrdenServicioEntrega: Fecha = now, Estado = 'Emitida'
                // (si la fila no existe, la query no rompe, simplemente no actualiza)
                $sqlUpdOSE = "UPDATE t59OrdenServicioEntrega SET Fecha = ?, Estado = 'Emitida' WHERE Id_OrdenPedido = ?";
                $stmtUpdOSE = $this->conn->prepare($sqlUpdOSE);
                if ($stmtUpdOSE === false) throw new Exception("Prepare upd OSE: " . $this->conn->error);
                $stmtUpdOSE->bind_param("si", $fechaNow, $idPedido);
                if (!$stmtUpdOSE->execute()) {
                    $err = $stmtUpdOSE->error;
                    $stmtUpdOSE->close();
                    throw new Exception("Execute upd OSE: " . $err);
                }
                $stmtUpdOSE->close();

                // (Opcional) Crear t09OrdenIngresoAlmacen si tu flujo lo pide — NO lo hacemos aquí porque
                // reprogramación no siempre implica ingreso a almacén. Si quieres hacerlo, lo añadimos.

                // Finalmente actualizar el registro recien insertado en t172 a 'Procesado'
                $sqlUpd172 = "UPDATE t172GestionNoEntregados SET Estado = 'Procesado' WHERE Id_Gestion = ?";
                $stmtUpd172 = $this->conn->prepare($sqlUpd172);
                if ($stmtUpd172 === false) throw new Exception("Prepare upd 172: " . $this->conn->error);
                $stmtUpd172->bind_param("i", $idGestionInsertada);
                if (!$stmtUpd172->execute()) {
                    $err = $stmtUpd172->error;
                    $stmtUpd172->close();
                    throw new Exception("Execute upd 172: " . $err);
                }
                $stmtUpd172->close();

                // 6) Actualizar t171Consolidacion_Entrega a 'Reprogramado'
                $sqlUpdConsol = "UPDATE t171Consolidacion_Entrega SET Estado = 'Reprogramado' WHERE ID_Consolidacion = ?";
                $stmtUpdConsol = $this->conn->prepare($sqlUpdConsol);
                if ($stmtUpdConsol === false) throw new Exception("Prepare upd consolidacion: " . $this->conn->error);
                $stmtUpdConsol->bind_param("i", $idConsolidacion);
                if (!$stmtUpdConsol->execute()) {
                    $err = $stmtUpdConsol->error;
                    $stmtUpdConsol->close();
                    throw new Exception("Execute upd consolidacion: " . $err);
                }
                $stmtUpdConsol->close();

                $this->conn->commit();
                return ["success" => true, "decision" => $decision, "message" => "Reprogramación registrada y procesada"];
            }

            // 7) Si es Devolución -> ya insertamos el registro en t172 con Decision='Devolución'
            // actualizamos t171Consolidacion_Entrega a 'Devuelto' (según lo que hablamos, si quieres que
            // eso lo haga el CUS de Devolución en vez de aquí puedes comentar esta parte)
            $sqlUpdConsolDev = "UPDATE t171Consolidacion_Entrega SET Estado = 'Devuelto' WHERE ID_Consolidacion = ?";
            $stmtUpdConsolDev = $this->conn->prepare($sqlUpdConsolDev);
            if ($stmtUpdConsolDev === false) throw new Exception("Prepare upd consolidacion dev: " . $this->conn->error);
            $stmtUpdConsolDev->bind_param("i", $idConsolidacion);
            if (!$stmtUpdConsolDev->execute()) {
                $err = $stmtUpdConsolDev->error;
                $stmtUpdConsolDev->close();
                throw new Exception("Execute upd consolidacion dev: " . $err);
            }
            $stmtUpdConsolDev->close();

            $this->conn->commit();
            return ["success" => true, "decision" => $decision, "redirect" => "CUS_Devolucion", "message" => "Pedido registrado (Derivado a Devolución)"];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("GestionNoEntregados::registrarGestion error: " . $e->getMessage());
            return ["success" => false, "message" => "Error interno: " . $e->getMessage()];
        }
    }
}
?>
