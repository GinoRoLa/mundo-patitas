<?php
require_once __DIR__ . '/../Controlador/Conexion.php';

class RequerimientoModel {
    private $conn;
    public function __construct(){
        $this->conn = (new Conexion())->conecta();
    }


    /**
     * Obtener financiamiento disponible para el periodo actual
     * Retorna: Monto del periodo + Saldo periodo anterior
     */
    public function obtenerFinanciamientoPeriodo($mes = null) {
        if(!$mes) $mes = date('Y-m'); // Formato YYYY-MM

        // === 1) Buscar partida actual ===
        $sqlActual = "
            SELECT 
                p.Id_PartidaPeriodo,
                p.CodigoPartida,
                p.Descripcion,
                p.Mes,
                p.MontoPeriodo
            FROM t406PartidaPeriodo p
            WHERE p.Mes = ? AND p.Estado = 'Activo'
            ORDER BY p.Id_PartidaPeriodo DESC
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sqlActual);
        $stmt->bind_param("s", $mes);
        $stmt->execute();
        $res = $stmt->get_result();
        $partidaActual = $res->fetch_assoc();

        if(!$partidaActual) {
            throw new Exception("No se encontró una partida activa para el periodo $mes");
        }

        // === 2) Obtener mes anterior ===
        $fecha = DateTime::createFromFormat('Y-m', $mes);
        if(!$fecha) throw new Exception("Formato de mes inválido: $mes");
        $fecha->modify('-1 month');
        $mesAnterior = $fecha->format('Y-m');

        // === 3) Calcular saldo del mes anterior desde t410ConsumoPartida ===
        $sqlSaldoAnt = "
            SELECT 
                p.MontoPeriodo,
                IFNULL(SUM(c.MontoConsumido),0) AS TotalConsumido,
                (p.MontoPeriodo - IFNULL(SUM(c.MontoConsumido),0)) AS SaldoRestante
            FROM t406PartidaPeriodo p
            LEFT JOIN t410ConsumoPartida c ON c.Id_PartidaPeriodo = p.Id_PartidaPeriodo
            WHERE p.Mes = ? AND p.Estado = 'Activo'
            GROUP BY p.Id_PartidaPeriodo
            ORDER BY p.Id_PartidaPeriodo DESC
            LIMIT 1
        ";
        $stmtAnt = $this->conn->prepare($sqlSaldoAnt);
        $stmtAnt->bind_param("s", $mesAnterior);
        $stmtAnt->execute();
        $resAnt = $stmtAnt->get_result();
        $saldoAnterior = 0.00;

        if($rowAnt = $resAnt->fetch_assoc()) {
            $saldoAnterior = max(0, (float)$rowAnt['SaldoRestante']);
        }

        // === 4) Calcular financiamiento total ===
        $montoPeriodo = (float)$partidaActual['MontoPeriodo'];
        $financiamientoTotal = $montoPeriodo + $saldoAnterior;

