<?php
// Controlador/ControladorGuiaHTML.php
// Renderiza una guía como HTML imprimible (A4). Sin librerías externas.

require_once 'Conexion.php';
require_once '../Modelo/Guia.php';

function fail(string $m, int $c=400) {
  http_response_code($c);
  echo "<h3>Error</h3><pre>".htmlspecialchars($m)."</pre>";
  exit;
}

try {
  $id = (int)($_GET['id'] ?? 0);
  $autoprint = isset($_GET['autoprint']); // ?autoprint=1 para abrir el diálogo de impresión

  if ($id <= 0) fail('Id_Guia inválido', 422);

  $guiaM = new Guia();
  // Reutiliza los métodos que ya agregaste:
  $enc = $guiaM->obtenerEncabezado($id);
  $det = $guiaM->obtenerDetalle($id);

  if (!$enc) fail('Guía no encontrada', 404);

  // Datos base
  $numeroTexto = $enc['NumeroTexto'] ?? ( ($enc['Serie'] ?? '001').'-'.str_pad((int)$enc['Numero'],6,'0',STR_PAD_LEFT) );
  $emitido     = substr((string)$enc['Fec_Emision'], 0, 16);
  $inicioTras  = substr((string)$enc['FechaInicioTraslado'], 0, 16);
  $origen      = (string)($enc['OrigenDireccion'] ?? '');
  $destino     = trim(($enc['DireccionDestino'] ?? '').' - '.($enc['DistritoDestino'] ?? ''));
  $modalidad   = (string)($enc['ModalidadTransporte'] ?? 'PROPIO');
  $motivo      = (string)($enc['Motivo'] ?? 'Venta');

} catch (Throwable $e) {
  fail('Error: '.$e->getMessage(), 500);
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<title>Guía de Remisión <?= htmlspecialchars($numeroTexto) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<style>
  /* ----- Estilos de impresión A4 sin márgenes locos ----- */
  @page { size: A4; margin: 18mm 15mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; }
  h1,h2,h3,h4 { margin: 0; }
  .wrap { max-width: 900px; margin: 0 auto; }
  .hdr { display: grid; grid-template-columns: 1fr 280px; gap: 12px; align-items: start; }
  .box { border: 1px solid #222; border-radius: 4px; padding: 10px; }
  .empresa h2 { font-size: 18px; margin-bottom: 6px; }
  .empresa .small { color:#444; font-size: 11px; line-height: 1.35; }
  .rucbox { text-align: center; }
  .rucbox h3 { font-size: 16px; margin-bottom: 6px; }
  .rucbox .nro { font-size: 16px; font-weight: bold; }
  .sec { margin-top: 10px; }
  .sec h4 { font-size: 13px; border-bottom: 1px solid #aaa; padding-bottom: 4px; margin-bottom: 6px; }
  .row { display: grid; grid-template-columns: repeat(2,1fr); gap: 8px 16px; margin-bottom: 4px; }
  .label { font-weight: bold; }
  table { border-collapse: collapse; width: 100%; }
  table.items th, table.items td { border: 1px solid #333; padding: 6px 5px; }
  table.items th { background: #f3f3f3; }
  .right { text-align: right; }
  .small { font-size: 11px; color:#444; }
  .foot { margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .toolbar { position: sticky; top: 0; background: #fff; padding: 8px 0; display: flex; gap: 8px; }
  .btn { border: 1px solid #ccc; background: #f7f7f7; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
  .btn:hover { background: #eee; }
  @media print {
    .toolbar { display: none !important; }
    a[href]:after { content: ""; } /* quita URLs impresas */
  }
</style>
<?php if ($autoprint): ?>
<script>
  // abre el diálogo de impresión automáticamente
  window.addEventListener('load', () => { window.print(); });
</script>
<?php endif; ?>
</head>
<body>
<div class="wrap">
  <!-- Barra para acciones rápidas (no se imprime) -->
  <div class="toolbar">
    <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
    <button class="btn" onclick="window.close()">Cerrar</button>
  </div>

  <div class="hdr">
    <div class="empresa box">
      <h2><?= htmlspecialchars($enc['RemitenteRazonSocial']) ?></h2>
      <div class="small">
        RUC: <b><?= htmlspecialchars($enc['RemitenteRUC']) ?></b><br>
        Modalidad: <?= htmlspecialchars($modalidad) ?><br>
        <!-- Puedes agregar dirección fiscal aquí si la tienes -->
      </div>
    </div>
    <div class="rucbox box">
      <h3>GUÍA DE REMISIÓN<br>REMITENTE</h3>
      <div class="nro">N° <?= htmlspecialchars($numeroTexto) ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Datos del traslado</h4>
    <div class="row">
      <div><span class="label">Fecha emisión:</span> <?= htmlspecialchars($emitido) ?></div>
      <div><span class="label">Inicio traslado:</span> <?= htmlspecialchars($inicioTras) ?></div>
      <div><span class="label">Motivo:</span> <?= htmlspecialchars($motivo) ?></div>
      <div><span class="label">Punto de partida:</span> <?= htmlspecialchars($origen) ?></div>
      <div style="grid-column:1 / -1"><span class="label">Punto de llegada:</span> <?= htmlspecialchars($destino) ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Destinatario</h4>
    <div class="row">
      <div><span class="label">Nombre:</span> <?= htmlspecialchars($enc['DestinatarioNombre']) ?></div>
      <div><span class="label">DNI:</span> <?= htmlspecialchars($enc['DniReceptor']) ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Transportista y unidad</h4>
    <div class="row">
      <div><span class="label">Conductor:</span> <?= htmlspecialchars($enc['Conductor']) ?></div>
      <div><span class="label">Licencia:</span> <?= htmlspecialchars($enc['Licencia']) ?></div>
      <div><span class="label">Marca:</span> <?= htmlspecialchars($enc['Marca']) ?></div>
      <div><span class="label">Placa:</span> <?= htmlspecialchars($enc['Placa']) ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Detalle de bienes transportados</h4>
    <table class="items">
      <thead>
        <tr>
          <th style="width:14%">Código</th>
          <th>Descripción</th>
          <th style="width:18%">Unidad</th>
          <th style="width:12%">Cantidad</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($det as $it): ?>
        <tr>
          <td class="small"><?= htmlspecialchars($it['Id_Producto']) ?></td>
          <td><?= htmlspecialchars($it['Descripcion']) ?></td>
          <td class="small"><?= htmlspecialchars($it['Unidad']) ?></td>
          <td class="right"><?= (int)$it['Cantidad'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="foot">
    <div class="small">Emitido: <?= htmlspecialchars($emitido) ?></div>
    <div class="right small">Serie: <?= htmlspecialchars($enc['Serie']) ?> — N° <?= (int)$enc['Numero'] ?></div>
  </div>
</div>
</body>
</html>
