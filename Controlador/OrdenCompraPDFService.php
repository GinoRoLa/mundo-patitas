<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

final class OrdenCompraPDFService
{
  private static function tmpBase(): string {
    $dir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
  }

  public static function renderHTML(array $oc): string
  {
    $e = $oc['encabezado'];
    $p = $oc['proveedor'];
    $c = $oc['empresa'];
    $det = $oc['detalle'] ?? [];

    $fecha = substr((string)$e['Fec_Emision'], 0, 10);
    $numOC = str_pad((string)$e['Id_OrdenCompra'], 5, '0', STR_PAD_LEFT);

    $subtotal = number_format((float)$e['SubTotal'], 2, '.', ',');
    $igv      = number_format((float)$e['Impuesto'], 2, '.', ',');
    $total    = number_format((float)$e['MontoTotal'], 2, '.', ',');

    // Logo opcional
    $logoPath = __DIR__ . '/../src/Imagen/Logo-MP.png';
    $logoData = (is_file($logoPath) && ($raw=@file_get_contents($logoPath))!==false)
      ? 'data:image/png;base64,'.base64_encode($raw) : '';

    ob_start(); ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Orden de Compra #<?= htmlspecialchars($numOC) ?></title>
<style>
  @page { size: A4; margin: 18mm 15mm; }
  body { font-family: Arial, Helvetica, sans-serif; color:#111; font-size:12px; }
  h1 { font-size:26px; margin:0 0 6px 0; }
  .muted{ color:#666; }
  .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
  .header-right { text-align:right; }
  .kva { margin:2px 0; }
  .two-col{ display:flex; gap:16px; margin:16px 0 12px 0; }
  .card { flex:1; border:1px solid #bbb; border-radius:6px; padding:10px 12px; }
  .card h3 { margin:0 0 8px 0; font-size:14px; }
  .row { display:flex; gap:8px; margin:2px 0; }
  .row .lbl{ width:130px; font-weight:bold; }
  table{ width:100%; border-collapse:collapse; }
  th, td{ border:1px solid #333; padding:6px 5px; }
  th{ background:#efefef; font-size:11px; }
  td{ font-size:11px; }
  .right{ text-align:right; }
  .no-border td{ border:0; }
  .totals { width:55%; margin-left:auto; border-collapse:collapse; margin-top:8px; }
  .totals td{ padding:6px 5px; border:1px solid #333; }
  .totals tr td:first-child{ background:#fafafa; font-weight:bold; }
  .section { margin-top:14px; }
  .sign { margin-top:36px; text-align:center; }
  .sign .line { margin:40px auto 4px; height:1px; background:#444; width:320px; }
  .logo { width:70px; height:70px; object-fit:contain; }
</style>
</head>
<body>

  <div class="header">
    <div>
      <h1>Orden de Compra</h1>
      <?php if ($logoData): ?>
        <img class="logo" src="<?= $logoData ?>" alt="Logo">
      <?php endif; ?>
    </div>
    <div class="header-right">
      <div class="kva"><b>Fecha:</b> <?= htmlspecialchars($fecha) ?></div>
      <div class="kva"><b>N° de orden:</b> <b><?= htmlspecialchars($numOC) ?></b></div>
    </div>
  </div>

  <div class="two-col">
    <div class="card">
      <h3>Datos del proveedor</h3>
      <div class="row"><div class="lbl">Nombre o razón social:</div><div><?= htmlspecialchars($p['RazonSocial']) ?></div></div>
      <div class="row"><div class="lbl">RUC:</div><div><?= htmlspecialchars($p['RUC']) ?></div></div>
      <div class="row"><div class="lbl">Dirección:</div><div><?= htmlspecialchars($p['Direccion']) ?></div></div>
      <div class="row"><div class="lbl">Teléfono:</div><div><?= htmlspecialchars($p['Telefono']) ?></div></div>
      <div class="row"><div class="lbl">Correo electrónico:</div><div><?= htmlspecialchars($p['Correo']) ?></div></div>
    </div>

    <div class="card">
      <h3>Datos del cliente</h3>
      <div class="row"><div class="lbl">Nombre o razón social:</div><div><?= htmlspecialchars($c['RazonSocial']) ?></div></div>
      <div class="row"><div class="lbl">RUC:</div><div><?= htmlspecialchars($c['RUC']) ?></div></div>
      <div class="row"><div class="lbl">Dirección:</div><div><?= htmlspecialchars($c['Direccion']) ?></div></div>
      <div class="row"><div class="lbl">Teléfono:</div><div><?= htmlspecialchars($c['Telefono']) ?></div></div>
      <div class="row"><div class="lbl">Correo electrónico:</div><div><?= htmlspecialchars($c['Correo']) ?></div></div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:12%">Ref.</th>
        <th>Descripción</th>
        <th style="width:12%">Cantidad</th>
        <th style="width:16%">Precio unitario</th>
        <th style="width:16%">Precio total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($det as $i): ?>
      <tr>
        <td>#<?= htmlspecialchars($i['Id_Producto']) ?></td>
        <td><?= htmlspecialchars($i['Descripcion']) ?></td>
        <td class="right"><?= number_format((float)$i['Cantidad'], 0) ?></td>
        <td class="right">S/ <?= number_format((float)$i['PrecioUnitario'], 2, '.', ',') ?></td>
        <td class="right">S/ <?= number_format((float)$i['Total'], 2, '.', ',') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Total pedido</td><td class="right">S/ <?= $subtotal ?></td></tr>
    <!-- Si no manejas descuentos/costos envío, déjalos en 0 o comenta las filas -->
    <!-- <tr><td>Descuento</td><td class="right">- S/ 0.00</td></tr> -->
    <!-- <tr><td>Gastos de envío</td><td class="right">S/ 0.00</td></tr> -->
    <tr><td>IGV (<?= number_format((float)$e['PorcentajeIGV'],2) ?>%)</td><td class="right">S/ <?= $igv ?></td></tr>
    <tr><td><b>Total a pagar</b></td><td class="right"><b>S/ <?= $total ?></b></td></tr>
  </table>

  <div class="section">
    <div class="row"><div class="lbl">Fecha de entrega:</div><div><?= htmlspecialchars($fecha) ?></div></div>
    <div class="row"><div class="lbl">Dirección de entrega:</div><div><?= htmlspecialchars($c['Direccion']) ?></div></div>
    <div class="row"><div class="lbl">Notas:</div><div></div></div>
  </div>

  <div class="sign">
    <div class="line"></div>
    <div>Firma del receptor</div>
  </div>

</body>
</html>
<?php
    return ob_get_clean();
  }

  public static function generarPDFTemporal(array $oc): string
  {
    $html = self::renderHTML($oc);
    $tmp  = self::tmpBase();

    $opts = new Options();
    $opts->set('isRemoteEnabled', true);
    $opts->set('isHtml5ParserEnabled', true);
    $opts->set('tempDir', $tmp);
    $opts->set('chroot', dirname(__DIR__));

    $dom = new Dompdf($opts);
    $dom->loadHtml($html, 'UTF-8');
    $dom->setPaper('A4', 'portrait');
    $dom->render();

    $safe = 'OC_' . date('Ymd_His') . '_' . uniqid();
    $dir  = $tmp . DIRECTORY_SEPARATOR . 'ocs_' . uniqid('', true);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $path = $dir . DIRECTORY_SEPARATOR . $safe . '.pdf';
    file_put_contents($path, $dom->output());
    return $path;
  }
}
