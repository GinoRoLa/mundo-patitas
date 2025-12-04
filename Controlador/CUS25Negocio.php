<?php
// Controlador para la funcionalidad de consolidación de entrega
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

// Función para obtener datos del repartidor
function obtenerRepartidor($cn) {
    try {
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50008";
        
        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $repartidor = mysqli_fetch_assoc($resultado);
            return [
                'success' => true,
                'data' => $repartidor
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No se encontró el repartidor'
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del repartidor: ' . $e->getMessage()
        ];
    }
}

// Función para obtener direcciones con órdenes de pedido
function obtenerDirecciones($cn, $idTrabajador) {
    try {
        if (!$idTrabajador) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idTrabajador'
            ];
        }

        $idTrabajador = mysqli_real_escape_string($cn, $idTrabajador);

        $sql = "SELECT 
                    t02.Id_OrdenPedido AS OrdenPedido,
                    t72.NumeroTexto AS GuiaRemision,
                    t77.DescNombre AS Distrito,
                    t71.DireccionSnap AS Direccion
                FROM t16CatalogoTrabajadores AS t16
                JOIN t79AsignacionRepartidorVehiculo AS t79 
                    ON t16.id_Trabajador = t79.Id_Trabajador
                JOIN t40OrdenAsignacionReparto AS t40
                    ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
                JOIN t401DetalleAsignacionReparto AS t401
                    ON t40.Id_OrdenAsignacion = t401.Id_OrdenAsignacion
                JOIN t59OrdenServicioEntrega AS t59
                    ON t401.Id_OSE = t59.Id_OSE
                JOIN t02OrdenPedido AS t02
                    ON t59.Id_OrdenPedido = t02.Id_OrdenPedido
                JOIN t93guia_ordenpedido AS t93
                    ON t02.Id_OrdenPedido = t93.Id_OrdenPedido
                JOIN t72GuiaRemision AS t72
                    ON t93.Id_Guia = t72.Id_Guia
                JOIN t71OrdenDirecEnvio AS t71
                    ON t02.Id_OrdenPedido = t71.Id_OrdenPedido
                JOIN t77DistritoEnvio AS t77
                    ON t71.Id_Distrito = t77.Id_Distrito
                WHERE t16.id_Trabajador = '$idTrabajador' 
                AND t02.Estado = 'En Reparto'";

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
            'message' => 'Error al obtener direcciones: ' . $e->getMessage()
        ];
    }
}

// Función para obtener productos por orden de pedido
function obtenerProductosPorOrden($cn, $idOrdenPedido) {
    try {
        if (!$idOrdenPedido) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenPedido'
            ];
        }

        $idOrdenPedido = mysqli_real_escape_string($cn, $idOrdenPedido);

        $sql = "SELECT 
                    t18.NombreProducto,
                    t60.Cantidad
                FROM t60DetOrdenPedido AS t60
                JOIN t18CatalogoProducto AS t18
                    ON t60.t18CatalogoProducto_Id_Producto = t18.Id_Producto
                WHERE t60.t02OrdenPedido_Id_OrdenPedido = '$idOrdenPedido'";

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
            'message' => 'Error al obtener productos: ' . $e->getMessage()
        ];
    }
}

// Función para obtener datos del destinatario por orden de pedido
function obtenerDestinatarioPorOrden($cn, $idOrdenPedido) {
    try {
        if (!$idOrdenPedido) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenPedido'
            ];
        }

        $idOrdenPedido = mysqli_real_escape_string($cn, $idOrdenPedido);

        $sql = "SELECT 
                    ReceptorDniSnap,
                    NombreContactoSnap,
                    TelefonoSnap,
                    DireccionSnap
                FROM t71OrdenDirecEnvio
                WHERE Id_OrdenPedido = '$idOrdenPedido'";

        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $destinatario = mysqli_fetch_assoc($resultado);
            return [
                'success' => true,
                'data' => $destinatario
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No se encontró información del destinatario'
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del destinatario: ' . $e->getMessage()
        ];
    }
}

// Función para obtener Id_OrdenAsignacion por repartidor
function obtenerOrdenAsignacionPorRepartidor($cn, $idTrabajador) {
    try {
        if (!$idTrabajador) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idTrabajador'
            ];
        }

        $idTrabajador = mysqli_real_escape_string($cn, $idTrabajador);

        $sql = "SELECT t40.Id_OrdenAsignacion
                FROM t16CatalogoTrabajadores AS t16
                JOIN t79AsignacionRepartidorVehiculo AS t79
                    ON t16.id_Trabajador = t79.Id_Trabajador
                JOIN t40OrdenAsignacionReparto AS t40
                    ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
                WHERE t16.id_Trabajador = '$idTrabajador'";

        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $row = mysqli_fetch_assoc($resultado);
            return [ 'success' => true, 'data' => $row['Id_OrdenAsignacion'] ];
        }

        return [ 'success' => true, 'data' => null ];

    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener orden asignada: ' . $e->getMessage()
        ];
    }
}

// Función para finalizar orden de asignación
function finalizarOrdenAsignacion($cn, $idOrdenAsignacion) {
    try {
        if (!$idOrdenAsignacion) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenAsignacion'
            ];
        }

        $idOrdenAsignacion = mysqli_real_escape_string($cn, $idOrdenAsignacion);

        $sql = "UPDATE t40OrdenAsignacionReparto 
                SET Estado = 'Finalizado' 
                WHERE Id_OrdenAsignacion = '$idOrdenAsignacion'";

        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Orden de asignación finalizada exitosamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al finalizar orden: ' . mysqli_error($cn)
            ];
        }

    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al finalizar orden de asignación: ' . $e->getMessage()
        ];
    }
}

