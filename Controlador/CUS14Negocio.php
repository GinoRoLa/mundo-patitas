<?php
// Controlador para la funcionalidad de solicitud de cotizaciones al proveedor
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

// Función para obtener datos del responsable de compra
function obtenerResponsable($pdo) {
    try {
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50028";
        
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
                'message' => 'No se encontró el responsable de compra'
            ];
        }
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del responsable: ' . $e->getMessage()
        ];
    }
}

// Función para obtener solicitudes de requerimiento evaluadas
function obtenerRequerimientosEvaluados($pdo) {
    try {
        $sql = "SELECT 
                    Id_ReqEvaluacion
                FROM 
                    t407RequerimientoEvaluado
                WHERE 
                    Estado IN ('Aprobado', 'Parcialmente Aprobado')
                ORDER BY Id_ReqEvaluacion DESC";
        
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
            'message' => 'Error al obtener requerimientos evaluados: ' . $e->getMessage()
        ];
    }
}

// Función para obtener productos por requerimiento evaluado
function obtenerProductosRequerimiento($pdo, $idRequerimiento) {
    try {
        if (!$idRequerimiento) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idRequerimiento'
            ];
        }

        // Consulta para obtener productos del requerimiento evaluado usando Id_ReqEvaluacion
        $sql = "SELECT 
                    p.Id_Producto,
                    p.NombreProducto,
                    d.Cantidad
                FROM 
                    t408DetalleReqEvaluado d
                JOIN 
                    t18CatalogoProducto p 
                    ON d.Id_Producto = p.Id_Producto
                WHERE 
                    d.Id_ReqEvaluacion = :idRequerimiento";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idRequerimiento', $idRequerimiento);
        $stmt->execute();
        $resultados = $stmt->fetchAll();

        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener productos del requerimiento: ' . $e->getMessage()
        ];
    }
}

// Función para obtener proveedores filtrados por producto
function obtenerProveedores($pdo, $nombreProducto = null) {
    try {
        if ($nombreProducto) {
            // Consulta para obtener proveedores filtrados por producto
            $sql = "SELECT DISTINCT
                        p.Id_NumRuc AS RUC,
                        p.des_RazonSocial AS Empresa,
                        p.Correo
                    FROM 
                        t17CatalogoProveedor p
                    JOIN 
                        t99_proveedores_productos pp 
                        ON p.Id_NumRuc = pp.Id_NumRuc
                    JOIN 
                        t18CatalogoProducto c 
                        ON pp.Id_Producto = c.Id_Producto
                    WHERE 
                        p.estado = 'Activo'
                        AND c.NombreProducto = :nombreProducto
                    ORDER BY p.des_RazonSocial";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombreProducto', $nombreProducto);
            $stmt->execute();
            $resultados = $stmt->fetchAll();
        } else {
            // Si no se proporciona nombre de producto, no mostrar proveedores
            $resultados = [];
        }

        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener proveedores: ' . $e->getMessage()
        ];
    }
}

// Función para generar solicitud de cotización
function generarSolicitud($pdo, $datos) {
    try {
        // Validar datos requeridos
        if (!isset($datos['requerimientos']) || empty($datos['requerimientos'])) {
            return [
                'success' => false,
                'message' => 'Faltan los requerimientos seleccionados'
            ];
        }

        if (!isset($datos['productos']) || empty($datos['productos'])) {
            return [
                'success' => false,
                'message' => 'Faltan los productos'
            ];
        }

        if (!isset($datos['proveedores']) || empty($datos['proveedores'])) {
            return [
                'success' => false,
                'message' => 'Faltan los proveedores'
            ];
        }

        // Generar solicitudes de cotización
        $solicitudes = [];
        $timestamp = time();
        
        foreach ($datos['proveedores'] as $index => $proveedor) {
            foreach ($datos['productos'] as $productoIndex => $producto) {
                $idSolicitud = 'SOL-' . $timestamp . '-' . ($index * count($datos['productos']) + $productoIndex + 1);
                
                $solicitudes[] = [
                    'Id_Solicitud' => $idSolicitud,
                    'RUC' => $proveedor['RUC'] || $proveedor['ruc'] || '',
                    'Empresa' => $proveedor['Empresa'] || $proveedor['NombreEmpresa'] || '',
                    'Correo' => $proveedor['Correo'] || $proveedor['Email'] || '',
                    'Producto' => $producto['NombreProducto'] || '',
                    'Cantidad' => $producto['Cantidad'] || 0
                ];
            }
        }

        // Aquí podrías guardar en la base de datos si es necesario
        // Por ahora, solo retornamos las solicitudes generadas

        return [
            'success' => true,
            'message' => 'Solicitud generada exitosamente',
            'data' => $solicitudes
        ];

    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al generar la solicitud: ' . $e->getMessage()
        ];
    }
}

