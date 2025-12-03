<?php
// Controlador/ControladorCUS30RecaudacionDelivery.php
header('Content-Type: application/json; charset=utf-8');

include_once 'Conexion.php';
include_once '../Modelo/Trabajador.php';
include_once '../Modelo/CUS30RecaudacionDeliveryModel.php';

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

function dni_ok($dni): bool
{
  return (bool)preg_match('/^\d{8}$/', (string)$dni);
}

function dec($v): float
{
  return (float)str_replace(',', '.', (string)$v);
}

try {
  $accion = $_GET['accion'] ?? '';
  if ($accion === '' && isset($_SERVER['PATH_INFO'])) {
    $accion = ltrim($_SERVER['PATH_INFO'], '/');
  }

  $model = new CUS30RecaudacionDeliveryModel();
  $trabM = new Trabajador();

  switch ($accion) {

    // =====================================================
    // 1) ACTOR (CAJERO) PARA CABECERA
    // =====================================================
    case 'actor':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);

      // Aquí puedes luego tomar el DNI desde sesión; por ahora fijo de ejemplo
      $dniActor = trim($_GET['dni'] ?? '22222222');
      if (!dni_ok($dniActor)) err('DNI inválido (8 dígitos).', 422);

      $row = $trabM->buscarPorDni($dniActor);
      if (!$row) {
        ok(['found' => false]);
      }

      ok([
        'found'      => true,
        'trabajador' => $row,
        'nombre'     => trim(($row['des_nombreTrabajador'] ?? '') . ' ' . ($row['des_apepatTrabajador'] ?? '') . ' ' . ($row['des_apematTrabajador'] ?? '')),
        'rol'        => $row['cargo'] ?? '',
      ]);
      break;

    // =====================================================
    // 2) LISTAR ASIGNACIONES PENDIENTES POR DNI REPARTIDOR
    // =====================================================
    case 'asignaciones-pendientes':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);

      $dni = trim($_GET['dniRepartidor'] ?? '');
      if (!dni_ok($dni)) err('DNI de repartidor inválido.', 422);

      $repartidor = $trabM->buscarPorDni($dni);
      if (!$repartidor) {
        err('No se encontró repartidor con ese DNI.', 404);
      }

      $rutas = $model->buscarAsignacionesPendientesPorDniRepartidor($dni);

      ok([
        'repartidor' => $repartidor,
        'rutas'      => $rutas,
      ]);
      break;

    // =====================================================
    // 3) DETALLE DE RECAUDACIÓN (SOLO CONSULTA)
    // =====================================================
    case 'recaudacion-detalle':
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);

  $idOrdenAsignacion = (int)($_GET['idOrdenAsignacion'] ?? 0);
  if ($idOrdenAsignacion <= 0) err('Id_OrdenAsignacion inválido.', 422);

  $cabecera = $model->obtenerCabeceraAsignacion($idOrdenAsignacion);
  if (!$cabecera) {
    err('No se encontró la orden de asignación o no tiene nota de caja.', 404);
  }

  $pedidos = $model->listarPedidosDeliveryContraentrega($idOrdenAsignacion);

  // Inicializar totales para front
  $ventasEsperadas = 0.0;
  foreach ($pedidos as &$p) {
    $montoPedido = (float)$p['MontoPedido'];
    
    // ⚠️ CAMBIO: Ya NO inicializamos MontoVueltoEntregado
    if ($p['EstadoPedido'] === 'Entregado') {
      $p['MontoCobrado'] = $montoPedido;
      $ventasEsperadas += $montoPedido;
    } else {
      $p['MontoCobrado'] = 0.0;
    }
    
    $p['Diferencia'] = 0.0;
  }
  unset($p);

  ok([
    'cabecera' => $cabecera,
    'notaCaja' => [
      'Id_NotaCajaDelivery' => $cabecera['Id_NotaCajaDelivery'],
      'MontoFondo'          => $cabecera['MontoFondo'],
      'Estado'              => $cabecera['EstadoNota'],
    ],
    'pedidos'  => $pedidos,
    'totalesIniciales' => [
      'MontoVentasEsperado' => $ventasEsperadas,
    ],
  ]);
  break;

// =====================================================
// CASO: cerrar-recaudacion - ⚠️ CAMBIO: No procesamos MontoVueltoEntregado
// =====================================================

