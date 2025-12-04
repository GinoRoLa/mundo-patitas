<?php
// Controlador para la funcionalidad de Emitir Nota de Caja para Delivery
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

// ⚠️ NOTA: Como mysqli no usa PDO, adaptamos las funciones
// Mantendremos las funciones pero usando mysqli en lugar de PDO

// Función para obtener datos del responsable de caja
function obtenerResponsable($cn) {
    try {
        $sql = "SELECT 
                    id_Trabajador, 
                    des_nombreTrabajador, 
                    des_apepatTrabajador
                FROM t16CatalogoTrabajadores
                WHERE id_Trabajador = 50001";
        
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
                'message' => 'No se encontró el responsable de caja'
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al obtener datos del responsable: ' . $e->getMessage()
        ];
    }
}

// Función para buscar datos del repartidor
function buscarRepartidor($cn, $idRepartidor) {
    try {
        if (!$idRepartidor) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idRepartidor'
            ];
        }

        $idRepartidor = mysqli_real_escape_string($cn, $idRepartidor);

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
                    t16.id_Trabajador = '$idRepartidor'
                    AND t40.Estado = 'Despachada'
                GROUP BY 
                    t16.DNITrabajador,
                    t16.des_nombreTrabajador,
                    t16.des_apepatTrabajador,
                    t40.Id_OrdenAsignacion";
        
        $resultado = mysqli_query($cn, $sql);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $datos = mysqli_fetch_assoc($resultado);
            return [
                'success' => true,
                'data' => [
                    'DNI' => $datos['DNITrabajador'],
                    'Nombre' => $datos['des_nombreTrabajador'],
                    'ApellidoPaterno' => $datos['des_apepatTrabajador'],
                    'IdOrdenAsignacion' => $datos['Id_OrdenAsignacion'],
                    'TotalOrdenes' => $datos['Cantidad_Detalles']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Repartidor no encontrado o sin Asignación de Reparto en estado Despachada'
            ];
        }
    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al buscar repartidor: ' . $e->getMessage()
        ];
    }
}

// Función para obtener detalle de contra entregas
function obtenerDetalleContraEntregas($cn, $idOrdenAsignacion) {
    try {
        if (!$idOrdenAsignacion) {
            return [
                'success' => false,
                'message' => 'Falta el parámetro idOrdenAsignacion'
            ];
        }

        $idOrdenAsignacion = mysqli_real_escape_string($cn, $idOrdenAsignacion);

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
                    t40.Id_OrdenAsignacion = '$idOrdenAsignacion'";
        
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
            'message' => 'Error al obtener detalle de contra entregas: ' . $e->getMessage()
        ];
    }
}