// Función para enviar solicitud de cotización
function enviarSolicitud($pdo, $datos) {
    try {
        // Validar datos requeridos
        if (!isset($datos['requerimientos']) || empty($datos['requerimientos'])) {
            return [
                'success' => false,
                'message' => 'Faltan los requerimientos seleccionados'
            ];
        }

        if (!isset($datos['solicitud']) || empty($datos['solicitud'])) {
            return [
                'success' => false,
                'message' => 'Falta la solicitud de cotización'
            ];
        }

        // Aquí deberías implementar la lógica para:
        // 1. Guardar las solicitudes en la base de datos
        // 2. Enviar correos electrónicos a los proveedores (opcional)
        // 3. Actualizar el estado de los requerimientos evaluados
        
        // Ejemplo de guardado (ajustar según tu estructura de BD):
        /*
        $pdo->beginTransaction();
        
        try {
            foreach ($datos['solicitud'] as $solicitud) {
                $sql = "INSERT INTO tSolicitudCotizacion 
                        (Id_Solicitud, RUC, Empresa, Correo, Producto, Cantidad, FechaCreacion, Estado)
                        VALUES 
                        (:idSolicitud, :ruc, :empresa, :correo, :producto, :cantidad, NOW(), 'Enviada')";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':idSolicitud' => $solicitud['Id_Solicitud'],
                    ':ruc' => $solicitud['RUC'],
                    ':empresa' => $solicitud['Empresa'],
                    ':correo' => $solicitud['Correo'],
                    ':producto' => $solicitud['Producto'],
                    ':cantidad' => $solicitud['Cantidad']
                ]);
            }
            
            // Actualizar estado de requerimientos evaluados
            foreach ($datos['requerimientos'] as $idRequerimiento) {
                $sql = "UPDATE tRequerimientoEvaluado 
                        SET Estado = 'En Cotización' 
                        WHERE Id_RequerimientoEvaluado = :idRequerimiento";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':idRequerimiento' => $idRequerimiento]);
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        */

        return [
            'success' => true,
            'message' => 'Solicitud enviada exitosamente'
        ];

    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al enviar la solicitud: ' . $e->getMessage()
        ];
    }
}

// Función para actualizar estado del requerimiento a 'Solicitado'
function actualizarEstadoSolicitado($pdo, $datos) {
    try {
        $idReqEvaluacion = isset($datos['idReqEvaluacion']) ? $datos['idReqEvaluacion'] : null;
        
        if (!$idReqEvaluacion) {
            return [
                'success' => false,
                'message' => 'Falta el ID del requerimiento'
            ];
        }

        $sql = "UPDATE t407RequerimientoEvaluado 
                SET Estado = 'Solicitado' 
                WHERE Id_ReqEvaluacion = :idReqEvaluacion";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idReqEvaluacion', $idReqEvaluacion);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'Estado actualizado a Solicitado correctamente'
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al actualizar estado: ' . $e->getMessage()
        ];
    }
}

