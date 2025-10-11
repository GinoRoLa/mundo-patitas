<?php
/**
 * Servicio para envío de correos electrónicos
 * Ubicación: /Controlador/EmailService.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    // Configuración SMTP
    private const SMTP_HOST = 'smtp.gmail.com';
    private const SMTP_PORT = 587;
    private const SMTP_USER = 'mundopatitas.venta@gmail.com';
    private const SMTP_PASS = 'password';
    private const SMTP_FROM = 'mundopatitas.venta@gmail.com';
    private const SMTP_FROM_NAME = 'Mundo Patitas';

    /**
     * Crea y configura una instancia de PHPMailer
     */
    private static function crearMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        
        // Configuración del servidor
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = self::SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = self::SMTP_USER;
        $mail->Password   = self::SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = self::SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Opciones SSL para resolver problemas de certificado
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        // Remitente
        $mail->setFrom(self::SMTP_FROM, self::SMTP_FROM_NAME);
        
        return $mail;
    }

    /**
     * Envía un correo con adjuntos
     * 
     * @param string $toEmail Email del destinatario
     * @param string $toName Nombre del destinatario
     * @param string $subject Asunto del correo
     * @param string $bodyHTML Cuerpo del mensaje en HTML
     * @param array $adjuntos Array asociativo [nombre_archivo => ruta_archivo]
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function enviarConAdjuntos(
        string $toEmail, 
        string $toName, 
        string $subject, 
        string $bodyHTML, 
        array $adjuntos = []
    ): array {
        try {
            $mail = self::crearMailer();
            
            // Destinatario
            $mail->addAddress($toEmail, $toName ?: $toEmail);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHTML;
            
            // Adjuntos
            foreach ($adjuntos as $nombre => $ruta) {
                if (!file_exists($ruta)) {
                    throw new Exception("Archivo no encontrado: {$nombre} ({$ruta})");
                }
                $mail->addAttachment($ruta, $nombre);
            }
            
            // Enviar
            $mail->send();
            
            return ['success' => true, 'error' => null];
            
        } catch (\Throwable $e) {
            error_log("Error enviando correo a {$toEmail}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Genera el HTML del cuerpo del correo para guías de remisión
     */
    public static function generarHTMLGuias(
        string $nombreDestinatario,
        int $asignacionId,
        array $guiasInfo
    ): string {
        $total = count($guiasInfo);
        $lineas = [];
        
        foreach ($guiasInfo as $info) {
            $numero = htmlspecialchars($info['numero'] ?? '', ENT_QUOTES, 'UTF-8');
            $destino = htmlspecialchars($info['destino'] ?? '', ENT_QUOTES, 'UTF-8');
            $lineas[] = "<li><strong>{$numero}</strong> — {$destino}</li>";
        }
        
        $listaHTML = implode('', $lineas);
        $nombre = htmlspecialchars($nombreDestinatario, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .highlight {
            background: #fff;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin: 20px 0;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🐾 Mundo Patitas</h2>
        <p>Guías de Remisión</p>
    </div>
    
    <div class="content">
        <p>Hola <strong>{$nombre}</strong>,</p>
        
        <div class="highlight">
            <p style="margin: 0;">✅ Se han generado <strong>{$total} guía(s) de remisión</strong> para la asignación <strong>#{$asignacionId}</strong>.</p>
        </div>
        
        <p><strong>Documentos adjuntos:</strong></p>
        <ul>
            {$listaHTML}
        </ul>
        
        <p>Por favor, revisa los archivos PDF adjuntos a este correo. Cada guía contiene el detalle completo de los productos a entregar.</p>
        
        <div class="footer">
            <p><strong>Importante:</strong></p>
            <ul style="margin: 5px 0;">
                <li>Verifica que todos los datos sean correctos</li>
                <li>Lleva las guías impresas durante la entrega</li>
                <li>Solicita firma del receptor al entregar</li>
            </ul>
            
            <p style="margin-top: 20px;">
                Saludos,<br>
                <strong>Equipo Mundo Patitas</strong>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Envía correo con guías de remisión
     */
    public static function enviarGuias(
        string $toEmail,
        string $toName,
        int $asignacionId,
        array $guiasInfo,
        array $adjuntos
    ): array {
        $subject = "Guías de Remisión - Asignación #{$asignacionId}";
        $body = self::generarHTMLGuias($toName, $asignacionId, $guiasInfo);
        
        return self::enviarConAdjuntos($toEmail, $toName, $subject, $body, $adjuntos);
    }
}