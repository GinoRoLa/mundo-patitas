<?php

/**
 * Servicio para envío de correos electrónicos
 * Ubicación: /Controlador/EmailService.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService{
    // Configuración SMTP
    private const SMTP_HOST = 'smtp.gmail.com';
    private const SMTP_PORT = 587;
    private const SMTP_USER = 'mundopatitas.venta@gmail.com';
    private const SMTP_PASS = 'fczx jhga rkop uekf';
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
        array $adjuntos = [],
        array $embebidos = []          // <-- NUEVO
    ): array {
        try {
            $mail = self::crearMailer();

            // Destinatario
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            // Embebidos (CID)
            foreach ($embebidos as $cid => $ruta) {
                if (!file_exists($ruta)) {
                    throw new Exception("Archivo embebido no encontrado: {$cid} ({$ruta})");
                }
                // tercer parámetro es nombre visible, opcional
                $mail->addEmbeddedImage($ruta, $cid, basename($ruta));
            }

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
        array $guiasInfo,
        ?string $logoCid = null        // <-- NUEVO
    ): string {
        $total = count($guiasInfo);
        $lineas = [];

        foreach ($guiasInfo as $info) {
            $numero  = htmlspecialchars($info['numero']  ?? '', ENT_QUOTES, 'UTF-8');
            $destino = htmlspecialchars($info['destino'] ?? '', ENT_QUOTES, 'UTF-8');
            $lineas[] = "<li><strong>{$numero}</strong> — {$destino}</li>";
        }

        $listaHTML = implode('', $lineas);
        $nombre = htmlspecialchars($nombreDestinatario, ENT_QUOTES, 'UTF-8');

        $logoHTML = $logoCid
            ? '<img src="cid:' . htmlspecialchars($logoCid, ENT_QUOTES, 'UTF-8') . '" alt="Logo Mundo Patitas" style="width:70px;height:70px;display:block;margin:0 auto 8px;" />'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
  /* Fondo general suave para separar la tarjeta */
  body{
    margin:0; padding:20px;
    background:#f3f6fb;
    color:#243145;
    font-family: Arial, Helvetica, sans-serif;
    line-height:1.6;
  }
  /* Tarjeta contenedora 
  .card{
    max-width:950px;margin:0 auto;border-radius:12px;
    overflow:hidden; border:1px solid #e6eef5; background:#fff;
    box-shadow:0 3px 10px rgba(18,38,63,.06);
  }*/
  /* Encabezado con azul marino legible */
  .header{
    background:#223447; color:#fff; text-align:center; padding:24px 20px;
  }
  .logo{display:block;margin:0 auto 8px;width:72px;height:72px}
  .title{margin:6px 0 4px 0; font-size:24px; font-weight:700; color:#ffffff;}
  .subtitle{margin:0; font-size:14px; color:#c7d7ea;}
  /* Contenido */
  .content{padding:28px 24px;}
  .highlight{
    background:#f6fff8; border-left:4px solid #27ae60; padding:14px 16px; margin:18px 0;
  }
  ul{padding-left:20px; margin:10px 0 0 0;}
  li{margin:8px 0;}
  /* Botón primario compatible */
  .button{
    display:inline-block; padding:12px 22px; border-radius:8px;
    background:#3b82f6; color:#fff !important; text-decoration:none; font-weight:600;
  }
  /* Pie */
  .footer{
    border-top:1px solid #e6eef5; margin-top:24px; padding-top:18px;
    color:#5b6b82; font-size:13px;
  }
  a{color:#3b82f6;}
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <!-- Reemplaza {$logoHTML} por tu <img src="cid:logoMP" ...> -->
      {$logoHTML}
      <h2 class="title">🐾 Mundo Patitas</h2>
      <p class="subtitle">Guías de Remisión</p>
    </div>

    <div class="content">
      <p>Hola <strong>{$nombre}</strong>,</p>

      <div class="highlight">
        <p style="margin:0;">✅ Se han generado <strong>{$total} guía(s) de remisión</strong> para la asignación <strong>#{$asignacionId}</strong>.</p>
      </div>

      <p><strong>Documentos adjuntos:</strong></p>
      <ul>{$listaHTML}</ul>

      <p>Por favor, revisa los archivos PDF adjuntos a este correo. Cada guía contiene el detalle completo de los productos a entregar.</p>

      <!-- Si quieres un CTA opcional:
      <p><a class="button" href="#">Ver en el sistema</a></p>
      -->

      <div class="footer">
        <p><strong>Importante:</strong></p>
        <ul style="margin:6px 0;">
          <li>Verifica que todos los datos sean correctos.</li>
          <li>Lleva las guías durante la entrega.</li>
        </ul>
        <p style="margin-top:14px;">Saludos,<br><strong>Equipo Mundo Patitas</strong></p>
      </div>
    </div>
  </div>
</body>
</html>

HTML;
    }

public static function generarHTMLOC(
  string $nombreProveedor,
  string $numeroOC,   // <- NUEVO: número visible (OC2025-0003)
  int $idOC,          // <- NUEVO: id interno
  string $moneda,
  float $total,
  ?string $empresa = 'Mundo Patitas'
): string {
  $totalFmt = number_format($total, 2, '.', ',');
  $nom = htmlspecialchars($nombreProveedor, ENT_QUOTES, 'UTF-8');
  $emp = htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8');
  $num = htmlspecialchars($numeroOC, ENT_QUOTES, 'UTF-8');

  return <<<HTML
<!DOCTYPE html>
<html lang="es">
<meta charset="utf-8">
<body style="font-family:Arial,Helvetica,sans-serif;color:#243145">
  <h2 style="margin:0 0 6px">Orden de Compra N° {$num}</h2>
<!--   <p style="margin:0 0 10px;font-size:12px;color:#6b7280">
    ID interno: <strong>#{$idOC}</strong>
  </p> -->

  <p>Estimado(a) <strong>{$nom}</strong>,</p>
  <p>Adjuntamos la <strong>Orden de Compra</strong> emitida por <strong>{$emp}</strong>.</p>
  <p><b>Total:</b> {$moneda} {$totalFmt}</p>
  <p>Quedamos atentos a su confirmación.</p>
  <p>Saludos,<br><b>{$emp}</b></p>
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

        // Define el CID y la ruta del logo
        $logoCid  = 'logoMP';
        $logoPath = __DIR__ . '/../src/Imagen/Logo-MP.png';

        // Genera el HTML referenciando el CID
        $body = self::generarHTMLGuias($toName, $asignacionId, $guiasInfo, $logoCid);

        // Envía con adjuntos + imagen embebida
        return self::enviarConAdjuntos(
            $toEmail,
            $toName,
            $subject,
            $body,
            $adjuntos,
            [$logoCid => $logoPath]  // embebido
        );
    }
}
