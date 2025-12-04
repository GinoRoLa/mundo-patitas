<?php
// Controlador para la funcionalidad de solicitud de cotizaciones al proveedor
header('Content-Type: application/json; charset=utf-8');

// ✅ USAR LA CONEXIÓN DEL GRUPO
require_once(__DIR__ . '/Conexion.php');

// ✅ CREAR INSTANCIA DE CONEXIÓN
$conexion = new Conexion();
$cn = $conexion->conecta();

// Verificar conexión
if (!$cn) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}

// Función para obtener datos del responsable de compra
function obtenerResponsable($cn) {
    try {
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50028";
        
        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $responsable = mysqli_fetch_assoc($resultado);
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
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del responsable: ' . $e->getMessage()
        ];
    }
}

// Función para obtener solicitudes de requerimiento evaluadas
function obtenerRequerimientosEvaluados($cn) {
    try {
        $sql = "SELECT 
                    Id_ReqEvaluacion
                FROM 
                    t407RequerimientoEvaluado
                WHERE 
                    Estado IN ('Aprobado', 'Parcialmente Aprobado')
                ORDER BY Id_ReqEvaluacion DESC";
        
        $resultado = mysqli_query($cn, $sql);
        $datos = [];
        
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $datos[] = $fila;
            }
        }

        return [
            'success' => true,
            'data' => $datos
        ];
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener requerimientos evaluados: ' . $e->getMessage()
        ];
    }
}

// Función para obtener productos por requerimiento evaluado
function obtenerProductosRequerimiento($cn, $idRequerimiento) {
    try {
        if (!$idRequerimiento) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idRequerimiento'
            ];
        }

        $idRequerimiento = mysqli_real_escape_string($cn, $idRequerimiento);

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
                    d.Id_ReqEvaluacion = '$idRequerimiento'";
        
        $resultado = mysqli_query($cn, $sql);
        $datos = [];
        
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $datos[] = $fila;
            }
        }

        return [
            'success' => true,
            'data' => $datos
        ];
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener productos del requerimiento: ' . $e->getMessage()
        ];
    }
}

// Función para obtener proveedores filtrados por requerimiento evaluado
function obtenerProveedores($cn, $idRequerimiento = null) {
    try {
        if (!$idRequerimiento) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idRequerimiento'
            ];
        }

        $idRequerimiento = mysqli_real_escape_string($cn, $idRequerimiento);

        $sql = "SELECT DISTINCT
                    prov.Id_NumRuc,
                    prov.des_RazonSocial,
                    prov.Correo
                FROM 
                    t17CatalogoProveedor prov
                JOIN 
                    t99_proveedores_productos pp 
                    ON prov.Id_NumRuc = pp.Id_NumRuc
                JOIN 
                    t18CatalogoProducto prod 
                    ON pp.Id_Producto = prod.Id_Producto
                JOIN 
                    t408DetalleReqEvaluado det 
                    ON det.Id_Producto = prod.Id_Producto
                WHERE 
                    det.Id_ReqEvaluacion = '$idRequerimiento'
                    AND prov.estado = 'Activo'
                ORDER BY prov.des_RazonSocial";
        
        $resultado = mysqli_query($cn, $sql);
        $datos = [];
        
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $datos[] = $fila;
            }
        }

        return [
            'success' => true,
            'data' => $datos
        ];
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener proveedores: ' . $e->getMessage()
        ];
    }
}

// Función para obtener proveedores de un producto específico
function obtenerProveedoresPorProducto($cn, $idProducto) {
    try {
        if (!$idProducto) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idProducto'
            ];
        }

        $idProducto = mysqli_real_escape_string($cn, $idProducto);

        $sql = "SELECT 
                    p.Id_NumRuc,
                    p.des_RazonSocial,
                    p.Correo
                FROM 
                    t17CatalogoProveedor p
                INNER JOIN 
                    t99_proveedores_productos pp 
                    ON p.Id_NumRuc = pp.Id_NumRuc
                INNER JOIN 
                    t18CatalogoProducto c 
                    ON pp.Id_Producto = c.Id_Producto
                WHERE 
                    c.Id_Producto = '$idProducto'
                    AND p.estado = 'Activo'
                ORDER BY p.des_RazonSocial";
        
        $resultado = mysqli_query($cn, $sql);
        $datos = [];
        
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $datos[] = $fila;
            }
        }

        return [
            'success' => true,
            'data' => $datos
        ];
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener proveedores del producto: ' . $e->getMessage()
        ];
    }
}

// Función para actualizar estado del requerimiento a 'Solicitado'
function actualizarEstadoSolicitado($cn, $datos) {
    try {
        $idReqEvaluacion = isset($datos['idReqEvaluacion']) ? $datos['idReqEvaluacion'] : null;
        
        if (!$idReqEvaluacion) {
            return [
                'success' => false,
                'message' => 'Falta el ID del requerimiento'
            ];
        }

        $idReqEvaluacion = mysqli_real_escape_string($cn, $idReqEvaluacion);

        $sql = "UPDATE t407RequerimientoEvaluado 
                SET Estado = 'Solicitado' 
                WHERE Id_ReqEvaluacion = '$idReqEvaluacion'";
        
        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Estado actualizado a Solicitado correctamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar estado: ' . mysqli_error($cn)
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al actualizar estado: ' . $e->getMessage()
        ];
    }
}

