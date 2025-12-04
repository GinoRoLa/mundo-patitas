<?php
// Vista/Ajax/CUS29/actualizarEstados.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/Conexion.php';
    
    $payload = json_decode(file_get_contents('php://input'), true);
    
    if (!$payload) {
        throw new Exception('Payload inválido.');
    }
    
    $idPedido = $payload['idPedido'] ?? null;
    $idOrdenAsignacion = $payload['idOrdenAsignacion'] ?? null;
    $estadoEntrega = $payload['estadoEntrega'] ?? 'Entregado';
    
    if (!$idPedido || !$idOrdenAsignacion) {
        throw new Exception('Faltan datos requeridos.');
    }
    
    $db = new Conexion();
    $conn = $db->conecta();
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Actualizar estado del pedido en t02OrdenPedido
        $sqlUpdatePedido = "UPDATE t02OrdenPedido 
                           SET Estado = ? 
                           WHERE Id_OrdenPedido = ?";
        
        $stmtPedido = $conn->prepare($sqlUpdatePedido);
        if (!$stmtPedido) {
            throw new Exception('Error al preparar actualización de pedido: ' . $conn->error);
        }
        
        $stmtPedido->bind_param("si", $estadoEntrega, $idPedido);
        
        if (!$stmtPedido->execute()) {
            throw new Exception('Error al actualizar estado del pedido: ' . $stmtPedido->error);
        }
        
        $stmtPedido->close();
        
        // 2. Verificar si todos los pedidos de la orden están entregados
        $sqlVerificar = "SELECT 
                            COUNT(*) as TotalPedidos,
                            SUM(CASE 
                                WHEN t02.Estado = 'Entregado' 
                                    OR EXISTS (
                                        SELECT 1 
                                        FROM t430EntregaPedidoDelivery t430 
                                        WHERE t430.Id_OrdenPedido = t02.Id_OrdenPedido
                                    )
                                THEN 1 
                                ELSE 0 
                            END) as PedidosEntregados
                        FROM t401DetalleAsignacionReparto t401
                        INNER JOIN t59OrdenServicioEntrega t59 ON t59.Id_OSE = t401.Id_OSE
                        INNER JOIN t02OrdenPedido t02 ON t02.Id_OrdenPedido = t59.Id_OrdenPedido
                        WHERE t401.Id_OrdenAsignacion = ?";
        
        $stmtVerificar = $conn->prepare($sqlVerificar);
        if (!$stmtVerificar) {
            throw new Exception('Error al preparar verificación: ' . $conn->error);
        }
        
        $stmtVerificar->bind_param("i", $idOrdenAsignacion);
        $stmtVerificar->execute();
        $resVerificar = $stmtVerificar->get_result();
        $verificacion = $resVerificar->fetch_assoc();
        $stmtVerificar->close();
        
        $todosEntregados = false;
        $nuevoEstadoOrden = 'Pendiente';
        
        if ($verificacion['TotalPedidos'] > 0 && 
            $verificacion['TotalPedidos'] == $verificacion['PedidosEntregados']) {
            // Todos los pedidos están entregados
            $todosEntregados = true;
            $nuevoEstadoOrden = 'Finalizada';
            
            // 3. Actualizar estado de la orden de asignación
            $sqlUpdateOrden = "UPDATE t40OrdenAsignacionReparto 
                              SET Estado = ? 
                              WHERE Id_OrdenAsignacion = ?";
            
            $stmtOrden = $conn->prepare($sqlUpdateOrden);
            if (!$stmtOrden) {
                throw new Exception('Error al preparar actualización de orden: ' . $conn->error);
            }
            
            $stmtOrden->bind_param("si", $nuevoEstadoOrden, $idOrdenAsignacion);
            
            if (!$stmtOrden->execute()) {
                throw new Exception('Error al actualizar estado de orden: ' . $stmtOrden->error);
            }
            
            $stmtOrden->close();
        } else {
            // Actualizar a "En Proceso" si hay al menos una entrega
            $nuevoEstadoOrden = 'En Proceso';
            
            $sqlUpdateOrden = "UPDATE t40OrdenAsignacionReparto 
                              SET Estado = ? 
                              WHERE Id_OrdenAsignacion = ? 
                              AND Estado = 'Pendiente'";
            
            $stmtOrden = $conn->prepare($sqlUpdateOrden);
            if ($stmtOrden) {
                $stmtOrden->bind_param("si", $nuevoEstadoOrden, $idOrdenAsignacion);
                $stmtOrden->execute();
                $stmtOrden->close();
            }
        }
        
        // Confirmar transacción
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'estadoPedido' => $estadoEntrega,
            'estadoOrden' => $nuevoEstadoOrden,
            'todosEntregados' => $todosEntregados,
            'totalPedidos' => $verificacion['TotalPedidos'],
            'pedidosEntregados' => $verificacion['PedidosEntregados']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error en actualizarEstados.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>