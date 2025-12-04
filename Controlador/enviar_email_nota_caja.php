<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailNotaCaja($rutaArchivoPDF, $nombreArchivo, $correoRepartidor, $nombreRepartidor, $idNotaCaja, $correoResponsable = null, $nombreResponsable = null) {
    $mail = new PHPMailer(true);
    
    try {
        // ============================================
        // CONFIGURACIÓN DEL SERVIDOR SMTP
        // ============================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mundopatitas.venta@gmail.com';
        $mail->Password   = 'fczx jhga rkop uekf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->CharSet = 'UTF-8';
        
        // ============================================
        // REMITENTE Y DESTINATARIO
        // ============================================
        $mail->setFrom('mundopatitas.venta@gmail.com', 'Mundo Patitas - Caja');
        $mail->addAddress($correoRepartidor, $nombreRepartidor);
        
        // ✅ COPIA AL RESPONSABLE DE CAJA (si tiene email)
        if (!empty($correoResponsable)) {
            $mail->addCC($correoResponsable, $nombreResponsable);
        }
        
        // ============================================
        // ADJUNTAR PDF
        // ============================================
        if (!file_exists($rutaArchivoPDF)) {
            throw new Exception('El archivo PDF no existe: ' . $rutaArchivoPDF);
        }
        $mail->addAttachment($rutaArchivoPDF, $nombreArchivo);
        
        // ============================================
        // CONTENIDO DEL EMAIL
        // ============================================
        $mail->isHTML(true);
        $mail->Subject = utf8_decode('Nota de Caja para Delivery - ID: ' . $idNotaCaja);
        
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
                    background-color: #28a745;
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
                    background-color: #d4edda;
                    border-left: 4px solid #28a745;
                    padding: 10px;
                    margin: 15px 0;
                }
                .important-box {
                    background-color: #fff3cd;
                    border: 2px solid #ffc107;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Nota de Caja para Delivery</h2>
                </div>
                
                <div class="content">
                    <p>Estimado(a) repartidor(a) <strong>' . htmlspecialchars($nombreRepartidor) . '</strong>,</p>
                    
                    <p>Se adjunta la <strong>Nota de Caja</strong> correspondiente a las contra entregas realizadas en su última asignación de reparto.</p>
                    
                    <div class="highlight">
                        <strong>📄 Documento adjunto:</strong><br>
                        ' . htmlspecialchars($nombreArchivo) . '
                    </div>
                    
                    <div class="important-box">
                        <strong>⚠️ IMPORTANTE:</strong><br>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Revise cuidadosamente el detalle de contra entregas</li>
                            <li>Verifique que el <strong>vuelto total a conciliar</strong> coincida con el efectivo que debe entregar</li>
                            <li>Presente este documento al momento de realizar la conciliación en caja</li>
                            <li>Conserve una copia para sus registros</li>
                        </ul>
                    </div>
                    
                    <p>Para cualquier consulta o aclaración sobre esta nota de caja, no dude en contactarnos.</p>
                    
                    <p><strong>Atentamente,</strong><br>
                    Departamento de Caja<br>
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
        
        // Versión en texto plano
        $mail->AltBody = "Estimado(a) repartidor(a) $nombreRepartidor,\n\n"
                       . "Se adjunta la Nota de Caja (ID: $idNotaCaja) correspondiente a sus contra entregas.\n\n"
                       . "Por favor revise el documento adjunto y presente esta nota al momento de realizar la conciliación en caja.\n\n"
                       . "Atentamente,\n"
                       . "Departamento de Caja - Mundo Patitas";
        
        // ============================================
        // ENVIAR EMAIL
        // ============================================
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Correo enviado exitosamente a ' . $correoRepartidor
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al enviar correo: ' . $mail->ErrorInfo
        ];
    }
}

// ============================================
// FUNCIÓN: Procesar y enviar nota de caja al repartidor
// ============================================
function procesarYEnviarNotaCaja($pdo, $idNotaCaja) {
    require_once(__DIR__ . '/generar_pdf_nota_caja.php');
    
    try {
        // 1. Obtener datos del repartidor para conseguir su email
        $sqlRepartidor = "SELECT 
                nc.IDNotaCaja,
                nc.IDRepartidor,
                rep.des_nombreTrabajador AS NombreRepartidor,
                rep.des_apepatTrabajador AS ApellidoRepartidor,
                rep.email AS EmailRepartidor
            FROM 
                t28Nota_caja nc
            JOIN 
                t16catalogotrabajadores rep 
                ON nc.IDRepartidor = rep.id_Trabajador
            WHERE 
                nc.IDNotaCaja = :idNotaCaja";
        
        $stmt = $pdo->prepare($sqlRepartidor);
        $stmt->bindParam(':idNotaCaja', $idNotaCaja, PDO::PARAM_INT);
        $stmt->execute();
        $datosRepartidor = $stmt->fetch();
        
        if (!$datosRepartidor) {
            return [
                'success' => false,
                'message' => 'No se encontraron datos del repartidor'
            ];
        }
        
        // Validar que el repartidor tenga email
        if (empty($datosRepartidor['EmailRepartidor'])) {
            return [
                'success' => false,
                'message' => 'El repartidor no tiene un correo electrónico registrado'
            ];
        }
        
        // 2. Verificar que el PDF exista, si no, generarlo
        $sqlPDF = "SELECT RutaPDF FROM t28Nota_caja WHERE IDNotaCaja = :idNotaCaja";
        $stmtPDF = $pdo->prepare($sqlPDF);
        $stmtPDF->bindParam(':idNotaCaja', $idNotaCaja, PDO::PARAM_INT);
        $stmtPDF->execute();
        $notaCaja = $stmtPDF->fetch();
        
        $rutaPDF = null;
        $nombreArchivo = null;
        
        if (!empty($notaCaja['RutaPDF']) && file_exists(__DIR__ . '/../' . $notaCaja['RutaPDF'])) {
            // El PDF ya existe
            $rutaPDF = __DIR__ . '/../' . $notaCaja['RutaPDF'];
            $nombreArchivo = basename($rutaPDF);
        } else {
            // Generar el PDF
            $resultadoPDF = generarPDFNotaCaja($pdo, $idNotaCaja);
            
            if (!$resultadoPDF['success']) {
                return [
                    'success' => false,
                    'message' => 'Error al generar PDF: ' . $resultadoPDF['message']
                ];
            }
            
            $rutaPDF = $resultadoPDF['rutaArchivo'];
            $nombreArchivo = $resultadoPDF['nombreArchivo'];
        }
        
        // 3. Enviar Email
        $nombreCompleto = $datosRepartidor['NombreRepartidor'] . ' ' . $datosRepartidor['ApellidoRepartidor'];
        
        $resultadoEmail = enviarEmailNotaCaja(
            $rutaPDF,
            $nombreArchivo,
            $datosRepartidor['EmailRepartidor'],
            $nombreCompleto,
            $idNotaCaja
        );
        
        if ($resultadoEmail['success']) {
            return [
                'success' => true,
                'message' => 'Nota de caja enviada exitosamente al correo del repartidor',
                'correo' => $datosRepartidor['EmailRepartidor']
            ];
        } else {
            return [
                'success' => false,
                'message' => $resultadoEmail['message']
            ];
        }
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error en el proceso: ' . $e->getMessage()
        ];
    }
}
?>