// Función para actualizar estado de solicitudes de Pendiente a Enviado
function actualizarEstadoSolicitudesEnviadas($cn) {
    try {
        $sql = "UPDATE t100Solicitud_Cotizacion_Proveedor 
                SET Estado = 'Enviado', 
                    FechaEnvio = NOW() 
                WHERE Estado = 'Pendiente'";
        
        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado) {
            $filasActualizadas = mysqli_affected_rows($cn);
            
            return [
                'success' => true,
                'message' => "Se actualizaron $filasActualizadas solicitudes a estado Enviado",
                'filasActualizadas' => $filasActualizadas
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al actualizar estado: ' . mysqli_error($cn)
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al actualizar estado de solicitudes: ' . $e->getMessage()
        ];
    }
}

// Obtener solicitudes de cotización enviadas desde BD
function obtenerSolicitudesEnviadas($cn) {
    try {
        $sql = "SELECT 
                    s.IDsolicitud,
                    s.Id_ReqEvaluacion,
                    s.RUC,
                    s.Empresa,
                    s.Correo,
                    s.RutaPDF,
                    COUNT(d.Id_Producto) AS Productos
                FROM 
                    t100Solicitud_Cotizacion_Proveedor s
                LEFT JOIN 
                    t101Detalle_Solicitud_Cotizacion_Proveedor d 
                    ON s.IDsolicitud = d.IDsolicitud
                WHERE 
                    s.Estado = 'Enviado'
                GROUP BY 
                    s.IDsolicitud, s.Id_ReqEvaluacion, s.RUC, s.Empresa, s.Correo, s.RutaPDF
                ORDER BY s.FechaEnvio DESC";

        $resultado = mysqli_query($cn, $sql);
        $datos = [];
        
        if ($resultado) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $datos[] = $fila;
            }
        }
        
        return [ 'success' => true, 'data' => $datos ];
    } catch (Exception $e) {
        return [ 'success' => false, 'message' => 'Error al obtener solicitudes: ' . $e->getMessage() ];
    }
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_responsable':
        echo json_encode(obtenerResponsable($cn));
        break;
        
    case 'obtener_requerimientos_evaluados':
        echo json_encode(obtenerRequerimientosEvaluados($cn));
        break;
        
    case 'obtener_productos_requerimiento':
        $idRequerimiento = isset($_GET['idRequerimiento']) ? $_GET['idRequerimiento'] : null;
        echo json_encode(obtenerProductosRequerimiento($cn, $idRequerimiento));
        break;
        
    case 'obtener_proveedores':
        $idRequerimiento = isset($_GET['idRequerimiento']) ? $_GET['idRequerimiento'] : null;
        echo json_encode(obtenerProveedores($cn, $idRequerimiento));
        break;

    case 'obtener_proveedores_por_producto':
        $idProducto = isset($_GET['idProducto']) ? $_GET['idProducto'] : null;
        echo json_encode(obtenerProveedoresPorProducto($cn, $idProducto));
        break;

    case 'obtener_solicitudes_enviadas':
        echo json_encode(obtenerSolicitudesEnviadas($cn));
        break;
        
    case 'actualizar_estado_solicitado':
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(actualizarEstadoSolicitado($cn, $datos));
        break;
    
    case 'actualizar_estado_solicitudes_enviadas':
        echo json_encode(actualizarEstadoSolicitudesEnviadas($cn));
        break;
        
    case 'generar_solicitud_por_proveedor':
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        
        try {
            $idReq = mysqli_real_escape_string($cn, $datos['idReqEvaluacion']);
            $ruc = mysqli_real_escape_string($cn, $datos['ruc']);
            $empresa = mysqli_real_escape_string($cn, $datos['empresa']);
            $correo = mysqli_real_escape_string($cn, $datos['correo']);
            
            // Llamar al stored procedure
            $sql = "CALL sp_GenerarSolicitudCotizacionPorProveedor('$idReq', '$ruc', '$empresa', '$correo')";
            $resultado = mysqli_query($cn, $sql);
            
            if ($resultado) {
                $result = mysqli_fetch_assoc($resultado);
                
                // Liberar resultados adicionales
                while (mysqli_more_results($cn)) {
                    mysqli_next_result($cn);
                    if ($res = mysqli_store_result($cn)) {
                        mysqli_free_result($res);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => $result['Mensaje'],
                    'idSolicitud' => $result['IDsolicitud_Generado'],
                    'productosInsertados' => $result['Productos_Insertados']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al ejecutar procedimiento: ' . mysqli_error($cn)
                ]);
            }
            
        } catch(Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'enviar_correos_cotizacion':
        require_once(__DIR__ . '/enviar_email_cotizacion.php');
        echo json_encode(procesarYEnviarSolicitudesPendientes($cn));
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
        break;
}

// Cerrar conexión
mysqli_close($cn);
?>
