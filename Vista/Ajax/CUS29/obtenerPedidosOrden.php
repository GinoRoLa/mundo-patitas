<?php
// Vista/Ajax/CUS29/obtenerPedidosOrden.php
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../Controlador/Conexion.php';
    
    $idOrdenAsignacion = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$idOrdenAsignacion) {
        throw new Exception('ID de orden de asignación inválido.');
    }
    
    $db = new Conexion();
    $conn = $db->conecta();
    
    // 1. Obtener info de la orden y nota de caja
    $sqlOrden = "SELECT 
                    t40.Id_OrdenAsignacion,
                    t40.FechaProgramada,
                    t40.Estado,
                    t28.IDNotaCaja,
                    t28.IDRepartidor,
                    t28.VueltoTotal,
                    t16.des_nombreTrabajador,
                    t16.des_apepatTrabajador,
                    t16.des_apematTrabajador,
                    t78.Placa,
                    t78.Marca,
                    t78.Modelo
                FROM t40OrdenAsignacionReparto t40
                LEFT JOIN t28Nota_caja t28 ON t28.IDAsignacionReparto = t40.Id_OrdenAsignacion
                LEFT JOIN t79AsignacionRepartidorVehiculo t79 ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
                LEFT JOIN t16CatalogoTrabajadores t16 ON t16.id_Trabajador = t79.Id_Trabajador
                LEFT JOIN t78Vehiculo t78 ON t78.Id_Vehiculo = t79.Id_Vehiculo
                WHERE t40.Id_OrdenAsignacion = ?";
    
    $stmtOrden = $conn->prepare($sqlOrden);
    if (!$stmtOrden) {
        throw new Exception('Error al preparar consulta de orden: ' . $conn->error);
    }
    
    $stmtOrden->bind_param("i", $idOrdenAsignacion);
    $stmtOrden->execute();
    $resOrden = $stmtOrden->get_result();
    $orden = $resOrden->fetch_assoc();
    $stmtOrden->close();
    
    if (!$orden) {
        throw new Exception('Orden de asignación no encontrada.');
    }
    
    // Construir nombre completo del repartidor
    $nombreRepartidor = 'Sin asignar';
    if ($orden['des_nombreTrabajador']) {
        $nombreRepartidor = trim($orden['des_nombreTrabajador'] . ' ' . 
                                 $orden['des_apepatTrabajador'] . ' ' . 
                                 $orden['des_apematTrabajador']);
    }
    
    // Construir info del vehículo
    $vehiculoInfo = 'Sin vehículo';
    if ($orden['Placa']) {
        $vehiculoInfo = $orden['Marca'] . ' ' . ($orden['Modelo'] ?? '') . ' - ' . $orden['Placa'];
    }
    
    // 2. Obtener pedidos de esta orden
    $sqlPedidos = "SELECT 
                    t02.Id_OrdenPedido,
                    t02.Total,
                    t02.Estado as EstadoPedido,
                    t20.des_nombreCliente as NombreCliente,
                    t20.des_apepatCliente as ApePatCliente,
                    t20.des_apematCliente as ApeMatCliente,
                    t20.direccionCliente as Direccion,
                    t401.Id_DetalleAsignacion,
                    COALESCE(
                        (SELECT COUNT(*) FROM t430EntregaPedidoDelivery t430 
                         WHERE t430.Id_OrdenPedido = t02.Id_OrdenPedido), 0
                    ) as YaEntregado
                FROM t401DetalleAsignacionReparto t401
                INNER JOIN t59OrdenServicioEntrega t59 ON t59.Id_OSE = t401.Id_OSE
                INNER JOIN t02OrdenPedido t02 ON t02.Id_OrdenPedido = t59.Id_OrdenPedido
                INNER JOIN t20Cliente t20 ON t20.Id_Cliente = t02.Id_Cliente
                WHERE t401.Id_OrdenAsignacion = ?
                ORDER BY t02.Id_OrdenPedido ASC";
    
    $stmtPedidos = $conn->prepare($sqlPedidos);
    if (!$stmtPedidos) {
        throw new Exception('Error al preparar consulta de pedidos: ' . $conn->error);
    }
    
    $stmtPedidos->bind_param("i", $idOrdenAsignacion);
    $stmtPedidos->execute();
    $resPedidos = $stmtPedidos->get_result();
    
    $pedidos = [];
    while ($row = $resPedidos->fetch_assoc()) {
        // Determinar estado real del pedido
        $estadoReal = 'Pendiente';
        
        // Si ya tiene registro en t430EntregaPedidoDelivery, está entregado
        if ($row['YaEntregado'] > 0) {
            $estadoReal = 'Entregado';
        }
        // O si el estado en t02OrdenPedido ya es "Entregado"
        else if (strtolower($row['EstadoPedido']) == 'entregado') {
            $estadoReal = 'Entregado';
        }
        
        $pedidos[] = [
            'idPedido' => $row['Id_OrdenPedido'],
            'idDetalleAsignacion' => $row['Id_DetalleAsignacion'],
            'cliente' => trim($row['NombreCliente'] . ' ' . $row['ApePatCliente'] . ' ' . $row['ApeMatCliente']),
            'direccion' => $row['Direccion'] ?? 'Sin dirección',
            'total' => floatval($row['Total']),
            'estado' => $estadoReal
        ];
    }
    $stmtPedidos->close();
    
    echo json_encode([
        'success' => true,
        'orden' => [
            'idOrden' => $orden['Id_OrdenAsignacion'],
            'fechaProgramada' => $orden['FechaProgramada'],
            'estado' => $orden['Estado'],
            'idNotaCaja' => $orden['IDNotaCaja'] ?? null,
            'idRepartidor' => $orden['IDRepartidor'] ?? null,
            'nombreRepartidor' => $nombreRepartidor,
            'vehiculo' => $vehiculoInfo,
            'vueltoTotal' => floatval($orden['VueltoTotal'] ?? 0)
        ],
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtenerPedidosOrden.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>