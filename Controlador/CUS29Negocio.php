<?php
// Controlador/CUS29Negocio.php
require_once __DIR__ . "/Conexion.php";
require_once __DIR__ . "/../Modelo/EntregaModel.php";
require_once __DIR__ . "/../Modelo/PagoEntregaModel.php";
require_once __DIR__ . "/../Modelo/DenominacionPagoModel.php";
require_once __DIR__ . "/../Modelo/DenominacionVueltoModel.php";
require_once __DIR__ . "/../Modelo/IncidenciaModel.php";
require_once __DIR__ . "/../Modelo/ControlVueltoModel.php";

class CUS29Negocio {
    private $conn;
    private $EntregaModel;
    private $PagoModel;
    private $PagoDenModel;
    private $VueltoDenModel;
    private $IncidenciaModel;
    private $ControlVueltoModel;

    public function __construct() {
        date_default_timezone_set('America/Lima'); // ← AGREGAR ESTO
        $db = new Conexion();
        $this->conn = $db->conecta();
        
        $this->EntregaModel     = new EntregaModel();
        $this->PagoModel        = new PagoEntregaModel();
        $this->PagoDenModel     = new DenominacionPagoModel();
        $this->VueltoDenModel   = new DenominacionVueltoModel();
        $this->IncidenciaModel  = new IncidenciaModel();
        $this->ControlVueltoModel  = new ControlVueltoModel();
    }

    /**
     * Registrar entrega de pedido
     * @param int $idPedido
     * @param int $idDetalleAsignacion
     * @param int|null $idNotaCaja
     * @param int|null $idTrabajador
     * @param string $estadoEntrega
     * @param string $observacion
     * @return int|false ID de la entrega o false
     */
    public function registrarEntrega($idPedido, $idDetalleAsignacion, $idNotaCaja, $idTrabajador, $estadoEntrega = 'Entregado', $observacion = '') {
        // Asegurar zona horaria de Lima
        date_default_timezone_set('America/Lima');
        
        $fechaEntrega = date("Y-m-d");
        $horaEntrega  = date("H:i:s");
        
        // Debug temporal (puedes comentar después)
        error_log("Fecha entrega generada: $fechaEntrega $horaEntrega (Zona: " . date_default_timezone_get() . ")");
        
        // Si no se pasa idTrabajador, intentar obtener de sesión
        if (!$idTrabajador && isset($_SESSION["id_trabajador"])) {
            $idTrabajador = $_SESSION["id_trabajador"];
        }
        
        $this->conn->begin_transaction();
        
        try {
            $idEntrega = $this->EntregaModel->registrarEntrega(
                $idPedido,
                $idDetalleAsignacion,
                $idNotaCaja,
                $idTrabajador,
                $fechaEntrega,
                $horaEntrega,
                $estadoEntrega,
                $observacion
            );
            
            if (!$idEntrega) {
                throw new Exception("Error al registrar entrega.");
            }
            
            $this->conn->commit();
            return $idEntrega;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error en registrarEntrega: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar pago de entrega
     */
    public function registrarPago($idEntrega, $montoEsperado, $montoRecibido, $montoVuelto) {
        $this->conn->begin_transaction();
        
        try {
            $idPago = $this->PagoModel->registrarPago(
                $idEntrega,
                $montoEsperado,
                $montoRecibido,
                $montoVuelto
            );
            
            if (!$idPago) {
                throw new Exception("No se pudo registrar el pago.");
            }
            
            $this->conn->commit();
            return $idPago;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error en registrarPago: " . $e->getMessage());
            return false;
        }
    }

    public function registrarDenominacionPago($idPago, $tipo, $denominacion, $cantidad) {
        return $this->PagoDenModel->registrarDenominacionPago($idPago, $tipo, $denominacion, $cantidad);
    }

    public function registrarDenominacionVuelto($idPago, $tipo, $denominacion, $cantidad) {
        return $this->VueltoDenModel->registrarDenominacionVuelto($idPago, $tipo, $denominacion, $cantidad);
    }

    public function registrarIncidencia($idEntrega, $motivo, $detalle) {
        return $this->IncidenciaModel->registrarIncidencia($idEntrega, $motivo, $detalle);
    }

    public function obtenerInfoPedidoParaEntrega($idPedido) {
        return $this->EntregaModel->obtenerInfoPedidoParaEntrega($idPedido);
    }
    
    /**
     * Obtener saldo actual de vuelto del repartidor
     * @param int $idNotaCaja
     * @return float
     */
    public function obtenerSaldoVueltoActual($idNotaCaja) {
        try {
            // Intentar obtener el saldo del último movimiento
            $saldo = $this->ControlVueltoModel->obtenerSaldoActual($idNotaCaja);
            
            // Si no existe, inicializar con el VueltoTotal de la nota de caja
            if ($saldo === null) {
                error_log("No existe control de vuelto para nota $idNotaCaja, inicializando...");
                
                // Obtener VueltoTotal de t28Nota_caja
                $sql = "SELECT VueltoTotal FROM t28Nota_caja WHERE IDNotaCaja = ?";
                $stmt = $this->conn->prepare($sql);
                
                if (!$stmt) {
                    error_log("Error al preparar consulta: " . $this->conn->error);
                    return 0;
                }
                
                $stmt->bind_param("i", $idNotaCaja);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $vueltoTotal = (float)$row['VueltoTotal'];
                    error_log("VueltoTotal encontrado: $vueltoTotal");
                    
                    // Inicializar control de vuelto
                    $resultado = $this->ControlVueltoModel->inicializarControlVuelto($idNotaCaja, $vueltoTotal);
                    
                    if ($resultado) {
                        error_log("Control de vuelto inicializado correctamente");
                        return $vueltoTotal;
                    } else {
                        error_log("Error al inicializar control de vuelto");
                        return $vueltoTotal; // Devolver el valor aunque falle la inicialización
                    }
                }
                
                error_log("No se encontró la nota de caja $idNotaCaja");
                return 0;
            }
            
            error_log("Saldo actual obtenido: $saldo");
            return $saldo;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSaldoVueltoActual: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Registrar movimientos de vuelto al hacer una entrega
     * @param int $idNotaCaja
     * @param int $idEntrega
     * @param float $montoRecibido
     * @param float $vueltoEntregado
     * @return bool
     */
    public function registrarMovimientosVuelto($idNotaCaja, $idEntrega, $montoRecibido, $vueltoEntregado) {
        try {
            // Obtener saldo actual
            $saldoActual = $this->obtenerSaldoVueltoActual($idNotaCaja);
            
            // 1. Registrar USO de vuelto (resta)
            if ($vueltoEntregado > 0) {
                $nuevoSaldo = $saldoActual - $vueltoEntregado;
                $this->ControlVueltoModel->registrarMovimiento(
                    $idNotaCaja,
                    'USO_VUELTO',
                    $vueltoEntregado,
                    $nuevoSaldo,
                    $idEntrega,
                    "Vuelto entregado al cliente"
                );
                $saldoActual = $nuevoSaldo;
            }
            
            // 2. Registrar RECEPCIÓN de cobro (suma)
            if ($montoRecibido > 0) {
                $nuevoSaldo = $saldoActual + $montoRecibido;
                $this->ControlVueltoModel->registrarMovimiento(
                    $idNotaCaja,
                    'RECEPCION_COBRO',
                    $montoRecibido,
                    $nuevoSaldo,
                    $idEntrega,
                    "Cobro recibido del cliente"
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error en registrarMovimientosVuelto: " . $e->getMessage());
            return false;
        }
    }
}
?>