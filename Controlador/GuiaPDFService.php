<?php

/**
 * Servicio para generar PDFs de Guías de Remisión
 * Ubicación: /Controlador/GuiaPDFService.php
 *
 * Requisitos en php.ini (activar y reiniciar Apache):
 *   extension=mbstring
 *   extension=gd
 *   extension=openssl
 *
 * Asegúrate de ejecutar `composer install` para dompdf.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class GuiaPDFService
{
  /** Devuelve ruta absoluta al directorio tmp del proyecto (creado si no existe) */
  private static function getTmpBase(): string
  {
    $tmpBase = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($tmpBase) && !@mkdir($tmpBase, 0777, true)) {
      throw new \RuntimeException("No se pudo crear el directorio temporal: $tmpBase");
    }
    return $tmpBase;
  }

  /** Genera el HTML de una guía de remisión */
  public static function renderHTML(array $guia): string
  {
    $enc = $guia['encabezado'] ?? [];
    $det = $guia['detalle'] ?? [];

    $numeroTexto = $enc['numeroStr']
      ?? (($enc['serie'] ?? '001') . '-' . str_pad((string)($enc['numero'] ?? 0), 6, '0', STR_PAD_LEFT));

    $emitido     = substr((string)($enc['fecha'] ?? date('Y-m-d H:i')), 0, 16);
    $inicioTras  = substr((string)($enc['fechaInicioTraslado'] ?? $enc['fecha'] ?? date('Y-m-d H:i')), 0, 16);
    $origen      = (string)($enc['direccionOrigen'] ?? $enc['origenDireccion'] ?? '');
    $destino     = trim(($enc['direccionDestino'] ?? '') . ' - ' . ($enc['distritoDestino'] ?? ''));
    $modalidad   = (string)($enc['modalidadTransporte'] ?? 'PROPIO');
    $motivo      = (string)($enc['motivo'] ?? 'Venta');

    $remRaz    = htmlspecialchars($enc['remitenteRazon'] ?? 'Mundo Patitas SAC', ENT_QUOTES, 'UTF-8');
    $remRuc    = htmlspecialchars($enc['remitenteRuc'] ?? '20123456789', ENT_QUOTES, 'UTF-8');
    $dni       = htmlspecialchars($enc['dniReceptor'] ?? '', ENT_QUOTES, 'UTF-8');
    $nom       = htmlspecialchars($enc['destinatarioNombre'] ?? '', ENT_QUOTES, 'UTF-8');

    $conductor = htmlspecialchars($enc['conductor'] ?? '', ENT_QUOTES, 'UTF-8');
    $licencia  = htmlspecialchars($enc['licencia'] ?? '', ENT_QUOTES, 'UTF-8');
    $marca     = htmlspecialchars($enc['vehMarca'] ?? '', ENT_QUOTES, 'UTF-8');
    $placa     = htmlspecialchars($enc['vehPlaca'] ?? '', ENT_QUOTES, 'UTF-8');

    // Logo opcional: no romper si falta
    $logoPath = __DIR__ . '/../src/Imagen/Logo-MP.png';
    $logoData = '';
    if (is_file($logoPath)) {
      $raw = @file_get_contents($logoPath);
      if ($raw !== false) {
        $logoData = 'data:image/png;base64,' . base64_encode($raw);
      }
    }

    ob_start();
?>
    <!doctype html>
    <html lang="es">

    <head>
      <meta charset="utf-8" />
      <title>Guía de Remisión <?= htmlspecialchars($numeroTexto) ?></title>
      <style>
        @page {
          size: A4;
          margin: 18mm 15mm;
        }

        * {
          box-sizing: border-box;
          margin: 0;
          padding: 0;
        }

        body {
          font-family: Arial, Helvetica, sans-serif;
          color: #111;
          font-size: 12px;
          padding: 15px;
        }

        .wrap {
          max-width: 900px;
          margin: 0 auto;
        }

        .hdr-table {
          width: 100%;
          border-collapse: separate;
          border-spacing: 8px 0;
          margin-bottom: 15px;
        }

        .hdr-table td {
          vertical-align: top;
          padding: 0;
        }

        .hdr-left {
          width: 60%;
          padding-right: 6px;
        }

        .hdr-right {
          width: 40%;
          padding-left: 6px;
        }

        .box {
          border: 2px solid #222;
          padding: 10px;
        }

        .empresa h2 {
          font-size: 18px;
          margin: 0 0 8px 0;
          font-weight: bold;
        }

        .empresa .info {
          color: #333;
          font-size: 11px;
          line-height: 1.5;
        }

        .rucbox {
          text-align: center;
        }

        .rucbox h3 {
          font-size: 13px;
          margin: 0 0 8px 0;
          line-height: 1.3;
          font-weight: bold;
        }

        .rucbox .numero {
          font-size: 18px;
          font-weight: bold;
          color: #000;
        }

        .seccion {
          margin-bottom: 12px;
        }

        .seccion h4 {
          font-size: 13px;
          font-weight: bold;
          border-bottom: 2px solid #666;
          padding-bottom: 4px;
          margin: 0 0 8px 0;
        }

        .datos-table {
          width: 100%;
          border-collapse: collapse;
        }

        .datos-table td {
          padding: 4px 8px 4px 0;
          vertical-align: top;
        }

        .datos-table td:nth-child(odd) {
          width: 50%;
        }

        .label {
          font-weight: bold;
        }

        .items-table {
          border-collapse: collapse;
          width: 100%;
        }

        .items-table th,
        .items-table td {
          border: 1px solid #333;
          padding: 6px 5px;
          text-align: left;
        }

        .items-table th {
          background: #e8e8e8;
          font-weight: bold;
          font-size: 11px;
        }

        .items-table td {
          font-size: 11px;
        }

        .right {
          text-align: right;
        }

        .footer-table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 15px;
        }

        .footer-table td {
          padding: 0;
          font-size: 10px;
          color: #555;
        }

        .footer-left {
          width: 50%;
        }

        .footer-right {
          width: 50%;
          text-align: right;
        }
      </style>
    </head>

    <body>
      <div class="wrap">

        <!-- HEADER -->
        <table class="hdr-table">
          <tr>
            <td class="hdr-left">
              <div class="empresa box">
                <table style="width: 100%; border-collapse: collapse;">
                  <tr>
                    <td style="width: 80px; vertical-align: middle; padding-right: 10px;">
                      <?php if ($logoData): ?>
                        <img src="<?= $logoData ?>" alt="Logo" style="width: 70px; height: 70px; display: block;">
                      <?php endif; ?>
                    </td>
                    <td style="vertical-align: middle;">
                      <h2><?= $remRaz ?></h2>
                      <div class="info">
                        <strong>RUC:</strong> <?= $remRuc ?><br>
                        <strong>Modalidad:</strong> <?= htmlspecialchars($modalidad) ?>
                      </div>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
            <td class="hdr-right">
              <div class="rucbox box">
                <h3>GUÍA DE REMISIÓN<br>REMITENTE</h3>
                <div class="numero">N° <?= htmlspecialchars($numeroTexto) ?></div>
              </div>
            </td>
          </tr>
        </table>

        <!-- DATOS DEL TRASLADO -->
        <div class="seccion box">
          <h4>Datos del traslado</h4>
          <table class="datos-table">
            <tr>
              <td><span class="label">Fecha emisión:</span> <?= htmlspecialchars($emitido) ?></td>
              <td><span class="label">Inicio traslado:</span> <?= htmlspecialchars($inicioTras) ?></td>
            </tr>
            <tr>
              <td><span class="label">Motivo:</span> <?= htmlspecialchars($motivo) ?></td>
              <td><span class="label">Punto de partida:</span> <?= htmlspecialchars($origen) ?></td>
            </tr>
            <tr>
              <td colspan="2"><span class="label">Punto de llegada:</span> <?= htmlspecialchars($destino) ?></td>
            </tr>
          </table>
        </div>

        <!-- DESTINATARIO -->
        <div class="seccion box">
          <h4>Destinatario</h4>
          <table class="datos-table">
            <tr>
              <td><span class="label">Nombre:</span> <?= $nom ?></td>
              <td><span class="label">DNI:</span> <?= $dni ?></td>
            </tr>
          </table>
        </div>

        <!-- TRANSPORTISTA -->
        <div class="seccion box">
          <h4>Transportista y unidad</h4>
          <table class="datos-table">
            <tr>
              <td><span class="label">Conductor:</span> <?= $conductor ?></td>
              <td><span class="label">Licencia:</span> <?= $licencia ?></td>
            </tr>
            <tr>
              <td><span class="label">Marca:</span> <?= $marca ?></td>
              <td><span class="label">Placa:</span> <?= $placa ?></td>
            </tr>
          </table>
        </div>

        <!-- DETALLE DE BIENES -->
        <div class="seccion box">
          <h4>Detalle de bienes transportados</h4>
          <table class="items-table">
            <thead>
              <tr>
                <th style="width:15%">Código</th>
                <th style="width:45%">Descripción</th>
                <th style="width:20%">Unidad</th>
                <th style="width:20%">Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($det as $item):
                $codigo = htmlspecialchars($item['codigo'] ?? (string)($item['idProducto'] ?? ''), ENT_QUOTES, 'UTF-8');
                $descripcion = htmlspecialchars($item['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
                $unidad = htmlspecialchars($item['unidad'] ?? '', ENT_QUOTES, 'UTF-8');
                $cantidad = (float)($item['cantidad'] ?? 0);
              ?>
                <tr>
                  <td><?= $codigo ?></td>
                  <td><?= $descripcion ?></td>
                  <td><?= $unidad ?></td>
                  <td class="right"><?= number_format($cantidad, 0) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- FOOTER -->
        <table class="footer-table">
          <tr>
            <td class="footer-left">Emitido: <?= htmlspecialchars($emitido) ?></td>
            <td class="footer-right">
              Serie: <?= htmlspecialchars($enc['serie'] ?? '001') ?> —
              N° <?= str_pad((string)($enc['numero'] ?? 0), 6, '0', STR_PAD_LEFT) ?>
            </td>
          </tr>
        </table>

      </div>
    </body>

    </html>
<?php
    return ob_get_clean();
  }

  /** Genera un PDF temporal y devuelve la ruta del archivo */
  public static function generarPDFTemporal(array $guia): string
  {
    // Validaciones mínimas del paquete
    if (empty($guia['encabezado'])) {
      throw new \RuntimeException('Paquete de guía sin encabezado.');
    }
    if (!isset($guia['detalle']) || !is_array($guia['detalle'])) {
      throw new \RuntimeException('Paquete de guía sin detalle.');
    }

    $html    = self::renderHTML($guia);
    $tmpBase = self::getTmpBase(); // tmp del proyecto

    // Dompdf con tmp/chroot explícitos
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('tempDir', $tmpBase);
    $options->set('chroot', dirname(__DIR__));

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');

    try {
      $dompdf->render();
    } catch (\Throwable $e) {
      // Suele fallar si faltan extensiones: mbstring, gd, openssl
      throw new \RuntimeException('Dompdf no pudo renderizar: ' . $e->getMessage());
    }

    // Carpeta única por ejecución
    $tmpDir = $tmpBase . DIRECTORY_SEPARATOR . 'guias_mp_' . uniqid('', true);
    if (!@mkdir($tmpDir, 0777, true)) {
      throw new \RuntimeException("No se pudo crear la carpeta de salida: $tmpDir");
    }

    // Nombre de archivo seguro
    $enc     = $guia['encabezado'];
    $numeroStr = $enc['numeroStr'] ?? (($enc['serie'] ?? '001') . '-' . str_pad((string)($enc['numero'] ?? 0), 6, '0', STR_PAD_LEFT));
    $safe    = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $numeroStr));
    if ($safe === '') $safe = 'guia_' . uniqid();
    $fileName = 'Guia_' . $safe . '.pdf';
    $filePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;

    // Escribir PDF y verificar
    $bytes = @file_put_contents($filePath, $dompdf->output());
    if ($bytes === false || $bytes === 0) {
      throw new \RuntimeException("No se pudo guardar el PDF en: $filePath");
    }

    return $filePath;
  }

  /** Genera múltiples PDFs y devuelve array [nombre => ruta] */
  public static function generarPDFsLote(array $guias): array
  {
    $archivos = [];  // nombre => ruta
    $errores  = [];  // idGuia (o numeroStr) => mensaje

    foreach ($guias as $guia) {
      try {
        // Identificador legible para errores
        $idOrNum = $guia['encabezado']['id']
          ?? ($guia['encabezado']['numeroStr'] ?? 's/n');

        $ruta = self::generarPDFTemporal($guia);
        $archivos[basename($ruta)] = $ruta;
      } catch (\Throwable $e) {
        $errores[$idOrNum] = $e->getMessage();
        error_log("PDF error ($idOrNum): " . $e->getMessage());
      }
    }

    return ['archivos' => $archivos, 'errores' => $errores];
  }


  /** Elimina archivos temporales (y su carpeta padre si aplica) */
  public static function limpiarTemporales(array $rutas): void
  {
    foreach ($rutas as $ruta) {
      if (is_file($ruta)) {
        @unlink($ruta);
        $dir = dirname($ruta);
        if (is_dir($dir) && strpos($dir, 'guias_mp_') !== false) {
          @rmdir($dir); // se borrará solo si está vacío
        }
      }
    }
  }
}
