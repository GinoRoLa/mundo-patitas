<?php
// Controlador para la funcionalidad de Emitir Nota de Caja para Delivery
header('Content-Type: application/json; charset=utf-8');

// Configuración de la base de datos
$host = 'localhost';
$port = '3306';
$dbname = 'mundo_patitas3';
$username = 'root';
$password = '12345';

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
    ]);
    exit;
}

// Función para obtener datos del responsable de caja
function obtenerResponsable($pdo) {
    try {
        // Ajusta el ID según tu base de datos (aquí uso 50009 como ejemplo para responsable de caja)
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50001";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $responsable = $stmt->fetch();
        
        if ($responsable) {
            return [
                'success' => true,
                'data' => $responsable
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No se encontró el responsable de caja'
            ];
        }
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del responsable: ' . $e->getMessage()
        ];
    }
}

// Función para buscar datos del repartidor
function buscarRepartidor($pdo, $idRepartidor) {
    try {
        if (!$idRepartidor) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idRepartidor'
            ];
        }

        // Consulta SQL exacta proporcionada por el usuario con filtro de Estado = 'Despachada'
        $sql = "SELECT 
                    t16.DNITrabajador,
                    t16.des_nombreTrabajador,
                    t16.des_apepatTrabajador,
                    t40.Id_OrdenAsignacion,
                    COUNT(t401.Id_DetalleAsignacion) AS Cantidad_Detalles
                FROM t16catalogotrabajadores t16
                JOIN t79asignacionrepartidorvehiculo t79
                    ON t16.id_Trabajador = t79.Id_Trabajador
                JOIN t40ordenasignacionreparto t40
                    ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
                LEFT JOIN t401detalleasignacionreparto t401
                    ON t40.Id_OrdenAsignacion = t401.Id_OrdenAsignacion
                WHERE 
                    t16.id_Trabajador = :idRepartidor
                    AND t40.Estado = 'Despachada'
                GROUP BY 
                    t16.DNITrabajador,
                    t16.des_nombreTrabajador,
                    t16.des_apepatTrabajador,
                    t40.Id_OrdenAsignacion";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idRepartidor', $idRepartidor);
        $stmt->execute();
        $resultado = $stmt->fetch();
        
        if ($resultado) {
            return [
                'success' => true,
                'data' => [
                    'DNI' => $resultado['DNITrabajador'],
                    'Nombre' => $resultado['des_nombreTrabajador'],
                    'ApellidoPaterno' => $resultado['des_apepatTrabajador'],
                    'IdOrdenAsignacion' => $resultado['Id_OrdenAsignacion'],
                    'TotalOrdenes' => $resultado['Cantidad_Detalles']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Repartidor no encontrado o sin Asignación de Reparto en estado Despachada'
            ];
        }
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al buscar repartidor: ' . $e->getMessage()
        ];
    }
}

// Función para obtener detalle de contra entregas usando ID Orden de Asignación
function obtenerDetalleContraEntregas($pdo, $idOrdenAsignacion) {
    try {
        if (!$idOrdenAsignacion) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idOrdenAsignacion'
            ];
        }

        // Consulta SQL exacta proporcionada por el usuario
        $sql = "SELECT 
                    t501.IdDet,
                    t501.IdOrdenPedido,
                    t501.Total,
                    t501.EfectivoCliente,
                    t501.Vuelto
                FROM t40ordenasignacionreparto t40
                JOIN t401detalleasignacionreparto t401
                    ON t40.Id_OrdenAsignacion = t401.Id_OrdenAsignacion
                JOIN t59ordenservicioentrega t59
                    ON t401.Id_OSE = t59.Id_OSE
                JOIN t501detalleopce t501
                    ON t59.Id_OrdenPedido = t501.IdOrdenPedido
                WHERE 
                    t40.Id_OrdenAsignacion = :idOrdenAsignacion";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idOrdenAsignacion', $idOrdenAsignacion);
        $stmt->execute();
        $resultados = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener detalle de contra entregas: ' . $e->getMessage()
        ];
    }
}

// Función para generar nota de caja
function generarNotaCaja($pdo, $datos) {
    try {
        // Validar datos requeridos
        $idResponsable = isset($datos['idResponsable']) ? $datos['idResponsable'] : null;
        $idRepartidor = isset($datos['idRepartidor']) ? $datos['idRepartidor'] : null;
        $idOrdenAsignacion = isset($datos['idOrdenAsignacion']) ? $datos['idOrdenAsignacion'] : null;
        $totalContraEntregas = isset($datos['totalContraEntregas']) ? $datos['totalContraEntregas'] : 0;
        $totalVuelto = isset($datos['totalVuelto']) ? $datos['totalVuelto'] : 0;
        
        if (!$idResponsable || !$idRepartidor || !$idOrdenAsignacion) {
            return [
                'success' => false,
                'message' => 'Faltan datos requeridos para generar la nota de caja'
            ];
        }

        // INSERT exacto proporcionado por el usuario
        $sql = "INSERT INTO t28Nota_caja 
                (IDResponsableCaja, IDRepartidor, IDAsignacionReparto, TotalContraEntrega, VueltoTotal)
                VALUES
                (:idResponsable, :idRepartidor, :idOrdenAsignacion, :totalContraEntregas, :totalVuelto)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idResponsable' => $idResponsable,
            ':idRepartidor' => $idRepartidor,
            ':idOrdenAsignacion' => $idOrdenAsignacion,
            ':totalContraEntregas' => $totalContraEntregas,
            ':totalVuelto' => $totalVuelto
        ]);
        
        $idNotaCaja = $pdo->lastInsertId();

        return [
            'success' => true,
            'message' => 'Nota de caja generada exitosamente',
            'idNotaCaja' => $idNotaCaja
        ];

    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al generar nota de caja: ' . $e->getMessage()
        ];
    }
}

// Función para obtener notas de caja generadas
function obtenerNotasCajaGeneradas($pdo) {
    try {
        // SELECT exacto proporcionado por el usuario
        $sql = "SELECT 
                    IDNotaCaja,
                    IDResponsableCaja,
                    IDRepartidor,
                    IDAsignacionReparto,
                    TotalContraEntrega,
                    VueltoTotal,
                    FechaEmision
                FROM t28Nota_caja
                ORDER BY FechaEmision DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener notas de caja: ' . $e->getMessage()
        ];
    }
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_responsable':
        echo json_encode(obtenerResponsable($pdo));
        break;
        
    case 'buscar_repartidor':
        $idRepartidor = isset($_GET['idRepartidor']) ? $_GET['idRepartidor'] : null;
        echo json_encode(buscarRepartidor($pdo, $idRepartidor));
        break;
        
    case 'obtener_detalle_contra_entregas':
        $idOrdenAsignacion = isset($_GET['idOrdenAsignacion']) ? $_GET['idOrdenAsignacion'] : null;
        echo json_encode(obtenerDetalleContraEntregas($pdo, $idOrdenAsignacion));
        break;
        
    case 'generar_nota_caja':
        // Leer datos JSON del cuerpo de la petición
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(generarNotaCaja($pdo, $datos));
        break;
        
    case 'obtener_notas_caja_generadas':
        echo json_encode(obtenerNotasCajaGeneradas($pdo));
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
        break;
}
?>