        // === 5) Retornar al frontend ===
        return [
            'Id_PartidaPeriodo' => (int)$partidaActual['Id_PartidaPeriodo'],
            'CodigoPartida' => $partidaActual['CodigoPartida'],
            'Descripcion' => $partidaActual['Descripcion'],
            'Mes' => $partidaActual['Mes'],
            'MontoPeriodo' => number_format($montoPeriodo, 2, '.', ''),
            'SaldoAnterior' => number_format($saldoAnterior, 2, '.', ''),
            'FinanciamientoTotal' => number_format($financiamientoTotal, 2, '.', '')
        ];
    }



    // devuelve partidas activas
    public function listarPartidasActivas() {
        $sql = "SELECT Id_PartidaPeriodo, CodigoPartida, Descripcion, Mes, MontoPeriodo, MontoConsumido, Estado
                FROM t406PartidaPeriodo
                WHERE Estado = 'Activo'
                ORDER BY Id_PartidaPeriodo DESC";
        $res = $this->conn->query($sql);
        $rows = [];
        if($res){
            while($r = $res->fetch_assoc()) $rows[] = $r;
        }
        return $rows;
    }

    // obtener saldo disponible de una partida
    public function obtenerSaldoDisponible($idPartida) {
        // Obtener monto total financiado del periodo
        $sql = "SELECT MontoPeriodo FROM t406PartidaPeriodo WHERE Id_PartidaPeriodo = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $idPartida);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$res) return false;

        $montoPeriodo = (float)$res['MontoPeriodo'];

        // Obtener consumo acumulado hasta ahora
        $sql2 = "SELECT IFNULL(SUM(MontoConsumido), 0) AS TotalConsumido 
                FROM t410ConsumoPartida 
                WHERE Id_PartidaPeriodo = ?";
        $stmt2 = $this->conn->prepare($sql2);
        if (!$stmt2) return false;
        $stmt2->bind_param("i", $idPartida);
        $stmt2->execute();
        $res2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $totalConsumido = (float)$res2['TotalConsumido'];
        $saldo = max(0, $montoPeriodo - $totalConsumido);

        return [
            'MontoPeriodo' => $montoPeriodo,
            'MontoConsumido' => $totalConsumido,
            'Saldo' => $saldo
        ];
    }


    // listar requerimientos pendientes
    public function listarSolicitudesPendientes(){
        $mesActual = date('Y-m');
        $sql = "SELECT Id_Requerimiento, FechaRequerimiento, Total, PrecioPromedio, Estado
                FROM t14RequerimientoCompra
                WHERE DATE_FORMAT(FechaRequerimiento, '%Y-%m') = '$mesActual'
                AND (Estado IN ('Pendiente','Generado') OR Estado IS NULL)
                ORDER BY FechaRequerimiento DESC
                LIMIT 1";
        $res = $this->conn->query($sql);
        $rows = [];
        if($res){
            while($r = $res->fetch_assoc()) $rows[] = $r;
        }
        return $rows;
    }


    // obtener detalle (productos) de una solicitud
    public function obtenerDetalleSolicitud($id)
    {
        $sql = "SELECT 
                    d.Id_Producto,
                    d.Cantidad,
                    d.PrecioPromedio,
                    (d.Cantidad * d.PrecioPromedio) AS Total
                FROM t15DetalleRequerimientoCompra d
                WHERE d.Id_Requerimiento = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error en la preparación de la consulta: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }


    /*
     * Aplica evaluación sobre la solicitud con el criterio compuesto:
     * - ordena por Precio asc (primario) y Cantidad desc (secundario)
     * - luego asigna aprobaciones por proporcionalidad hasta cubrir monto disponible
     */
    public function evaluarYRegistrar($idReq, $idPartida, $criterioLabel = 'Precio+Rotacion+Proporcionalidad') {
        $estadoPartida = $this->obtenerEstadoPartida($idPartida);
        if ($estadoPartida === 'Cerrado') {
            throw new Exception("La partida ya está cerrada y no puede evaluarse nuevamente.");
        }

        
        $this->conn->autocommit(false);
        try {
            // 1) Obtener detalle de la solicitud
            $detalle = $this->obtenerDetalleSolicitud($idReq);
            if (empty($detalle)) throw new Exception("No hay detalle para la solicitud.");

            // 2) Calcular monto total solicitado
            $montoSolicitado = 0.0;
            foreach ($detalle as $r) {
                $linea = (float)$r['Cantidad'] * (float)$r['PrecioPromedio'];
                $montoSolicitado += $linea;
            }

            // 3) Obtener saldo disponible actual
            $saldoInfo = $this->obtenerSaldoDisponible($idPartida);
            if(!$saldoInfo) throw new Exception("Partida no encontrada.");
            $montoPeriodo = (float)$saldoInfo['MontoPeriodo'];

            // 4) Aplicar los 3 criterios: Precio asc, Cantidad desc, Proporcionalidad
            // (Proporcionalidad: se asigna de forma equitativa mientras haya saldo)
            usort($detalle, function ($a, $b) {
                if ($a['PrecioPromedio'] == $b['PrecioPromedio']) {
                    return $b['Cantidad'] <=> $a['Cantidad']; // cantidad desc
                }
                return $a['PrecioPromedio'] <=> $b['PrecioPromedio']; // precio asc
            });

            // 5) Evaluar productos según saldo (ajustado con saldo anterior y mejor cálculo)
            $consumido = 0.0;
            $detalleEvaluado = [];

            // Obtener financiamiento total = monto del mes + saldo anterior
            $saldoAnterior = $this->obtenerSaldoAnteriorPorPartida($idPartida);
            $totalDisponible = $montoPeriodo + $saldoAnterior;
            $saldoDisponible = $totalDisponible;


            // Calcular peso proporcional inicial (para criterio 3)
            $totalCant = array_sum(array_column($detalle, 'Cantidad'));

            foreach ($detalle as $it) {
                $precio = (float)$it['PrecioPromedio'];
                $cant = (int)$it['Cantidad'];
                $totalLinea = $precio * $cant;

                if ($saldoDisponible <= 0) {
                    $aprobadoQty = 0;
                    $montoAsignado = 0.0;
                    $estadoProd = 'Rechazado';
                } elseif ($totalLinea <= $saldoDisponible) {
                    // puede cubrir toda la cantidad
                    $aprobadoQty = $cant;
                    $montoAsignado = $totalLinea;
                    $estadoProd = 'Aprobado';
                    $saldoDisponible -= $montoAsignado;
                    $consumido += $montoAsignado;
                } else {
                    // aplicar proporcionalidad (criterio 3)
                    $proporcion = ($totalCant > 0) ? ($cant / $totalCant) : 0;
                    $montoAsignado = $saldoDisponible * $proporcion;

                    // calcular cantidad aprobada
                    $aprobadoQty = (int) floor($montoAsignado / $precio);
                    if ($aprobadoQty > $cant) $aprobadoQty = $cant;

                    // Si aún hay saldo y se redondeó hacia abajo, ajustar ligeramente
                    if ($aprobadoQty == 0 && $saldoDisponible > $precio) {
                        $aprobadoQty = 1;
                    }

                    $montoAsignado = $aprobadoQty * $precio;

                    // Evitar pasarse del saldo disponible
                    if ($montoAsignado > $saldoDisponible) {
                        $montoAsignado = $saldoDisponible;
                        $aprobadoQty = (int) floor($montoAsignado / $precio);
                    }

                    $saldoDisponible -= $montoAsignado;
                    $consumido += $montoAsignado;

                    $estadoProd = ($aprobadoQty == 0)
                        ? 'Rechazado'
                        : (($aprobadoQty < $cant) ? 'Parcial' : 'Aprobado');
                }

                $detalleEvaluado[] = [
                    'Id_Producto' => $it['Id_Producto'],
                    'CantidadSolicitada' => $cant,
                    'CantidadAprobada' => $aprobadoQty,
                    'Precio' => $precio,
                    'MontoAsignado' => round($montoAsignado, 2),
                    'EstadoProducto' => $estadoProd
                ];
            }

            // saldo después del consumo
            $saldoDespues = max(0,round($totalDisponible - $consumido, 2));
            $montoAprobadoTotal = round($consumido, 2);


            // 6) Insertar cabecera t407RequerimientoEvaluado
            $montoAprobadoTotal = $consumido;
            $fechaEval = date('Y-m-d H:i:s');
            $estadoCab = ($montoAprobadoTotal == 0)
                ? 'Rechazado'
                : (($montoAprobadoTotal < $montoSolicitado) ? 'Parcialmente Aprobado' : 'Aprobado');

            $sqlC = "INSERT INTO t407RequerimientoEvaluado
                    (Id_Requerimiento, Id_PartidaPeriodo, FechaEvaluacion, CriterioEvaluacion, 
                    MontoSolicitado, MontoAprobado, SaldoRestantePeriodo, Observaciones, Estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sqlC);
            if (!$stmt) throw new Exception("Error preparar INSERT t407: " . $this->conn->error);

            
            $obs = "Evaluación automática con 3 criterios";

            $stmt->bind_param(
                "iissdddss",
                $idReq,
                $idPartida,
                $fechaEval,
                $criterioLabel,
                $montoSolicitado,
                $montoAprobadoTotal,
                $saldoDespues,
                $obs,
                $estadoCab
            );
            $stmt->execute();
            $idEval = $stmt->insert_id;
            $stmt->close();

            // 7) Insertar detalle en t408DetalleReqEvaluado (solo columnas reales)
            $sqlD = "INSERT INTO t408DetalleReqEvaluado (Id_ReqEvaluacion, Id_Producto, Cantidad, Precio)
                    VALUES (?, ?, ?, ?)";
            $stmtD = $this->conn->prepare($sqlD);
            if (!$stmtD) throw new Exception("Error preparar INSERT t408: " . $this->conn->error);

            foreach ($detalleEvaluado as $de) {
                if ($de['CantidadAprobada'] > 0) {
                    $stmtD->bind_param("iiid", $idEval, $de['Id_Producto'], $de['CantidadAprobada'], $de['Precio']);
                    $stmtD->execute();
                }
            }
            $stmtD->close();

            // 8) Registrar consumo en t410ConsumoPartida
            if ($montoAprobadoTotal > 0) {
                $sqlCons = "INSERT INTO t410ConsumoPartida (Id_PartidaPeriodo, Id_ReqEvaluacion, MontoConsumido, FechaRegistro, SaldoDespues)
                            VALUES (?, ?, ?, NOW(), ?)";
                $stmtCons = $this->conn->prepare($sqlCons);
                if (!$stmtCons) throw new Exception("Error preparar INSERT t410: " . $this->conn->error);
                $stmtCons->bind_param("iidd", $idPartida, $idEval, $montoAprobadoTotal, $saldoDespues);
                $stmtCons->execute();
                $stmtCons->close();

                // 9) Actualizar MontoConsumido en t406PartidaPeriodo
                //$sqlUpd = "UPDATE t406PartidaPeriodo 
                  //          SET MontoPeriodo = MontoPeriodo - ?
                  //          WHERE Id_PartidaPeriodo = ?";
                //$stmtUpd = $this->conn->prepare($sqlUpd);
                //if (!$stmtUpd) throw new Exception("Error preparar UPDATE t406: " . $this->conn->error);
                //$stmtUpd->bind_param("di", $montoAprobadoTotal, $idPartida);
                //$stmtUpd->execute();
              //  $stmtUpd->close();
            }

            // 10) Actualizar estado del requerimiento
            $sqlUpReq = "UPDATE t14RequerimientoCompra SET Estado = ? WHERE Id_Requerimiento = ?";
            $stmtUp = $this->conn->prepare($sqlUpReq);
            if (!$stmtUp) throw new Exception("Error preparar UPDATE t14: " . $this->conn->error);
            $stmtUp->bind_param("si", $estadoCab, $idReq);
            $stmtUp->execute();
            $stmtUp->close();

            // 11) Registrar en historial
            $sqlHist = "INSERT INTO t409HistorialEvaluacion (Id_ReqEvaluacion, FechaCambio, DetalleCambio)
                        VALUES (?, NOW(), ?)";
            $stmtHist = $this->conn->prepare($sqlHist);
            if ($stmtHist) {
                $detalleCambio = "Evaluación completada. Criterio: $criterioLabel. Estado: $estadoCab. Monto aprobado: S/. $montoAprobadoTotal";
                $stmtHist->bind_param("is", $idEval, $detalleCambio);
                $stmtHist->execute();
                $stmtHist->close();
            }

            $this->conn->commit();

            // 12) Devolver respuesta para el front
            return [
                'success' => true,
                'idEvaluacion' => $idEval,
                'MontoSolicitado' => $montoSolicitado,
                'MontoAprobado' => $montoAprobadoTotal,
                'SaldoDespues' => $saldoDespues,
                'Estado' => $estadoCab,
                'detalle' => $detalleEvaluado
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            $this->conn->autocommit(true);
        }
    }

    public function obtenerConsumoTotal($idPartida) {
        $sql = "SELECT IFNULL(SUM(MontoConsumido), 0) AS TotalConsumido
                FROM t410ConsumoPartida
                WHERE Id_PartidaPeriodo = ?";
        $stmt = $this->conn->prepare($sql);
        if(!$stmt) return 0;
        $stmt->bind_param("i", $idPartida);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ? (float)$res['TotalConsumido'] : 0.0;
    }

    /**
 * Devuelve el saldo (SaldoDespues) del mes anterior a la partida actual.
 * Busca la partida que tenga el Mes anterior (formato 'YYYY-MM' o 'YYYY-MM-DD' según uso).
 */
    public function obtenerSaldoAnteriorPorPartida($idPartidaActual) {
        // 1. Obtener el mes (YYYY-MM) de la partida actual
        $sql = "SELECT Mes FROM t406PartidaPeriodo WHERE Id_PartidaPeriodo = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0.0;
        $stmt->bind_param("i", $idPartidaActual);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['Mes'])) return 0.0;

        // Normalizar formato
        $mesActual = substr($row['Mes'], 0, 7); // Si está como "2025-11" o "2025-11-01"
        $fecha = DateTime::createFromFormat('Y-m', $mesActual);
        if (!$fecha) {
            $fecha = DateTime::createFromFormat('Y-m-d', $row['Mes']);
        }
        if (!$fecha) return 0.0;

        // Restar un mes
        $fecha->modify('-1 month');
        $mesAnterior = $fecha->format('Y-m');

        // 2. Obtener el Id_PartidaPeriodo del mes anterior
        $sql2 = "SELECT Id_PartidaPeriodo 
                FROM t406PartidaPeriodo 
                WHERE Mes LIKE CONCAT(?, '%') 
                ORDER BY Id_PartidaPeriodo DESC 
                LIMIT 1";
        $stmt2 = $this->conn->prepare($sql2);
        if (!$stmt2) return 0.0;
        $stmt2->bind_param("s", $mesAnterior);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if (!$r2) return 0.0;
        $idPartidaAnterior = (int)$r2['Id_PartidaPeriodo'];

        // 3. Tomar solo el último saldo registrado en ese mes anterior
        $sql3 = "SELECT SaldoDespues
                FROM t410ConsumoPartida
                WHERE Id_PartidaPeriodo = ?
                ORDER BY FechaRegistro DESC, Id_Consumo DESC
                LIMIT 1";
        $stmt3 = $this->conn->prepare($sql3);
        if (!$stmt3) return 0.0;
        $stmt3->bind_param("i", $idPartidaAnterior);
        $stmt3->execute();
        $r3 = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();

        // Si no hay registro, asumimos que no quedó saldo
        return $r3 ? (float)$r3['SaldoDespues'] : 0.0;
    }



    public function obtenerEstadoPartida($idPartida) {
        $sql = "SELECT Estado FROM t406PartidaPeriodo WHERE Id_PartidaPeriodo = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $idPartida);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ? $res['Estado'] : null;
    }


}
?>