// Ejecuta el procedimiento almacenado sp_GenerarSolicitudCotizacion por cada proveedor
function generarSolicitudCotizacionBD($pdo, $datos) {
    try {
        $idReq = isset($datos['idReqEvaluacion']) ? $datos['idReqEvaluacion'] : null;
        $idProducto = isset($datos['idProducto']) ? $datos['idProducto'] : null;
        $producto = isset($datos['producto']) ? $datos['producto'] : null;
        $cantidad = isset($datos['cantidad']) ? $datos['cantidad'] : null;
        $proveedores = isset($datos['proveedores']) ? $datos['proveedores'] : [];

        if (!$idReq || !$idProducto || !$producto || $cantidad === null || empty($proveedores)) {
            return [
                'success' => false,
                'message' => 'Parámetros incompletos para generar solicitud'
            ];
        }

        $stmt = $pdo->prepare("CALL sp_GenerarSolicitudCotizacion(:idReq, :ruc, :empresa, :correo, :idProducto, :producto, :cantidad)");

        $procesados = 0;
        foreach ($proveedores as $prov) {
            $ruc = isset($prov['RUC']) ? $prov['RUC'] : (isset($prov['ruc']) ? $prov['ruc'] : null);
            $empresa = isset($prov['Empresa']) ? $prov['Empresa'] : (isset($prov['NombreEmpresa']) ? $prov['NombreEmpresa'] : null);
            $correo = isset($prov['Correo']) ? $prov['Correo'] : (isset($prov['Email']) ? $prov['Email'] : null);

            if (!$ruc || !$empresa || !$correo) {
                continue; // omitir filas inválidas
            }

            $stmt->bindParam(':idReq', $idReq);
            $stmt->bindParam(':ruc', $ruc);
            $stmt->bindParam(':empresa', $empresa);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':idProducto', $idProducto);
            $stmt->bindParam(':producto', $producto);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->execute();

            // Consumir posibles conjuntos de resultados para liberar el statement
            while ($stmt->nextRowset()) { /* no-op */ }

            $procesados++;
        }

        return [
            'success' => true,
            'message' => 'Procedimiento ejecutado',
            'procesados' => $procesados
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al ejecutar procedimiento: ' . $e->getMessage()
        ];
    }
}

// Obtener solicitudes de cotización pendientes desde BD
function obtenerSolicitudesPendientes($pdo) {
    try {
        $sql = "SELECT 
                    c.IDsolicitud AS Id_Solicitud,
                    c.RUC,
                    c.Empresa,
                    c.Correo,
                    d.Id_Producto,
                    d.Producto,
                    d.Cantidad,
                    c.FechaEmision,
                    c.FechaCierre
                FROM 
                    t100Solicitud_Cotizacion_Proveedor c
                JOIN 
                    t101Detalle_Solicitud_Cotizacion_Proveedor d 
                    ON c.IDsolicitud = d.IDsolicitud
                WHERE 
                    c.Estado = 'Pendiente'";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return [ 'success' => true, 'data' => $rows ];
    } catch (PDOException $e) {
        return [ 'success' => false, 'message' => 'Error al obtener solicitudes: ' . $e->getMessage() ];
    }
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_responsable':
        echo json_encode(obtenerResponsable($pdo));
        break;
        
    case 'obtener_requerimientos_evaluados':
        echo json_encode(obtenerRequerimientosEvaluados($pdo));
        break;
        
    case 'obtener_productos_requerimiento':
        $idRequerimiento = isset($_GET['idRequerimiento']) ? $_GET['idRequerimiento'] : null;
        echo json_encode(obtenerProductosRequerimiento($pdo, $idRequerimiento));
        break;
        
    case 'obtener_proveedores':
        $nombreProducto = isset($_GET['nombreProducto']) ? $_GET['nombreProducto'] : null;
        echo json_encode(obtenerProveedores($pdo, $nombreProducto));
        break;
        
    case 'generar_solicitud':
        // Leer datos JSON del cuerpo de la petición
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(generarSolicitud($pdo, $datos));
        break;

    case 'generar_solicitud_bd':
        // Ejecutar SP por cada proveedor
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(generarSolicitudCotizacionBD($pdo, $datos));
        break;

    case 'obtener_solicitudes_pendientes':
        echo json_encode(obtenerSolicitudesPendientes($pdo));
        break;
        
    case 'enviar_solicitud':
        // Leer datos JSON del cuerpo de la petición
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(enviarSolicitud($pdo, $datos));
        break;

    // Y agregar este case en el switch (alrededor de la línea 350):
    case 'actualizar_estado_solicitado':
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(actualizarEstadoSolicitado($pdo, $datos));
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
        break;
}
?>

