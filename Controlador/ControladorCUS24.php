<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../vendor/autoload.php';
require_once 'Conexion.php';
require_once '../Modelo/Trabajador.php';
require_once '../Modelo/Almacen.php';
require_once '../Modelo/Asignacion.php';
require_once '../Modelo/Producto.php';
require_once '../Modelo/Guia.php';

require_once __DIR__ . '/GuiaPDFService.php';
require_once __DIR__ . '/EmailService.php';

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

      // ===== Validaciones mínimas =====
      $asigId = (int)($in['asignacionId'] ?? 0);
      if ($asigId <= 0) err('Id de asignación inválido', 422);

      $asigRV = (int)($in['asignacionRV'] ?? 0);
      if ($asigRV <= 0) err('Falta Id_AsignacionRepartidorVehiculo', 422);

      if (empty($in['origen']['id'])) err('Origen inválido', 422);

      $grupos = $in['grupos'] ?? null;
      if (!is_array($grupos) || count($grupos) === 0) err('No hay grupos para generar', 422);

      // (Opcional) remitente fijo / configurable
      $serieGuia       = $in['serie'] ?? '001';
      $remitenteRuc    = $in['remitenteRuc'] ?? '20123456789';
      $remitenteRazon  = $in['remitenteRazon'] ?? 'Mundo Patitas SAC';
      $vehiculo        = $in['vehiculo'] ?? [];
      $transportista   = $in['transportista'] ?? [];
      $enviarCorreo    = $in['enviarCorreo'] ?? true;

      $almacenM = new Almacen();
      $guiaM    = new Guia();
      $repo     = new Asignacion();

      $outGuias     = [];
      $bloqueos     = [];
      $guiasParaPDF = []; // paquetes completos para generar PDFs

      // ===== GENERAR GUÍAS =====
      foreach ($grupos as $g) {
        $ops = $g['ops'] ?? [];
        if (!is_array($ops) || count($ops) === 0) {
          $bloqueos[] = ['key' => ($g['key'] ?? ''), 'motivo' => 'Grupo sin OP'];
          continue;
        }

        $dni      = trim((string)($g['dni'] ?? ''));
        $destNom  = (string)($g['nombre'] ?? '');
        $dir      = trim((string)($g['direccion'] ?? $g['dir'] ?? ''));
        $distrito = (string)($g['distritoNombre'] ?? '');

        if ($dni === '' || $dir === '') {
          $bloqueos[] = ['key' => ($g['key'] ?? ''), 'motivo' => 'Destino incompleto (dni/dirección)'];
          continue;
        }

        try {
          // 1) Salida + kardex + estados
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

          // 3) Detalle desde OPs
          $guiaM->insertarDetalleDesdeOps($guia['idGuia'], $ops);

          // 4) Para respuesta
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

          // 5) Paquete completo para PDF/correo
          if ($enviarCorreo) {
            $paquete = $guiaM->obtenerGuiaCompleta($guia['idGuia']);
            if ($paquete) $guiasParaPDF[] = $paquete;
            else error_log("obtenerGuiaCompleta({$guia['idGuia']}) => null");
          }
        } catch (Throwable $e) {
          $bloqueos[] = [
            'key'     => ($g['key'] ?? ''),
            'motivo'  => 'Excepción en generación',
            'detalle' => $e->getMessage()
          ];
        }
      }

      // ===== Recomputar estado de asignación =====
      $quedanPagados = 0;
      try {
        $quedanPagados = $repo->contarPedidosPendientes($asigId);
        if ($quedanPagados > 0) $repo->actualizarEstado($asigId, 'Parcial');
        else                    $repo->actualizarEstado($asigId, 'Despachada');
      } catch (Throwable $e) {
        error_log("Error actualizando estado asignación: " . $e->getMessage());
      }

      // ===== GENERAR PDFs y ENVIAR CORREO =====
      $correoResultado = [
        'enviado'      => false,
        'destinatario' => null,
        'error'        => null,
        'total'        => 0
      ];

      // diagnóstico adicional de PDFs
      $pdfDiag = [
        'errores' => [],
        'tmpBase' => realpath(__DIR__ . '/../tmp')
      ];

      if ($enviarCorreo && !empty($guiasParaPDF)) {
        try {
          if (!class_exists('GuiaPDFService')) {
            throw new Exception('GuiaPDFService no está disponible (revisa require_once y composer install)');
          }

          // Email del repartidor
          $encAsig = $repo->obtenerEncabezado($asigId);
          $toEmail = (string)($encAsig['email'] ?? '');
          $toName  = trim(($encAsig['nombre'] ?? '') . ' ' . ($encAsig['apePat'] ?? '') . ' ' . ($encAsig['apeMat'] ?? ''));

          // Override desde el cliente
          if (empty($toEmail) && !empty($in['correoDestino'])) {
            $toEmail = $in['correoDestino'];
            $toName  = $in['nombreDestino'] ?? '';
          }

          // 1) Generar PDFs (ahora con errores por guía)
          $lote = GuiaPDFService::generarPDFsLote($guiasParaPDF);
          $archivos = $lote['archivos'] ?? [];
          $pdfDiag['errores'] = $lote['errores'] ?? [];

          if (empty($archivos)) {
            throw new Exception('No se pudieron generar los PDFs');
          }

          // 2) Armar info para el correo
          $guiasInfo = [];
          foreach ($guiasParaPDF as $pkg) {
            $enc = $pkg['encabezado'] ?? [];
            $guiasInfo[] = [
              'numero'  => $enc['numeroStr'] ?? '',
              'destino' => trim(($enc['direccionDestino'] ?? '') . ' - ' . ($enc['distritoDestino'] ?? ''))
            ];
          }

          if (empty($toEmail)) {
            $correoResultado['error'] = 'No se encontró email del destinatario';
          } else {
            // 3) Enviar correo (usa tu servicio / función)
            $resultado = EmailService::enviarGuias(
              $toEmail,
              $toName,
              $asigId,
              $guiasInfo,
              $archivos
            );

            $correoResultado = [
              'enviado'      => $resultado['success'],
              'destinatario' => $toEmail,
              'error'        => $resultado['error'] ?? null,
              'total'        => count($archivos)
            ];
          }
        } catch (Throwable $e) {
          $correoResultado['error'] = $e->getMessage();
          error_log("Error en proceso de correo: " . $e->getMessage());
        } finally {
          // Limpieza de temporales si hubo
          if (!empty($archivos)) {
            GuiaPDFService::limpiarTemporales(array_values($archivos));
          }
        }
      }

      // ===== RESPUESTA =====
      ok([
        'asignacion' => [
          'id'            => $asigId,
          'estado'        => $quedanPagados > 0 ? 'Parcial' : 'Despachada', // fix
          'quedanPagados' => $quedanPagados,
        ],
        'guias'    => $outGuias,
        'bloqueos' => $bloqueos,
        'correo'   => $correoResultado,
        // diagnóstico de PDFs para esa PC
        'pdf'      => $pdfDiag
      ]);
      break;

    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado Back', 500, ['detail' => $e->getMessage()]);
}