// Función para generar nota de caja
function generarNotaCaja($cn, $datos) {
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

        // Escapar datos
        $idResponsable = mysqli_real_escape_string($cn, $idResponsable);
        $idRepartidor = mysqli_real_escape_string($cn, $idRepartidor);
        $idOrdenAsignacion = mysqli_real_escape_string($cn, $idOrdenAsignacion);
        $totalContraEntregas = mysqli_real_escape_string($cn, $totalContraEntregas);
        $totalVuelto = mysqli_real_escape_string($cn, $totalVuelto);

        $sql = "INSERT INTO t28Nota_caja 
                (IDResponsableCaja, IDRepartidor, IDAsignacionReparto, TotalContraEntrega, VueltoTotal)
                VALUES
                ('$idResponsable', '$idRepartidor', '$idOrdenAsignacion', '$totalContraEntregas', '$totalVuelto')";
        
        $resultado = mysqli_query($cn, $sql);
        
        if (!$resultado) {
            throw new Exception('Error al insertar nota de caja: ' . mysqli_error($cn));
        }
        
        $idNotaCaja = mysqli_insert_id($cn);

        // ✅ PASO 1: GENERAR PDF AUTOMÁTICAMENTE
        require_once(__DIR__ . '/generar_pdf_nota_caja.php');
        $resultadoPDF = generarPDFNotaCaja($cn, $idNotaCaja);
        
        if (!$resultadoPDF['success']) {
            error_log('Error al generar PDF: ' . $resultadoPDF['message']);
        }

        // ✅ PASO 2: OBTENER EMAILS
        $sqlEmails = "SELECT 
                rep.email AS EmailRepartidor,
                rep.des_nombreTrabajador AS NombreRepartidor,
                rep.des_apepatTrabajador AS ApellidoRepartidor,
                resp.email AS EmailResponsable,
                resp.des_nombreTrabajador AS NombreResponsable,
                resp.des_apepatTrabajador AS ApellidoResponsable
            FROM 
                t28Nota_caja nc
            JOIN 
                t16catalogotrabajadores rep 
                ON nc.IDRepartidor = rep.id_Trabajador
            JOIN 
                t16catalogotrabajadores resp 
                ON nc.IDResponsableCaja = resp.id_Trabajador
            WHERE 
                nc.IDNotaCaja = '$idNotaCaja'";
        
        $resultadoEmails = mysqli_query($cn, $sqlEmails);
        $datosEmails = mysqli_fetch_assoc($resultadoEmails);

        // ✅ PASO 3: ENVIAR EMAIL
        $emailEnviado = false;
        $mensajeEmail = '';
        $correosEnviados = [];
        
        if ($datosEmails && !empty($datosEmails['EmailRepartidor'])) {
            require_once(__DIR__ . '/enviar_email_nota_caja.php');
            
            $rutaPDF = __DIR__ . '/../' . $resultadoPDF['rutaRelativa'];
            $nombreArchivo = $resultadoPDF['nombreArchivo'];
            $correoRepartidor = $datosEmails['EmailRepartidor'];
            $nombreRepartidor = $datosEmails['NombreRepartidor'] . ' ' . $datosEmails['ApellidoRepartidor'];
            $correoResponsable = !empty($datosEmails['EmailResponsable']) ? $datosEmails['EmailResponsable'] : null;
            $nombreResponsable = $datosEmails['NombreResponsable'] . ' ' . $datosEmails['ApellidoResponsable'];
            
            $resultadoEmail = enviarEmailNotaCaja(
                $rutaPDF,
                $nombreArchivo,
                $correoRepartidor,
                $nombreRepartidor,
                $idNotaCaja,
                $correoResponsable,
                $nombreResponsable
            );
            
            // ⏱️ DELAY: Esperar 2 segundos para asegurar envío completo del email
            sleep(2);
            
            if ($resultadoEmail['success']) {
                $emailEnviado = true;
                $correosEnviados[] = $correoRepartidor;
                if ($correoResponsable) {
                    $correosEnviados[] = $correoResponsable;
                }
            } else {
                $mensajeEmail = $resultadoEmail['message'];
            }
        } else {
            $mensajeEmail = 'El repartidor no tiene correo electrónico registrado';
        }

        return [
            'success' => true,
            'message' => 'Nota de caja generada exitosamente',
            'idNotaCaja' => $idNotaCaja,
            'pdfGenerado' => $resultadoPDF['success'],
            'emailEnviado' => $emailEnviado,
            'correosEnviados' => $correosEnviados,
            'mensajeEmail' => $mensajeEmail
        ];

    } catch(Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al generar nota de caja: ' . $e->getMessage()
        ];
    }
}

// Función para obtener notas de caja generadas
function obtenerNotasCajaGeneradas($cn) {
    try {
        $sql = "SELECT 
                    IDNotaCaja,
                    IDResponsableCaja,
                    IDRepartidor,
                    IDAsignacionReparto,
                    TotalContraEntrega,
                    VueltoTotal,
                    FechaEmision,
                    RutaPDF
                FROM t28Nota_caja
                ORDER BY FechaEmision DESC";
        
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
            'message' => 'Error al obtener notas de caja: ' . $e->getMessage()
        ];
    }
}

// Procesar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'obtener_responsable':
        echo json_encode(obtenerResponsable($cn));
        break;
        
    case 'buscar_repartidor':
        $idRepartidor = isset($_GET['idRepartidor']) ? $_GET['idRepartidor'] : null;
        echo json_encode(buscarRepartidor($cn, $idRepartidor));
        break;
        
    case 'obtener_detalle_contra_entregas':
        $idOrdenAsignacion = isset($_GET['idOrdenAsignacion']) ? $_GET['idOrdenAsignacion'] : null;
        echo json_encode(obtenerDetalleContraEntregas($cn, $idOrdenAsignacion));
        break;
        
    case 'generar_nota_caja':
        $json = file_get_contents('php://input');
        $datos = json_decode($json, true);
        echo json_encode(generarNotaCaja($cn, $datos));
        break;
        
    case 'obtener_notas_caja_generadas':
        echo json_encode(obtenerNotasCajaGeneradas($cn));
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