case 'cerrar-recaudacion':
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);

  $idOrdenAsignacion      = (int)($_POST['idOrdenAsignacion'] ?? 0);
  $idTrabajadorRepartidor = (int)($_POST['idTrabajadorRepartidor'] ?? 0);
  $idNotaCajaDelivery     = (int)($_POST['idNotaCajaDelivery'] ?? 0);
  $montoEfectivoEntregado = dec($_POST['montoEfectivoEntregado'] ?? 0);
  $detalleJson            = $_POST['detalleJson'] ?? '[]';

  if ($idOrdenAsignacion <= 0) err('Id_OrdenAsignacion inválido.', 422);
  if ($idTrabajadorRepartidor <= 0) err('Id del repartidor inválido.', 422);
  if ($idNotaCajaDelivery <= 0) err('Id de Nota de Caja inválido.', 422);
  if ($montoEfectivoEntregado < 0) err('Monto efectivo inválido.', 422);

  $detalle = json_decode($detalleJson, true);
  if (!is_array($detalle)) {
    err('detalleJson inválido (no es JSON de arreglo).', 422, ['detalleJson' => $detalleJson]);
  }

  if ($model->existeRecaudacionParaAsignacion($idOrdenAsignacion)) {
    err('Ya existe una recaudación registrada para esta asignación.', 409);
  }

  $montoFondoRetirado = $model->obtenerMontoFondoNotaCaja($idNotaCajaDelivery, $idOrdenAsignacion);
  if ($montoFondoRetirado === null) {
    err('No se encontró la nota de caja para esta asignación.', 404);
  }

  // ⚠️ CAMBIO: Calculamos totales SIN MontoVueltoEntregado
  $montoVentasEsperado = 0.0;
  $rowsDetalle = [];

  foreach ($detalle as $idx => $item) {
    $idOrdenPed   = (int)($item['idOrdenPedido'] ?? 0);
    $montoPedido  = dec($item['montoPedido'] ?? 0);
    $montoCobrado = dec($item['montoCobrado'] ?? 0);
    // ❌ ELIMINADO: $montoVuelto = dec($item['montoVueltoEntregado'] ?? 0);
    $estadoPed    = trim($item['estadoPedido'] ?? 'Entregado');

    if ($idOrdenPed <= 0) {
      err("Id_OrdenPedido inválido en detalle índice {$idx}.", 422);
    }
    // ⚠️ CAMBIO: Solo validamos montoPedido y montoCobrado
    if ($montoPedido < 0 || $montoCobrado < 0) {
      err("Montos negativos en detalle índice {$idx}.", 422);
    }

    if ($estadoPed === 'Entregado') {
      $montoVentasEsperado += $montoCobrado;
    }

    $diferenciaPed = $montoCobrado - $montoPedido;

    // ⚠️ CAMBIO: Ya NO incluimos MontoVueltoEntregado en el array
    $rowsDetalle[] = [
      'Id_OrdenPedido' => $idOrdenPed,
      'MontoPedido'    => $montoPedido,
      'MontoCobrado'   => $montoCobrado,
      'Diferencia'     => $diferenciaPed,
      'EstadoPedido'   => $estadoPed,
    ];
  }

  // Cálculo sin cambios (el vuelto ya está en el fondo)
  $montoEsperadoRetorno = $montoFondoRetirado + $montoVentasEsperado;
  $diferenciaGlobal     = $montoEfectivoEntregado - $montoEsperadoRetorno;

  $epsilon = 0.01;
  if (abs($diferenciaGlobal) <= $epsilon) {
    $estadoRec = 'Cuadrado';
    $diferenciaGlobal = 0.0;
  } elseif ($diferenciaGlobal < 0) {
    $estadoRec = 'Faltante';
  } else {
    $estadoRec = 'Sobrante';
  }

  // Delegar al modelo (sin cambios en parámetros de cabecera)
  $resultado = $model->cerrarRecaudacion(
    [
      'Id_OrdenAsignacion'      => $idOrdenAsignacion,
      'Id_TrabajadorRepartidor'=> $idTrabajadorRepartidor,
      'Id_NotaCajaDelivery'     => $idNotaCajaDelivery,
      'MontoFondoRetirado'      => $montoFondoRetirado,
      'MontoVentasEsperado'     => $montoVentasEsperado,
      'MontoEsperadoRetorno'    => $montoEsperadoRetorno,
      'MontoEfectivoEntregado'  => $montoEfectivoEntregado,
      'DiferenciaGlobal'        => $diferenciaGlobal,
      'EstadoRec'               => $estadoRec,
    ],
    $rowsDetalle
  );

  ok([
    'idRecaudacion'        => $resultado['Id_Recaudacion'],
    'estadoFinal'          => $estadoRec,
    'diferencia'           => $diferenciaGlobal,
    'montoEsperadoRetorno' => $montoEsperadoRetorno,
    'notaDescuento'        => $resultado['NotaDescuento'] ?? null,
    'msg'                  => ($estadoRec === 'Cuadrado'
                                ? 'Recaudación cerrada correctamente.'
                                : 'Recaudación cerrada con observación.')
  ]);
  break;

    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado', 500, [
    'detail' => $e->getMessage(),
  ]);
}