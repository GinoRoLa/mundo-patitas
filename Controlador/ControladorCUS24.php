<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../vendor/autoload.php';
require_once 'Conexion.php';
require_once '../Modelo/Trabajador.php';
require_once '../Modelo/Almacen.php';
require_once '../Modelo/Asignacion.php';
require_once '../Modelo/Producto.php';
require_once '../Modelo/Guia.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// ============= CONFIGURACIÓN SMTP =============
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'mundopatitas.venta@gmail.com';
const SMTP_PASS = 'fczx jhga rkop uekf';
const SMTP_FROM = 'mundopatitas.venta@gmail.com';
const SMTP_FROM_NAME = 'Mundo Patitas';

function ok(array $d = [], int $c = 200) {
  http_response_code($c);
  echo json_encode(['ok' => true] + $d, JSON_UNESCAPED_UNICODE);
  exit;
}

function err(string $m, int $c = 400, array $x = []) {
  http_response_code($c);
  echo json_encode(['ok' => false, 'error' => $m] + $x, JSON_UNESCAPED_UNICODE);
  exit;
}

// ============= FUNCIONES DE CORREO =============
function renderGuiaHTML(array $guia): string {
  $enc = $guia['encabezado'] ?? [];
  $det = $guia['detalle'] ?? [];

  $numeroTexto = $enc['numeroStr'] ?? (($enc['serie'] ?? '001') . '-' . str_pad((string)($enc['numero'] ?? 0), 6, '0', STR_PAD_LEFT));
  $emitido     = substr((string)($enc['fecha'] ?? date('Y-m-d H:i')), 0, 16);
  $inicioTras  = substr((string)($enc['fechaInicioTraslado'] ?? $enc['fecha'] ?? date('Y-m-d H:i')), 0, 16);
  $origen      = (string)($enc['direccionOrigen'] ?? '');
  $destino     = trim(($enc['direccionDestino'] ?? '') . ' - ' . ($enc['distritoDestino'] ?? ''));
  $modalidad   = (string)($enc['modalidadTransporte'] ?? 'PROPIO');
  $motivo      = (string)($enc['motivo'] ?? 'Venta');

  $remRaz   = htmlspecialchars($enc['remitenteRazon'] ?? 'Mundo Patitas SAC', ENT_QUOTES, 'UTF-8');
  $remRuc   = htmlspecialchars($enc['remitenteRuc'] ?? '20123456789', ENT_QUOTES, 'UTF-8');
  $dni      = htmlspecialchars($enc['dniReceptor'] ?? '', ENT_QUOTES, 'UTF-8');
  $nom      = htmlspecialchars($enc['destinatarioNombre'] ?? '', ENT_QUOTES, 'UTF-8');
  
  $conductor = htmlspecialchars($enc['conductor'] ?? '', ENT_QUOTES, 'UTF-8');
  $licencia  = htmlspecialchars($enc['licencia'] ?? '', ENT_QUOTES, 'UTF-8');
  $marca     = htmlspecialchars($enc['vehMarca'] ?? '', ENT_QUOTES, 'UTF-8');
  $placa     = htmlspecialchars($enc['vehPlaca'] ?? '', ENT_QUOTES, 'UTF-8');

  ob_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<title>Guía de Remisión <?= htmlspecialchars($numeroTexto) ?></title>
<style>
  @page { size: A4; margin: 18mm 15mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 20px; }
  .wrap { max-width: 900px; margin: 0 auto; }
  .hdr { display: grid; grid-template-columns: 1fr 280px; gap: 12px; margin-bottom: 15px; }
  .box { border: 1px solid #222; border-radius: 4px; padding: 10px; }
  .empresa h2 { font-size: 18px; margin: 0 0 6px 0; }
  .empresa .small { color:#444; font-size: 11px; line-height: 1.4; }
  .rucbox { text-align: center; }
  .rucbox h3 { font-size: 16px; margin: 0 0 6px 0; }
  .rucbox .nro { font-size: 16px; font-weight: bold; }
  .sec { margin-bottom: 12px; }
  .sec h4 { font-size: 13px; border-bottom: 1px solid #aaa; padding-bottom: 4px; margin: 0 0 8px 0; }
  .row { display: grid; grid-template-columns: repeat(2,1fr); gap: 6px 16px; }
  .label { font-weight: bold; }
  table { border-collapse: collapse; width: 100%; }
  table th, table td { border: 1px solid #333; padding: 6px 5px; text-align: left; }
  table th { background: #f3f3f3; font-weight: bold; }
  .right { text-align: right; }
  .small { font-size: 11px; color:#444; }
  .foot { margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; }
</style>
</head>
<body>
<div class="wrap">
  <div class="hdr">
    <div class="empresa box">
      <h2><?= $remRaz ?></h2>
      <div class="small">
        RUC: <b><?= $remRuc ?></b><br>
        Modalidad: <?= htmlspecialchars($modalidad) ?>
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
      <div style="grid-column:1/-1"><span class="label">Punto de llegada:</span> <?= htmlspecialchars($destino) ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Destinatario</h4>
    <div class="row">
      <div><span class="label">Nombre:</span> <?= $nom ?></div>
      <div><span class="label">DNI:</span> <?= $dni ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Transportista y unidad</h4>
    <div class="row">
      <div><span class="label">Conductor:</span> <?= $conductor ?></div>
      <div><span class="label">Licencia:</span> <?= $licencia ?></div>
      <div><span class="label">Marca:</span> <?= $marca ?></div>
      <div><span class="label">Placa:</span> <?= $placa ?></div>
    </div>
  </div>

  <div class="sec box">
    <h4>Detalle de bienes transportados</h4>
    <table>
      <thead>
        <tr>
          <th style="width:14%">Código</th>
          <th>Descripción</th>
          <th style="width:18%">Unidad</th>
          <th style="width:12%">Cantidad</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($det as $it): 
        $cod = htmlspecialchars($it['codigo'] ?? (string)($it['idProducto'] ?? ''), ENT_QUOTES, 'UTF-8');
        $des = htmlspecialchars($it['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
        $um  = htmlspecialchars($it['unidad'] ?? '', ENT_QUOTES, 'UTF-8');
        $can = (float)($it['cantidad'] ?? 0);
      ?>
        <tr>
          <td class="small"><?= $cod ?></td>
          <td><?= $des ?></td>
          <td class="small"><?= $um ?></td>
          <td class="right"><?= number_format($can, 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="foot">
    <div class="small">Emitido: <?= htmlspecialchars($emitido) ?></div>
    <div class="right small">Serie: <?= htmlspecialchars($enc['serie'] ?? '001') ?> — N° <?= (int)($enc['numero'] ?? 0) ?></div>
  </div>
</div>
</body>
</html>
<?php
  return ob_get_clean();
}

function generarPDFTemp(array $guia): string {
  $html = renderGuiaHTML($guia);
  
  $opts = new Options();
  $opts->set('isRemoteEnabled', true);
  $dompdf = new Dompdf($opts);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'guias_mp_' . uniqid();
  @mkdir($tmpDir, 0777, true);

  $fileName = 'Guia_' . ($guia['encabezado']['numeroStr'] ?? $guia['encabezado']['id'] ?? uniqid()) . '.pdf';
  $filePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
  file_put_contents($filePath, $dompdf->output());
  
  return $filePath;
}

function enviarCorreoConAdjuntos(string $toEmail, string $toName, string $subject, string $body, array $adjuntos): array {
  try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    
    // Opciones SSL para resolver problemas de certificado
    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true
      ]
    ];

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($toEmail, $toName ?: $toEmail);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    foreach ($adjuntos as $nombre => $ruta) {
      if (!file_exists($ruta)) {
        throw new \Exception("Archivo no encontrado: {$nombre}");
      }
      $mail->addAttachment($ruta, $nombre);
    }

    $mail->send();
    return ['success' => true, 'error' => null];
  } catch (\Throwable $e) {
    error_log("Error enviando correo: " . $e->getMessage());
    return ['success' => false, 'error' => $e->getMessage()];
  }
}

// ============= ENDPOINTS =============
try {
  $accion = $_GET['accion'] ?? '';
  if ($accion === '' && isset($_SERVER['PATH_INFO'])) {
    $accion = ltrim($_SERVER['PATH_INFO'], '/');
  }

  switch ($accion) {
    case 'actor':
      $dniDemo = '77777777';
      $trabM = new Trabajador();
      $tRow  = $trabM->buscarPorDni($dniDemo);
      if (!$tRow) err('Trabajador no encontrado o inactivo', 404);

      $actor = [
        'id'     => (int)$tRow['id_Trabajador'],
        'dni'    => $tRow['DniTrabajador'],
        'nombre' => trim(($tRow['des_nombreTrabajador'] ?? '') . ' ' . ($tRow['des_apepatTrabajador'] ?? '') . ' ' . ($tRow['des_apematTrabajador'] ?? '')),
        'cargo'  => $tRow['cargo'] ?? '',
        'email'  => $tRow['email'] ?? '',
      ];

      $almM  = new Almacen();
      $alms  = $almM->listarPorTrabajadorId((int)$tRow['id_Trabajador']);
      if (!$alms) $alms = [];

      ok([
        'actor' => $actor,
        'almacenes' => $alms,
        'almacenPorDefecto' => $alms[0] ?? null
      ]);
      break;

    case 'buscar-asignacion':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) err('Id de asignación inválido.', 422);

      $repo = new Asignacion();
      $enc  = $repo->obtenerEncabezado($id);
      $pedidos = $repo->obtenerPedidos($id);
      
      $licNum    = $enc['numLicencia'] ?? null;
      $licEstado = $enc['licenciaEstado'] ?? null;

      ok([
        'asignacion' => [
          'id'              => (int)$enc['id'],
          'idAsignacionRV'  => (int)$enc['idAsignacionRV'],
          'fechaProgramada' => $enc['fechaProgramada'],
          'fecCreacion'     => $enc['fecCreacion'],
          'estado'          => $enc['estado'],
        ],
        'repartidor' => [
          'idTrabajador' => (int)$enc['idTrabajador'],
          'dni'          => $enc['dni'],
          'nombre'       => $enc['nombre'],
          'apePat'       => $enc['apePat'],
          'apeMat'       => $enc['apeMat'],
          'telefono'     => $enc['telefono'],
          'email'        => $enc['email'],
          'cargo'        => $enc['cargo'],
          'licencia'     => $licNum,
          'licenciaInfo' => [
            'numero' => $licNum,
            'estado' => $licEstado,
          ],
        ],
        'vehiculo' => [
          'idVehiculo' => (int)$enc['idVehiculo'],
          'marca'      => $enc['vehMarca'],
          'placa'      => $enc['vehPlaca'],
          'modelo'     => $enc['vehModelo'],
        ],
        'pedidos' => $pedidos,
      ]);
      break;

    case 'items-por-orden':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $idOP = (int)($_GET['idOP'] ?? 0);
      if ($idOP <= 0) err('Id de Orden inválido.', 422);

      $ordenM = new Producto();
      $items  = $ordenM->itemsPorOrden($idOP);

      $dni = $nom = $dir = $dist = null;
      if (!empty($items)) {
        $dni  = $items[0]['receptorDni'] ?? null;
        $nom  = $items[0]['receptorNombre'] ?? null;
        $dir  = $items[0]['direccionSnap'] ?? null;
        $dist = $items[0]['idDistrito'] ?? null;
      }

      ok([
        'items' => $items,
        'meta'  => [
          'receptorDni'    => $dni,
          'receptorNombre' => $nom,
          'direccionSnap'  => $dir,
          'idDistrito'     => $dist
        ]
      ]);
      break;

    case 'generar-salida-lote':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);

      $in = json_decode(file_get_contents('php://input'), true) ?? [];

      // Validaciones
      $asigId = (int)($in['asignacionId'] ?? 0);
      if ($asigId <= 0) err('Id de asignación inválido', 422);

      $asigRV = (int)($in['asignacionRV'] ?? 0);
      if ($asigRV <= 0) err('Falta Id_AsignacionRepartidorVehiculo', 422);

      if (empty($in['origen']['id'])) err('Origen inválido', 422);

      $grupos = $in['grupos'] ?? null;
      if (!is_array($grupos) || count($grupos) === 0) err('No hay grupos para generar', 422);

      $serieGuia       = $in['serie'] ?? '001';
      $remitenteRuc    = $in['remitenteRuc'] ?? '20123456789';
      $remitenteRazon  = $in['remitenteRazon'] ?? 'Mundo Patitas SAC';
      $vehiculo        = $in['vehiculo'] ?? [];
      $transportista   = $in['transportista'] ?? [];
      $enviarCorreo    = $in['enviarCorreo'] ?? true; // Nuevo parámetro

      $almacenM = new Almacen();
      $guiaM    = new Guia();
      $repo     = new Asignacion();

      $outGuias = [];
      $bloqueos = [];
      $guiasGeneradas = []; // Para almacenar paquetes completos

      // Generar guías
      foreach ($grupos as $g) {
        $ops = $g['ops'] ?? [];
        if (!is_array($ops) || count($ops) === 0) {
          $bloqueos[] = ['key' => ($g['key'] ?? ''), 'motivo' => 'Grupo sin OP'];
          continue;
        }

        $dni     = trim((string)($g['dni'] ?? ''));
        $destNom = (string)($g['nombre'] ?? '');
        $dir     = trim((string)($g['direccion'] ?? $g['dir'] ?? ''));
        $distrito = (string)($g['distritoNombre'] ?? '');

        if ($dni === '' || $dir === '') {
          $bloqueos[] = ['key' => ($g['key'] ?? ''), 'motivo' => 'Destino incompleto (dni/dirección)'];
          continue;
        }

        try {
          // 1) Registrar salida
          $almacenM->registrarSalida($ops);

          // 2) Crear guía
          $guia = $guiaM->crearGuiaSinNumerador([
            'serie'              => $serieGuia,
            'remitenteRuc'       => $remitenteRuc,
            'remitenteRazon'     => $remitenteRazon,
            'destinatarioNombre' => $destNom,
            'dniReceptor'        => $dni,
            'direccionDestino'   => $dir,
            'distritoDestino'    => $distrito,
            'idDireccionAlmacen' => (int)$in['origen']['id'],
            'idAsignacionRV'     => $asigRV,
            'marca'              => $vehiculo['marca'] ?? '',
            'placa'              => $vehiculo['placa'] ?? '',
            'conductor'          => $transportista['conductor'] ?? '',
            'licencia'           => $transportista['licencia'] ?? '',
            'motivo'             => 'Venta'
          ]);

          // 3) Insertar detalle
          $guiaM->insertarDetalleDesdeOps($guia['idGuia'], $ops);

          // 4) Guardar info
          $outGuias[] = [
            'key'       => ($g['key'] ?? ''),
            'id'        => $guia['idGuia'],
            'numero'    => $guia['numero'],
            'numeroStr' => $guia['numeroStr'],
            'ops'       => $ops,
            'destino'   => [
              'dni'       => $dni,
              'nombre'    => $destNom,
              'direccion' => $dir,
              'distrito'  => $distrito,
            ],
          ];

          // Guardar paquete completo para correo
          if ($enviarCorreo) {
            $paquete = $guiaM->obtenerGuiaCompleta($guia['idGuia']);
            if ($paquete) {
              $guiasGeneradas[] = $paquete;
            }
          }

        } catch (Throwable $e) {
          $bloqueos[] = [
            'key'     => ($g['key'] ?? ''),
            'motivo'  => 'Excepción en generación',
            'detalle' => $e->getMessage()
          ];
        }
      }

      // Actualizar estado de asignación
      try {
        $quedanPagados = $repo->contarPedidosPendientes($asigId);
        if ($quedanPagados > 0) {
          $repo->actualizarEstado($asigId, 'Parcial');
        } else {
          $repo->actualizarEstado($asigId, 'Despachada');
        }
      } catch (Throwable $e) {
        error_log("Error actualizando estado asignación: " . $e->getMessage());
      }

      // ============= ENVIAR CORREO AUTOMÁTICAMENTE =============
      $correoEnviado = false;
      $correoError = null;
      $correoDestinatario = null;

      if ($enviarCorreo && !empty($guiasGeneradas)) {
        try {
          // Obtener email del repartidor
          $encAsig = $repo->obtenerEncabezado($asigId);
          $toEmail = (string)($encAsig['email'] ?? '');
          $toName  = trim(($encAsig['nombre'] ?? '') . ' ' . ($encAsig['apePat'] ?? '') . ' ' . ($encAsig['apeMat'] ?? ''));

          // Permitir override desde el cliente
          if (empty($toEmail) && !empty($in['correoDestino'])) {
            $toEmail = $in['correoDestino'];
            $toName  = $in['nombreDestino'] ?? '';
          }

          if (!empty($toEmail)) {
            $adjuntos = [];
            $lineas = [];

            foreach ($guiasGeneradas as $pkg) {
              $enc = $pkg['encabezado'] ?? [];
              $numStr = $enc['numeroStr'] ?? (($enc['serie'] ?? '001') . '-' . str_pad((string)($enc['numero'] ?? 0), 6, '0', STR_PAD_LEFT));
              $dest = trim(($enc['direccionDestino'] ?? '') . ' - ' . ($enc['distritoDestino'] ?? ''));
              
              $lineas[] = "<li>" . htmlspecialchars($numStr, ENT_QUOTES, 'UTF-8') . " – " . htmlspecialchars($dest, ENT_QUOTES, 'UTF-8') . "</li>";

              // Generar PDF
              $nombreArchivo = 'Guia_' . preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $numStr)) . '.pdf';
              $rutaPDF = generarPDFTemp($pkg);
              $adjuntos[$nombreArchivo] = $rutaPDF;
            }

            $total = count($adjuntos);
            $asunto = "Guías de Remisión - Asignación #{$asigId}";
            $cuerpo = "<p>Hola <b>" . htmlspecialchars($toName ?: $toEmail, ENT_QUOTES, 'UTF-8') . "</b>,</p>"
                    . "<p>Se han generado {$total} guía(s) de remisión para tu asignación.</p>"
                    . "<ul>" . implode('', $lineas) . "</ul>"
                    . "<p>Por favor, revisa los documentos adjuntos.</p>"
                    . "<p>Saludos,<br><b>Mundo Patitas</b></p>";

            $correoEnviado = enviarCorreoConAdjuntos($toEmail, $toName, $asunto, $cuerpo, $adjuntos);
            $correoDestinatario = $toEmail;

            // Limpiar archivos temporales
            foreach ($adjuntos as $ruta) {
              @unlink($ruta);
            }

            if (!$correoEnviado['success']) {
              $correoError = $correoEnviado['error'];
            }
          } else {
            $correoError = "No se encontró email del destinatario";
          }
        } catch (Throwable $e) {
          $correoError = $e->getMessage();
        }
      }

      // Respuesta
      ok([
        'asignacion' => [
          'id'            => $asigId,
          'estado'        => ($quedanPagados ?? 0) > 0 ? 'Parcial' : 'Despachada',
          'quedanPagados' => (int)($quedanPagados ?? 0),
        ],
        'guias'   => $outGuias,
        'bloqueos' => $bloqueos,
        'correo'  => [
          'enviado'      => $correoEnviado['success'] ?? false,
          'destinatario' => $correoDestinatario,
          'error'        => $correoError,
          'total'        => $enviarCorreo ? count($guiasGeneradas) : 0
        ]
      ]);
      break;

    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado', 500, ['detail' => $e->getMessage()]);
}