// Función para procesar el registro de consolidación
function procesarRegistroConsolidacion($cn, $datos) {
    try {
        // Validar datos requeridos
        if (!$datos['idOrdenPedido']) {
            return [
                'success' => false,
                'message' => 'Falta el ID de la orden de pedido'
            ];
        }

        // Crear directorio de uploads si no existe
        $uploadDir = 'C:/xampp/htdocs/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Procesar archivos de imágenes
        $fotoDireccion = null;
        $fotoDni = null;
        $fotoEntrega = null;

        // Procesar foto de dirección
        if (isset($_FILES['fotoDireccion']) && $_FILES['fotoDireccion']['error'] === UPLOAD_ERR_OK) {
            $fotoDireccion = procesarArchivo($_FILES['fotoDireccion'], $uploadDir, 'direccion');
        }

        // Procesar foto de DNI
        if (isset($_FILES['fotoDni']) && $_FILES['fotoDni']['error'] === UPLOAD_ERR_OK) {
            $fotoDni = procesarArchivo($_FILES['fotoDni'], $uploadDir, 'dni');
        }

        // Procesar foto de entrega
        if (isset($_FILES['fotoEntrega']) && $_FILES['fotoEntrega']['error'] === UPLOAD_ERR_OK) {
            $fotoEntrega = procesarArchivo($_FILES['fotoEntrega'], $uploadDir, 'entrega');
        }

        // Preparar parámetros para el procedimiento almacenado
        $idOrdenPedido = mysqli_real_escape_string($cn, $datos['idOrdenPedido']);
        $estado = mysqli_real_escape_string($cn, $datos['estadoEntrega']);
        $observaciones = mysqli_real_escape_string($cn, $datos['observaciones']);

        // Construir la llamada con LOAD_FILE correctamente
        $fotoDireccionParam = $fotoDireccion ? "LOAD_FILE('$fotoDireccion')" : "NULL";
        $fotoDniParam = $fotoDni ? "LOAD_FILE('$fotoDni')" : "NULL";
        $fotoEntregaParam = $fotoEntrega ? "LOAD_FILE('$fotoEntrega')" : "NULL";

        // Construir la llamada al procedimiento almacenado
        $sql = "CALL sp_cus25_RegistrarConsolidacion(
            '$idOrdenPedido',
            $fotoDireccionParam,
            $fotoDniParam,
            $fotoEntregaParam,
            '$estado',
            '$observaciones'
        )";

        $resultado = mysqli_query($cn, $sql);
        
        // Liberar resultados adicionales del stored procedure
        while (mysqli_more_results($cn)) {
            mysqli_next_result($cn);
            if ($res = mysqli_store_result($cn)) {
                mysqli_free_result($res);
            }
        }
        
        if ($resultado) {
            return [
                'success' => true,
                'message' => 'Consolidación registrada exitosamente'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error al ejecutar procedimiento: ' . mysqli_error($cn)
            ];
        }

    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al procesar el registro: ' . $e->getMessage()
        ];
    }
}

// Función auxiliar para procesar archivos
function procesarArchivo($archivo, $uploadDir, $tipo) {
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombreArchivo = $tipo . '_' . time() . '_' . uniqid() . '.' . $extension;
    $rutaCompleta = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        // Retornar solo la ruta, sin LOAD_FILE
        return str_replace('\\', '/', $rutaCompleta);
    }
    
    return null;
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_repartidor':
        echo json_encode(obtenerRepartidor($cn));
        break;
        
    case 'obtener_direcciones':
        $idTrabajador = isset($_GET['idTrabajador']) ? $_GET['idTrabajador'] : null;
        echo json_encode(obtenerDirecciones($cn, $idTrabajador));
        break;
        
    case 'obtener_productos':
        $idOrdenPedido = isset($_GET['idOrdenPedido']) ? $_GET['idOrdenPedido'] : null;
        echo json_encode(obtenerProductosPorOrden($cn, $idOrdenPedido));
        break;
        
    case 'obtener_destinatario':
        $idOrdenPedido = isset($_GET['idOrdenPedido']) ? $_GET['idOrdenPedido'] : null;
        echo json_encode(obtenerDestinatarioPorOrden($cn, $idOrdenPedido));
        break;
        
    case 'obtener_orden_asignacion':
        $idTrabajador = isset($_GET['idTrabajador']) ? $_GET['idTrabajador'] : null;
        echo json_encode(obtenerOrdenAsignacionPorRepartidor($cn, $idTrabajador));
        break;
        
    case 'finalizar_orden_asignacion':
        $idOrdenAsignacion = isset($_GET['idOrdenAsignacion']) ? $_GET['idOrdenAsignacion'] : null;
        echo json_encode(finalizarOrdenAsignacion($cn, $idOrdenAsignacion));
        break;
        
    case 'procesar_entrega':
        // Procesar datos del formulario POST
        $datos = [
            'idOrdenPedido' => isset($_POST['idOrdenPedido']) ? $_POST['idOrdenPedido'] : null,
            'estadoEntrega' => isset($_POST['estadoEntrega']) ? $_POST['estadoEntrega'] : null,
            'observaciones' => isset($_POST['observaciones']) ? $_POST['observaciones'] : null
        ];
        echo json_encode(procesarRegistroConsolidacion($cn, $datos));
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
