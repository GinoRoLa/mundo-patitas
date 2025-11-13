<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailCotizacion($rutaArchivoPDF, $nombreArchivo, $correoProveedor, $razonSocial, $idSolicitud) {
    $mail = new PHPMailer(true);
    
    try {
        // ============================================
        // CONFIGURACIÓN DEL SERVIDOR SMTP
        // ============================================
        // Para Gmail:
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mundopatitas.venta@gmail.com';
        $mail->Password   = 'fczx jhga rkop uekf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Para otros servidores SMTP, ajusta estos valores:
        // $mail->Host = 'smtp.tuservidor.com';
        // $mail->Port = 465; // o 587
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // o STARTTLS
        
        $mail->CharSet = 'UTF-8';
        
        // ============================================
        // REMITENTE Y DESTINATARIO
        // ============================================
        $mail->setFrom('mundopatitas.venta@gmail.com', 'Mundo Patitas - Compras'); // ⚠️ CAMBIAR
        $mail->addAddress($correoProveedor, $razonSocial);
        
        // Copia opcional para el responsable de compras
        // $mail->addCC('responsable_compras@empresa.com');
        
        // ============================================
        // ADJUNTAR PDF
        // ============================================
        if (!file_exists($rutaArchivoPDF)) {
            throw new Exception('El archivo PDF no existe: ' . $rutaArchivoPDF);
        }
        $mail->addAttachment($rutaArchivoPDF, $nombreArchivo);
        
        // ============================================
        // ADJUNTAR EXCEL (PLANTILLA DE RESPUESTA)
        // ============================================
        $rutaExcel = __DIR__ . '/../src/Documentos/Ejemplo de Respuesta a la Solicitud.xlsx';
        if (file_exists($rutaExcel)) {
            $mail->addAttachment($rutaExcel, 'Ejemplo de Respuesta a la Solicitud.xlsx');
        } else {
            // Log del error pero continúa el envío (opcional)
            error_log('⚠️ Advertencia: No se encontró el archivo Excel en: ' . $rutaExcel);
        }
        
        // ============================================
        // CONTENIDO DEL EMAIL
        // ============================================
        $mail->isHTML(true);
        $mail->Subject = utf8_decode('Solicitud de Cotización - ID: ' . $idSolicitud);
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #007bff;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background-color: #f8f9fa;
                    padding: 30px;
                    border: 1px solid #dee2e6;
                }
                .footer {
                    background-color: #343a40;
                    color: white;
                    padding: 15px;
                    text-align: center;
                    font-size: 12px;
                    border-radius: 0 0 5px 5px;
                }
                .highlight {
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 10px;
                    margin: 15px 0;
                }
                .btn {
                    display: inline-block;
                    background-color: #28a745;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 15px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Solicitud de Cotización</h2>
                </div>
                
                <div class="content">
                    <p>Estimado(a) proveedor <strong>' . htmlspecialchars($razonSocial) . '</strong>,</p>
                    
                    <p>Por medio de la presente, solicitamos su cotización para los productos detallados en el documento adjunto.</p>
                    
                    <div class="highlight">
                        <strong>📎 Documentos adjuntos:</strong><br>
                        1️⃣ ' . htmlspecialchars($nombreArchivo) . ' (Solicitud de cotización)<br>
                        2️⃣ Ejemplo de Respuesta a la Solicitud.xlsx (Plantilla para responder)
                    </div>
                    
                    <p><strong>Información importante:</strong></p>
                    <ul>
                        <li>Revise cuidadosamente los productos y cantidades solicitadas</li>
                        <li>Incluya precios unitarios y totales</li>
                        <li>Indique tiempos de entrega</li>
                        <li>Especifique condiciones de pago</li>
                        <li>Respete los días hábiles indicados en el documento</li>
                        <li><strong>📊 Utilice la plantilla Excel adjunta para enviar su respuesta</strong></li>
                    </ul>
                    
                    <p>Para cualquier consulta o aclaración, no dude en contactarnos.</p>
                    
                    <p>Agradecemos de antemano su atención y quedamos a la espera de su pronta respuesta.</p>
                    
                    <p><strong>Atentamente,</strong><br>
                    Departamento de Compras<br>
                    Mundo Patitas</p>
                </div>
                
                <div class="footer">
                    <p>Este es un correo automático, por favor no responder a esta dirección.</p>
                    <p>Para consultas, contacte a: mundopatitas.venta@gmail.com</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Versión en texto plano (para clientes que no soportan HTML)
        $mail->AltBody = "Estimado(a) proveedor $razonSocial,\n\n"
                       . "Adjuntamos solicitud de cotización (ID: $idSolicitud).\n\n"
                       . "Por favor revise el documento adjunto y envíe su cotización antes de la fecha de cierre.\n\n"
                       . "Atentamente,\n"
                       . "Departamento de Compras - Mundo Patitas";
        
        // ============================================
        // ENVIAR EMAIL
        // ============================================
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Correo enviado exitosamente a ' . $correoProveedor
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al enviar correo: ' . $mail->ErrorInfo
        ];
    }
}

// ============================================
// FUNCIÓN PRINCIPAL: Procesar todas las solicitudes pendientes
// ============================================
function procesarYEnviarSolicitudesPendientes($pdo) {
    require_once(__DIR__ . '/generar_pdf_cotizacion.php');
    
    try {
        // Obtener todas las solicitudes pendientes
        $sql = "SELECT IDsolicitud 
                FROM t100Solicitud_Cotizacion_Proveedor 
                WHERE Estado = 'Pendiente'
                ORDER BY IDsolicitud";
        
        $stmt = $pdo->query($sql);
        $solicitudes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($solicitudes)) {
            return [
                'success' => false,
                'message' => 'No hay solicitudes pendientes para enviar'
            ];
        }
        
        $resultados = [];
        $exitosos = 0;
        $fallidos = 0;
        
        foreach ($solicitudes as $idSolicitud) {
            // 1. Generar PDF
            $resultadoPDF = generarPDFCotizacion($pdo, $idSolicitud);
            
            if (!$resultadoPDF['success']) {
                $fallidos++;
                $resultados[] = [
                    'idSolicitud' => $idSolicitud,
                    'estado' => 'error',
                    'mensaje' => 'Error al generar PDF: ' . $resultadoPDF['message']
                ];
                continue;
            }
            
            // 2. Enviar Email
            $resultadoEmail = enviarEmailCotizacion(
                $resultadoPDF['rutaArchivo'],
                $resultadoPDF['nombreArchivo'],
                $resultadoPDF['correoProveedor'],
                $resultadoPDF['razonSocial'],
                $idSolicitud
            );
            
            if ($resultadoEmail['success']) {
                
                $exitosos++;
                $resultados[] = [
                    'idSolicitud' => $idSolicitud,
                    'estado' => 'exitoso',
                    'proveedor' => $resultadoPDF['razonSocial'],
                    'correo' => $resultadoPDF['correoProveedor']
                ];
                
                // 4. Eliminar PDF temporal (opcional)
                // unlink($resultadoPDF['rutaArchivo']);
            } else {
                $fallidos++;
                $resultados[] = [
                    'idSolicitud' => $idSolicitud,
                    'estado' => 'error',
                    'mensaje' => $resultadoEmail['message']
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => "Proceso completado: $exitosos enviados, $fallidos fallidos",
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'detalles' => $resultados
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error en el proceso: ' . $e->getMessage()
        ];
    }
}
?>