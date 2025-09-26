<?php
// /Controlador/CUS02/ControladorCUS02.php
header('Content-Type: application/json; charset=utf-8');

include_once 'Conexion.php';
include_once '../Modelo/Cliente.php';
include_once '../Modelo/MetodoEntrega.php';
include_once '../Modelo/PreOrden.php';
include_once '../Modelo/OrdenPedido.php';
include_once '../Modelo/DireccionEnvioCliente.php';

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
function dni_ok($dni)
{
  return (bool)preg_match('/^\d{8}$/', (string)$dni);
}

function desc_hu002(int $cant): float
{
  if ($cant > 8) return 0.15;
  if ($cant >= 5) return 0.10;
  return 0.00;
}



try {
  $accion = $_GET['accion'] ?? '';
  if ($accion === '' && isset($_SERVER['PATH_INFO'])) $accion = ltrim($_SERVER['PATH_INFO'], '/');

  switch ($accion) {
    case 'metodos-entrega':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $met = (new MetodoEntrega())->listarActivos();
      ok(['metodos' => $met]);

    case 'buscar-cliente':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $dni = trim($_POST['dni'] ?? '');
      if (!dni_ok($dni)) err('DNI inválido (8 dígitos).', 422);

      $cli = (new Cliente())->buscarPorDni($dni);
      if (!$cli) ok(['found' => false]);

      $pre  = (new PreOrden())->vigentesPorCliente($dni);
      $dirs = (new DireccionEnvioCliente())->listarPorClienteId((int)$cli['Id_Cliente']);

      ok(['found' => true, 'cliente' => $cli, 'preordenes' => $pre, 'direcciones' => $dirs]);


    case 'consolidar':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $dni = trim($_POST['dni'] ?? '');
      $ids = $_POST['ids'] ?? [];
      if (!dni_ok($dni)) err('DNI inválido (8 dígitos).', 422);
      if (!is_array($ids) || !$ids) err('Debe seleccionar al menos una preorden para generar la orden.', 422);
      $ids = array_values(array_unique(array_map('intval', $ids)));

      $preM = new PreOrden();
      $validas = $preM->filtrarVigentesDelCliente($dni, $ids);
      if (count($validas) !== count($ids)) err('Hay preórdenes no vigentes o que no pertenecen al cliente. Refresca la lista.', 422);

      $items = $preM->consolidarProductos($validas);
      $cant  = array_sum(array_map(fn($r) => (int)$r['Cantidad'],  $items));
      $subt  = array_sum(array_map(fn($r) => (float)$r['Subtotal'], $items));

      $rate  = desc_hu002($cant);
      $desc  = round($subt * $rate, 2);

      ok([
        'items'               => $items,
        'cantidadProductos'   => $cant,
        'subtotal'            => $subt,
        'descuento'           => $desc,
        'descuentoRate'       => $rate,
        'total'               => max(0, $subt - $desc)
      ]);


    case 'registrar':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);
      $dni      = trim($_POST['dni'] ?? '');
      $idsPre   = $_POST['idsPreorden'] ?? [];
      $metodoId = (int)($_POST['metodoEntregaId'] ?? 0);
      if (!dni_ok($dni)) err('DNI inválido.', 422);
      if (!is_array($idsPre) || !$idsPre) err('Debe seleccionar al menos una preorden.', 422);
      $idsPre = array_values(array_map('intval', $idsPre));

      // Método y costo
      $met = (new MetodoEntrega())->obtenerPorId($metodoId);
      if (!$met || (isset($met['Estado']) && $met['Estado'] !== 'Activo')) err('Método de entrega inválido.', 422);
      $costoEntrega = (float)$met['Costo'];

      // Validar/Consolidar preórdenes
      $preM = new PreOrden();
      $validas = $preM->filtrarVigentesDelCliente($dni, $idsPre);
      if (count($validas) !== count($idsPre)) err('Hay preórdenes no vigentes o que no pertenecen al cliente. Refresca la lista.', 422);

      $items = $preM->consolidarProductos($validas);
      if (!$items) err('No hay ítems para registrar.', 422);

      $cant = array_sum(array_map(fn($r) => (int)$r['Cantidad'], $items));
      $subt = array_sum(array_map(fn($r) => (float)$r['Subtotal'], $items));
      $rate  = desc_hu002($cant);
      $desc  = round($subt * $rate, 2);
      $total = max(0, $subt - $desc + $costoEntrega);

      // Cliente
      $cli = (new Cliente())->buscarPorDni($dni);
      if (!$cli) err('Cliente no encontrado por DNI', 422);
      $idClienteInt = (int)$cli['Id_Cliente'];

      // Crear orden + detalle (igual que antes; puedes mantener tu método PHP)
      $ordenId = (new OrdenPedido())->crearOrdenConDetalle([
        'idCliente'       => $idClienteInt,
        'metodoEntregaId' => $metodoId,
        'costoEntrega'    => $costoEntrega,
        'descuento'       => $desc,
        'total'           => $total,
        'items'           => $items
      ]);

      // NUEVO: Vincular preórdenes a la orden en t90 y marcar Procesado en t01 (SP)
      // (el método del modelo ahora llama a sp_vincular_preordenes_a_orden)
      $res = $preM->procesarYVincular($validas, $ordenId);
      if (($res['vinculadas'] ?? 0) <= 0) {
        err('No se pudieron marcar/vincular las preórdenes.', 422);
      }

      // === Delivery: snapshot (igual que antes) ===
      $esDelivery = isset($met['EsDelivery'])
        ? ((int)$met['EsDelivery'] === 1)
        : (stripos($met['Descripcion'] ?? '', 'delivery') !== false);

      if ($esDelivery) {
        $dirM = new DireccionEnvioCliente();
        $direccionEnvioId = (int)($_POST['direccionEnvioId'] ?? 0);

        try {
          if ($direccionEnvioId > 0) {
            $dirM->crearSnapshotDesdeGuardada($ordenId, $idClienteInt, $direccionEnvioId);
          } else {
            $guardar = isset($_POST['guardarDireccionCliente']) && (int)$_POST['guardarDireccionCliente'] === 1;
            $dirM->crearSnapshotDesdeOtra(
              $ordenId,
              $idClienteInt,
              trim($_POST['envioNombre']    ?? ''),
              trim($_POST['envioTelefono']  ?? ''),
              trim($_POST['envioDireccion'] ?? ''),
              $guardar
            );
          }
        } catch (InvalidArgumentException $e) {
          err($e->getMessage(), 422);
        }
      }

      ok(['ordenId' => $ordenId, 'msg' => 'Orden generada y preórdenes procesadas.']);



    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado', 500, ['detail' => $e->getMessage()]);
}
