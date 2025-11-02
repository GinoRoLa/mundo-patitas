<?php
// Controlador para la funcionalidad de consolidación de entrega
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

// Función para obtener datos del repartidor
function obtenerRepartidor($pdo) {
    try {
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50010";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $repartidor = $stmt->fetch();
        
        if ($repartidor) {
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
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del repartidor: ' . $e->getMessage()
        ];
    }
}

// Función para obtener direcciones con órdenes de pedido
function obtenerDirecciones($pdo, $idTrabajador) {
    try {
        if (!$idTrabajador) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idTrabajador'
            ];
        }

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
                WHERE t16.id_Trabajador = :idTrabajador 
                AND t02.Estado = 'En Reparto'";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idTrabajador', $idTrabajador);
        $stmt->execute();
        $resultados = $stmt->fetchAll();

        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener direcciones: ' . $e->getMessage()
        ];
    }
}

// Función para obtener productos por orden de pedido
function obtenerProductosPorOrden($pdo, $idOrdenPedido) {
    try {
        if (!$idOrdenPedido) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenPedido'
            ];
        }

        $sql = "SELECT 
                    t18.NombreProducto,
                    t60.Cantidad
                FROM t60DetOrdenPedido AS t60
                JOIN t18CatalogoProducto AS t18
                    ON t60.t18CatalogoProducto_Id_Producto = t18.Id_Producto
                WHERE t60.t02OrdenPedido_Id_OrdenPedido = :idOrdenPedido";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idOrdenPedido', $idOrdenPedido);
        $stmt->execute();
        $resultados = $stmt->fetchAll();

        return [
            'success' => true,
            'data' => $resultados
        ];
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener productos: ' . $e->getMessage()
        ];
    }
}

// Función para obtener datos del destinatario por orden de pedido
function obtenerDestinatarioPorOrden($pdo, $idOrdenPedido) {
    try {
        if (!$idOrdenPedido) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenPedido'
            ];
        }

        $sql = "SELECT 
                    ReceptorDniSnap,
                    NombreContactoSnap,
                    TelefonoSnap,
                    DireccionSnap
                FROM t71OrdenDirecEnvio
                WHERE Id_OrdenPedido = :idOrdenPedido";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idOrdenPedido', $idOrdenPedido);
        $stmt->execute();
        $resultado = $stmt->fetch();

        if ($resultado) {
            return [
                'success' => true,
                'data' => $resultado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No se encontró información del destinatario'
            ];
        }
    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del destinatario: ' . $e->getMessage()
        ];
    }
}

// Función para obtener Id_OrdenAsignacion por repartidor
function obtenerOrdenAsignacionPorRepartidor($pdo, $idTrabajador) {
    try {
        if (!$idTrabajador) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idTrabajador'
            ];
        }

        $sql = "SELECT t40.Id_OrdenAsignacion
                FROM t16CatalogoTrabajadores AS t16
                JOIN t79AsignacionRepartidorVehiculo AS t79
                    ON t16.id_Trabajador = t79.Id_Trabajador
                JOIN t40OrdenAsignacionReparto AS t40
                    ON t79.Id_AsignacionRepartidorVehiculo = t40.Id_AsignacionRepartidorVehiculo
                WHERE t16.id_Trabajador = :idTrabajador";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idTrabajador', $idTrabajador);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row && isset($row['Id_OrdenAsignacion'])) {
            return [ 'success' => true, 'data' => $row['Id_OrdenAsignacion'] ];
        }

        return [ 'success' => true, 'data' => null ];

    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener orden asignada: ' . $e->getMessage()
        ];
    }
}

// Función para finalizar orden de asignación
function finalizarOrdenAsignacion($pdo, $idOrdenAsignacion) {
    try {
        if (!$idOrdenAsignacion) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro requerido: idOrdenAsignacion'
            ];
        }

        $sql = "UPDATE t40OrdenAsignacionReparto 
                SET Estado = 'Finalizado' 
                WHERE Id_OrdenAsignacion = :idOrdenAsignacion";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idOrdenAsignacion', $idOrdenAsignacion);
        $stmt->execute();

        return [
            'success' => true,
            'message' => 'Orden de asignación finalizada exitosamente'
        ];

    } catch(PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error al finalizar orden de asignación: ' . $e->getMessage()
        ];
    }
}

// Función para procesar el registro de consolidación
function procesarRegistroConsolidacion($pdo, $datos) {
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
        $idOrdenPedido = $datos['idOrdenPedido'];
        $estado = $datos['estadoEntrega'];
        $observaciones = $datos['observaciones'];

        // Construir la llamada al procedimiento almacenado
        $sql = "CALL sp_cus25_RegistrarConsolidacion(
            :idOrdenPedido,
            :fotoDireccion,
            :fotoDni,
            :fotoEntrega,
            :estado,
            :observaciones
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idOrdenPedido', $idOrdenPedido);
        $stmt->bindParam(':fotoDireccion', $fotoDireccion);
        $stmt->bindParam(':fotoDni', $fotoDni);
        $stmt->bindParam(':fotoEntrega', $fotoEntrega);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':observaciones', $observaciones);

        $stmt->execute();

        return [
            'success' => true,
            'message' => 'Consolidación registrada exitosamente'
        ];

    } catch(PDOException $e) {
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
        return "LOAD_FILE('" . str_replace('\\', '/', $rutaCompleta) . "')";
    }
    
    return null;
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_repartidor':
        echo json_encode(obtenerRepartidor($pdo));
        break;
        
    case 'obtener_direcciones':
        $idTrabajador = isset($_GET['idTrabajador']) ? $_GET['idTrabajador'] : null;
        echo json_encode(obtenerDirecciones($pdo, $idTrabajador));
        break;
        
    case 'obtener_productos':
        $idOrdenPedido = isset($_GET['idOrdenPedido']) ? $_GET['idOrdenPedido'] : null;
        echo json_encode(obtenerProductosPorOrden($pdo, $idOrdenPedido));
        break;
        
    case 'obtener_destinatario':
        $idOrdenPedido = isset($_GET['idOrdenPedido']) ? $_GET['idOrdenPedido'] : null;
        echo json_encode(obtenerDestinatarioPorOrden($pdo, $idOrdenPedido));
        break;
        
    case 'obtener_orden_asignacion':
        $idTrabajador = isset($_GET['idTrabajador']) ? $_GET['idTrabajador'] : null;
        echo json_encode(obtenerOrdenAsignacionPorRepartidor($pdo, $idTrabajador));
        break;
        
    case 'finalizar_orden_asignacion':
        $idOrdenAsignacion = isset($_GET['idOrdenAsignacion']) ? $_GET['idOrdenAsignacion'] : null;
        echo json_encode(finalizarOrdenAsignacion($pdo, $idOrdenAsignacion));
        break;
        
    case 'procesar_entrega':
        // Procesar datos del formulario POST
        $datos = [
            'idOrdenPedido' => isset($_POST['idOrdenPedido']) ? $_POST['idOrdenPedido'] : null,
            'estadoEntrega' => isset($_POST['estadoEntrega']) ? $_POST['estadoEntrega'] : null,
            'observaciones' => isset($_POST['observaciones']) ? $_POST['observaciones'] : null
        ];
        echo json_encode(procesarRegistroConsolidacion($pdo, $datos));
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
        break;
}
?>