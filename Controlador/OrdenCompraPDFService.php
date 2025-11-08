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
    $nroCotProv = trim((string)($e['NroCotizacionProv'] ?? ''));

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
  body {
    font-family: 'Helvetica', Arial, sans-serif;
    color:#111;
    font-size:12px;
    line-height:1.4;
  }

  /* === HEADER === */
  .header {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    border-bottom:2px solid #444;
    padding-bottom:8px;
    margin-bottom:14px;
  }
  .header-left {
    display:flex;
    gap:12px;
    align-items:center;
  }
  .logo {
    width:70px;
    height:70px;
    object-fit:contain;
  }
  .title-block h1 {
    font-size:24px;
    margin:0;
  }
  .header-right {
    text-align:right;
    font-size:12px;
  }
  .kva { margin:3px 0; }

  /* === CARDS === */
  .two-col {
    display:flex;
    gap:18px;
    margin:16px 0 10px;
  }
  .card {
    flex:1;
    border:1px solid #ccc;
    border-radius:8px;
    padding:10px 14px;
    background:#fafafa;
  }
  .card h3 {
    margin:0 0 6px 0;
    font-size:13px;
    border-bottom:1px solid #ddd;
    padding-bottom:4px;
    color:#333;
  }

  .row {
    display:flex;
    margin:2px 0;
  }
  .lbl {
    width:120px;
    font-weight:bold;
    color:#333;
  }
  .val {
    flex:1;
    color:#111;
  }

  /* === TABLE === */
  table {
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
  }
  th, td {
    border:1px solid #666;
    padding:6px 5px;
    font-size:11px;
  }
  th {
    background:#f1f1f1;
    text-align:center;
    font-weight:bold;
  }
  td.right { text-align:right; }

  /* === TOTALS === */
  .totals {
    width:45%;
    margin-left:auto;
    border-collapse:collapse;
    margin-top:12px;
  }
  .totals td {
    padding:6px 5px;
    border:1px solid #666;
    font-size:11.5px;
  }
  .totals tr td:first-child {
    background:#f7f7f7;
    font-weight:bold;
    width:60%;
  }
  .totals tr:last-child td {
    font-size:12.5px;
    font-weight:bold;
    background:#e8e8e8;
  }

  /* === FOOTER === */
  .section {
    margin-top:16px;
    border-top:1px solid #ccc;
    padding-top:8px;
  }
  .section .row { margin:4px 0; }
  .sign {
    margin-top:50px;
    text-align:center;
  }
  .sign .line {
    margin:40px auto 6px;
    height:1px;
    background:#444;
    width:250px;
  }
</style>
</head>
<body>

  <div class="header">
    <div class="header-left">
      <?php if ($logoData): ?>
        <img class="logo" src="<?= $logoData ?>" alt="Logo">
      <?php endif; ?>
      <div class="title-block">
        <h1>Orden de Compra</h1>
      </div>
    </div>
    <div class="header-right">
      <div class="kva"><b>Fecha:</b> <?= htmlspecialchars($fecha) ?></div>
      <div class="kva"><b>N° de orden:</b> <?= htmlspecialchars($numOC) ?></div>
      <?php if ($nroCotProv !== ''): ?>
        <div class="kva"><b>Ref. cotización proveedor:</b> <?= htmlspecialchars($nroCotProv) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="two-col">
    <div class="card">
      <h3>Datos del proveedor</h3>
      <div class="row"><div class="lbl">Razón social:</div><div class="val"><?= htmlspecialchars($p['RazonSocial']) ?></div></div>
      <div class="row"><div class="lbl">RUC:</div><div class="val"><?= htmlspecialchars($p['RUC']) ?></div></div>
      <div class="row"><div class="lbl">Dirección:</div><div class="val"><?= htmlspecialchars($p['Direccion']) ?></div></div>
      <div class="row"><div class="lbl">Teléfono:</div><div class="val"><?= htmlspecialchars($p['Telefono']) ?></div></div>
      <div class="row"><div class="lbl">Correo:</div><div class="val"><?= htmlspecialchars($p['Correo']) ?></div></div>
    </div>

    <div class="card">
      <h3>Datos del cliente</h3>
      <div class="row"><div class="lbl">Razón social:</div><div class="val"><?= htmlspecialchars($c['RazonSocial']) ?></div></div>
      <div class="row"><div class="lbl">RUC:</div><div class="val"><?= htmlspecialchars($c['RUC']) ?></div></div>
      <div class="row"><div class="lbl">Dirección:</div><div class="val"><?= htmlspecialchars($c['Direccion']) ?></div></div>
      <div class="row"><div class="lbl">Teléfono:</div><div class="val"><?= htmlspecialchars($c['Telefono']) ?></div></div>
      <div class="row"><div class="lbl">Correo:</div><div class="val"><?= htmlspecialchars($c['Correo']) ?></div></div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:10%">Ref.</th>
        <th>Descripción</th>
        <th style="width:12%">Cantidad</th>
        <th style="width:14%">Precio unitario</th>
        <th style="width:14%">Precio total</th>
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
    <tr><td>IGV (<?= number_format((float)$e['PorcentajeIGV'],2) ?>%)</td><td class="right">S/ <?= $igv ?></td></tr>
    <tr><td>Total a pagar</td><td class="right">S/ <?= $total ?></td></tr>
  </table>

  <div class="section">
    <div class="row"><div class="lbl">Plazo de entrega:</div><div class="val"><?= htmlspecialchars($e['TiempoEntregaDias'] ?? 15) ?> días</div></div>
    <div class="row"><div class="lbl">Dirección de entrega:</div><div class="val"><?= htmlspecialchars($c['Direccion']) ?></div></div>
    <div class="row"><div class="lbl">Notas:</div><div class="val">—</div></div>
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
