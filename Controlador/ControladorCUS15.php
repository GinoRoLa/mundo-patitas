<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'Conexion.php';
require_once '../Modelo/Cotizacion.php';
require_once '../Modelo/OrdenCompra.php';
require_once '../Modelo/Requerimiento.php'; // <- lo usaremos para estado / detalle
require_once '../Modelo/Trabajador.php';
require_once '../Modelo/Evaluacion.php';
//require_once '../Modelo/Proveedor.php';

/* ===== Helpers de respuesta ===== */
function ok(array $d = [], int $c = 200)
{
  http_response_code($c);
  echo json_encode(['ok' => true] + $d, JSON_UNESCAPED_UNICODE);
  exit;
}

function err(string $m, int $c = 400, array $x = [])
{
  http_response_code($c);
  echo json_encode(['ok' => false, 'error' => $m] + $x, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $accion = $_GET['accion'] ?? '';
  if ($accion === '' && isset($_SERVER['PATH_INFO'])) {
    $accion = ltrim($_SERVER['PATH_INFO'], '/');
  }

  switch ($accion) {

    /* ==========================================================
       1. Identidad del actor (usuario logueado)
    ========================================================== */
    case 'actor':
      $dniDemo = '77777777';
      $trabM = new Trabajador();
      $tRow = $trabM->buscarPorDni($dniDemo);
      if (!$tRow) err('Trabajador no encontrado o inactivo', 404);

      $actor = [
        'id'     => (int)$tRow['id_Trabajador'],
        'dni'    => $tRow['DniTrabajador'],
        'nombre' => trim(($tRow['des_nombreTrabajador'] ?? '') . ' ' . ($tRow['des_apepatTrabajador'] ?? '') . ' ' . ($tRow['des_apematTrabajador'] ?? '')),
        'cargo'  => $tRow['cargo'] ?? '',
        'email'  => $tRow['email'] ?? '',
      ];
      ok(['actor' => $actor]);
      break;

    /* ==========================================================
       2. Lista de requerimientos pendientes de evaluación
    ========================================================== */
    case 'req-list':
      $reqM = new Requerimiento();
      $rows = $reqM->listarAprobadosParaEvaluacion();
      ok(['requerimientos' => $rows]);
      break;

    /* ==========================================================
       3. Detalle de requerimiento (productos)
    ========================================================== */
    case 'req-detalle':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $idReq = $_GET['id'] ?? '';
      if ($idReq === '') err('Id de requerimiento inválido', 422);

      $reqM = new Requerimiento();
      $enc = $reqM->obtenerEncabezado($idReq);
      if (!$enc) err('Requerimiento no encontrado', 404);
      $det = $reqM->obtenerDetalle($idReq);
      ok(['req' => $enc, 'detalle' => $det]);
      break;

    case 'scan-excel':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $id = $_GET['id'] ?? '';
      if ($id === '') err('Id de requerimiento inválido', 422);

      require_once __DIR__ . '/CotizacionImportService.php';
      $svc = new CotizacionImportService();
      $res = $svc->scanArchivos($id, true);   // <- withDebug
      ok($res);
      break;

    case 'importar-excel':
      // POST JSON: { "file":"RECREQ001_20123456789.xlsx", "overwrite": true }
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $file = $in['file'] ?? '';
      $overwrite = !empty($in['overwrite']);
      if ($file === '') err('Archivo requerido', 422);

      require_once __DIR__ . '/CotizacionImportService.php';
      $svc = new CotizacionImportService();
      try {
        $r = $svc->importarPorNombre($file, $overwrite);
        ok(['import' => $r]);
      } catch (Throwable $e) {
        err('No se pudo importar', 400, ['detail' => $e->getMessage()]);
      }
      break;

    case 'importar-excel-req':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $idReq     = $in['idReq'] ?? '';
      $overwrite = !empty($in['overwrite']);
      if ($idReq === '') err('Id de requerimiento inválido', 422);

      require_once __DIR__ . '/CotizacionImportService.php';
      $svc  = new CotizacionImportService();
      $scan = $svc->scanArchivos($idReq, true);
      $arch = $scan['archivos'] ?? [];

      $importados = [];
      $omitidos   = []; // ya existían (skipped)
      $errores    = [];

      foreach ($arch as $a) {
        try {
          $r = $svc->importarPorNombre($a['file'], $overwrite);
          if (!empty($r['skipped'])) {
            $omitidos[] = [
              'file' => $r['file'] ?? $a['file'],
              'ruc'  => $r['ruc']  ?? $a['ruc'],
              'msg'  => $r['message'] ?? 'Ya existía'
            ];
          } else {
            $importados[] = [
              'file' => $r['file'] ?? $a['file'],
              'ruc'  => $r['ruc']  ?? $a['ruc'],
              'id'   => $r['idCotizacion'] ?? null
            ];
          }
        } catch (Throwable $e) {
          $errores[] = ['file' => $a['file'], 'error' => $e->getMessage()];
        }
      }

      // Cuántas cotizaciones hay en BD (para pintar 🟢)
      $cn = (new Conexion())->conecta();
      $st = mysqli_prepare($cn, "SELECT COUNT(*) FROM t86Cotizacion WHERE Id_ReqEvaluacion=?");
      $idReqInt = (int)$idReq;
      mysqli_stmt_bind_param($st, "i", $idReqInt);
      mysqli_stmt_execute($st);
      $rs = mysqli_stmt_get_result($st);
      $totalEnBD = (int)mysqli_fetch_row($rs)[0];
      mysqli_stmt_close($st);

      ok([
        'ok'         => true,
        'scan'       => $scan,
        'importados' => $importados,
        'omitidos'   => $omitidos,
        'errores'    => $errores,
        'stats' => [
          'detectados' => count($arch),
          'nuevos'     => count($importados),
          'omitidos'   => count($omitidos),
          'errores'    => count($errores),
          'totalEnBD'  => $totalEnBD,
        ],
        'message' => sprintf(
          "Importados: %d · Omitidos (ya existían): %d · Errores: %d",
          count($importados),
          count($omitidos),
          count($errores)
        ),
      ]);
      break;

    /* ==========================================================
       4. Cotizaciones generadas (opcional)
    ========================================================== */
    case 'cots-generadas':
      $idReq = $_GET['id'] ?? '';
      $cotM = new Cotizacion();
      $rows = $cotM->listarPorRequerimiento($idReq, 'Generada');
      ok(['generadas' => $rows]);
      break;

    /* ==========================================================
       5. Cotizaciones recibidas
    ========================================================== */
    case 'cots-recibidas':
      $idReq = $_GET['id'] ?? '';
      if ($idReq === '') err('Id de requerimiento inválido', 422);

      $cotM = new Cotizacion();
      $cab = $cotM->listarPorRequerimiento($idReq, 'Recibida');

      $detallePorCot = [];
      foreach ($cab as $c) {
        $detallePorCot[$c['Id_Cotizacion']] = $cotM->listarDetalle((int)$c['Id_Cotizacion']);
      }
      ok([
        'recibidas' => $cab,
        'detallePorCotizacion' => $detallePorCot
      ]);
      break;

    /* ==========================================================
   6. Evaluar (greedy asignación precio/stock) — PREVIEW
========================================================== */
    case 'evaluar': // o crea un alias 'evaluar-preview' si tu JS lo llama así
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $in   = json_decode(file_get_contents('php://input'), true) ?? [];
      $idReq = $in['idRequerimiento'] ?? $in['idReq'] ?? '';
      if ($idReq === '') err('Id de requerimiento requerido', 422);

      $ev = new Evaluacion();
      $res = $ev->evaluarRequerimientoGreedy($idReq);
      if (empty($res['ok'])) err($res['error'] ?? 'No se pudo evaluar', 400);

      ok([
        'ok'        => true,
        'productos' => $res['productos'],
        'resumen'   => $res['resumen'],
      ]);
      break;

    /* ==========================================================
   7. Generar Órdenes de Compra (transacción)
========================================================== */
case 'generar-ocs':
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);

  $in    = json_decode(file_get_contents('php://input'), true) ?? [];
  $idReq = $in['idRequerimiento'] ?? $in['idReq'] ?? '';
  if ($idReq === '' || (int)$idReq <= 0) err('Id de requerimiento requerido', 422);

  // ------------- Helper: construir adjudicación robusta -------------
  $construirAdjudicacionDesdePreview = function(array $resEval) {
    // formato A (por proveedor): [{ruc, razon, items:[{Id_Producto,Descripcion,Unidad,Cantidad,Precio}, ...]}]
    $porProv = [];

    $productos = $resEval['productos'] ?? [];
    foreach ((array)$productos as $p) {
      // Producto base
      $idp   = (int)($p['Id_Producto'] ?? $p['idProducto'] ?? $p['ProductoId'] ?? 0);
      $desc  = (string)($p['Nombre'] ?? $p['NombreProducto'] ?? $p['Descripcion'] ?? '');
      $um    = (string)($p['UnidadMedida'] ?? $p['Unidad'] ?? $p['UM'] ?? 'UND');

      // Lista de asignaciones por proveedor en el preview
      $asigs = $p['Asignacion'] ?? $p['asignacion'] ?? $p['proveedores'] ?? [];
      foreach ((array)$asigs as $a) {
        $ruc   = (string)($a['ruc'] ?? $a['RUC'] ?? $a['RUC_Proveedor'] ?? $a['provRUC'] ?? '');
        $razon = (string)($a['proveedor'] ?? $a['RazonSocial'] ?? $a['nombre'] ?? '');

        // Cantidad: aceptar varios alias
        $cant = null;
        foreach (['Cantidad','cantidad','CantidadAprobada','qty','qtyAprob'] as $k) {
          if (isset($a[$k]) && is_numeric($a[$k])) { $cant = (float)$a[$k]; break; }
        }
        if ($cant === null && isset($p['CantidadAprobada']) && is_numeric($p['CantidadAprobada'])) {
          $cant = (float)$p['CantidadAprobada']; // fallback por producto
        }
        if (!is_finite($cant ?? null)) $cant = 0;

        // Precio: aceptar varios alias; si no hay, intentar costo/cantidad
        $prec = null;
        foreach (['Precio','precio','PrecioUnitario','PrecioAprobado','price'] as $k) {
          if (isset($a[$k]) && is_numeric($a[$k])) { $prec = (float)$a[$k]; break; }
        }
        if ($prec === null) {
          $costo = null;
          foreach (['costo','Costo','CostoUnitario'] as $k) {
            if (isset($a[$k]) && is_numeric($a[$k])) { $costo = (float)$a[$k]; break; }
          }
          if ($costo !== null && ($cant ?? 0) > 0) $prec = $costo / (float)$cant;
        }
        if (!is_finite($prec ?? null)) $prec = 0;

        // Filtrar inválidos
        if ($ruc === '' || $cant <= 0 || $prec <= 0) continue;

        if (!isset($porProv[$ruc])) {
          $porProv[$ruc] = ['ruc' => $ruc, 'razon' => $razon, 'items' => []];
        }
        $porProv[$ruc]['items'][] = [
          'Id_Producto' => $idp,
          'Descripcion' => $desc,
          'Unidad'      => $um,
          'Cantidad'    => (float)$cant,
          'Precio'      => (float)round($prec, 4),
        ];
      }
    }
    return array_values($porProv);
  };

  // ------------- 1) Si mandan adjudicación externa, validarla rápido -------------
  $adjud = $in['adjudicacion'] ?? null;
  if (is_array($adjud)) {
    $validos = 0;
    foreach ($adjud as $prodOrProv) {
      $items = $prodOrProv['items']
            ?? $prodOrProv['asignacion']
            ?? $prodOrProv['Asignacion']
            ?? [];
      foreach ((array)$items as $it) {
        $cant  = (float)($it['cantidad'] ?? $it['Cantidad'] ?? 0);
        $prec  = (float)($it['precio']   ?? $it['Precio']   ?? $it['PrecioUnitario'] ?? 0);
        $costo = (float)($it['costo']    ?? $it['Costo']    ?? 0);
        if ($cant > 0 && ($prec > 0 || $costo > 0)) { $validos++; break; }
      }
    }
    if ($validos === 0) {
      // adjudicación enviada pero no utilizable
      $adjud = null; // forzar construir desde evaluación
    }
  }

  // ------------- 2) Si no hay adjudicación, construir desde Evaluación -------------
  if (empty($adjud)) {
    $ev  = new Evaluacion();
    $res = $ev->evaluarRequerimientoGreedy($idReq);
    if (empty($res['ok'])) err($res['error'] ?? 'No se pudo evaluar', 400);

    // construir formato A robusto
    $adjud = $construirAdjudicacionDesdePreview($res);
    if (empty($adjud)) {
      err('No se pudo construir la adjudicación desde la evaluación (sin cantidades/precios válidos).', 409, [
        'idReq' => $idReq
      ]);
    }
  }

  // ------------- 3) Generar OCs con captura controlada -------------
  try {
    $ocM = new OrdenCompra();
    $ocs = $ocM->crearOCsDesdeAdjudicacion($idReq, $adjud);
  } catch (Throwable $e) {
    // Si el modelo vuelve a decir "Adjudicación vacía", devolvemos diagnóstico útil
    err('No se pudo generar las órdenes de compra', 500, [
      'detail' => $e->getMessage(),
      'idReq'  => $idReq,
      'nota'   => 'Verifica que en la evaluación cada proveedor tenga CantidadAprobada>0 y PrecioAprobado>0 (o costo>0).'
    ]);
  }

  if (empty($ocs)) {
    err('No se generó ninguna orden de compra.', 409, ['idReq' => $idReq]);
  }

  // ------------- 4) Actualizar estado del requerimiento -------------
  (new Requerimiento())->actualizarEstado((string)$idReq, 'Cerrado');

  ok(['ok' => true, 'ordenes' => $ocs]);
  break;



    /* ==========================================================
   OC → generar PDF (uno)
========================================================== */
    case 'oc-pdf':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $idOC = (int)($_GET['id'] ?? 0);
      if ($idOC <= 0) err('Id de OC inválido', 422);

      require_once __DIR__ . '/../Modelo/OrdenCompra.php';
      require_once __DIR__ . '/OrdenCompraPDFService.php';

      $ocM = new OrdenCompra();
      $data = $ocM->obtenerParaPDF($idOC);
      $pdfPath = OrdenCompraPDFService::generarPDFTemporal($data);

      ok(['file' => $pdfPath, 'name' => 'OC_' . $idOC . '.pdf']);
      break;

    /* ==========================================================
   10. OC → enviar por correo (LOTE por requerimiento)
   POST JSON: { "idReq":123 }
   - Genera PDF por cada OC del requerimiento
   - Envía un correo por proveedor con su propia OC
========================================================== */
    case 'oc-enviar-lote':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $in   = json_decode(file_get_contents('php://input'), true) ?? [];
      $idReq = (int)($in['idRequerimiento'] ?? $in['idReq'] ?? 0);
      if ($idReq <= 0) err('Id de requerimiento requerido', 422);

      require_once __DIR__ . '/../Modelo/OrdenCompra.php';
      require_once __DIR__ . '/OrdenCompraPDFService.php';
      require_once __DIR__ . '/EmailService.php';

      $ocM = new OrdenCompra();

      // 1) Traer todas las OCs del requerimiento (con datos del proveedor)
      $ocs = $ocM->listarPorEvaluacionConContacto($idReq);
      if (empty($ocs)) ok(['ok' => true, 'total' => 0, 'enviados' => 0, 'omitidos' => 0, 'errores' => []]);

      $result = [
        'ok'      => true,
        'total'   => count($ocs),
        'enviados' => 0,
        'omitidos' => 0,
        'errores' => [],
        'detalles' => []  // opcional: para UI
      ];

      $tmpPaths = []; // para limpiar luego

      foreach ($ocs as $ocRow) {
        $idOC = (int)$ocRow['Id_OrdenCompra'];

        // 2) Generar datos y PDF
        try {
          $data = $ocM->obtenerParaPDF($idOC);
          $pdfPath = OrdenCompraPDFService::generarPDFTemporal($data);
          $tmpPaths[] = $pdfPath;
        } catch (\Throwable $e) {
          $result['errores'][] = ['idOC' => $idOC, 'error' => 'PDF: ' . $e->getMessage()];
          $result['omitidos']++;
          continue;
        }

        // 3) Destino (email + nombre)
        [$toEmail, $toName] = $ocM->correoNombreProveedor($ocRow);
        if ($toEmail === '') {
          $result['errores'][] = ['idOC' => $idOC, 'error' => 'Proveedor sin email'];
          $result['omitidos']++;
          continue;
        }

        // 4) Cuerpo (usa el helper si lo añadiste)
        if (method_exists('EmailService', 'generarHTMLOC')) {
          $body = EmailService::generarHTMLOC(
            $toName,
            $idOC,
            $ocRow['Moneda'] ?: 'PEN',
            (float)$ocRow['MontoTotal'],
            $data['empresa']['RazonSocial'] ?? 'Mundo Patitas'
          );
        } else {
          $totalFmt = number_format((float)$ocRow['MontoTotal'], 2, '.', ',');
          $body = "<p>Estimado(a) <b>" . htmlspecialchars($toName) . "</b>,</p>"
            . "<p>Adjuntamos la <b>Orden de Compra N° {$idOC}</b>.</p>"
            . "<p>Total: " . ($ocRow['Moneda'] ?: 'PEN') . " {$totalFmt}</p>"
            . "<p>Saludos,<br><b>" . htmlspecialchars($data['empresa']['RazonSocial'] ?? 'Mundo Patitas') . "</b></p>";
        }

        // 5) Enviar
        $send = EmailService::enviarConAdjuntos(
          $toEmail,
          $toName,
          "Orden de Compra N° {$idOC}",
          $body,
          ['OC_' . $idOC . '.pdf' => $pdfPath]
        );

        if (!empty($send['success'])) {
          $result['enviados']++;
          $result['detalles'][] = ['idOC' => $idOC, 'email' => $toEmail, 'status' => 'sent'];
        } else {
          $result['errores'][] = ['idOC' => $idOC, 'error' => 'MAIL: ' . $send['error']];
          $result['omitidos']++;
        }
      }

      // 6) Limpieza de temporales
      foreach ($tmpPaths as $p) {
        if (is_file($p)) @unlink($p);
        @rmdir(@dirname($p));
      }

      ok($result);
      break;




    /* ==========================================================
       9. Acción no encontrada
    ========================================================== */
    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado Back', 500, ['detail' => $e->getMessage()]);
}
