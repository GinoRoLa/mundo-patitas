<?php
// /Controlador/CUS02/ControladorCUS02.php
header('Content-Type: application/json; charset=utf-8');

include_once 'Conexion.php';
include_once '../Modelo/Cliente.php';
include_once '../Modelo/MetodoEntrega.php';
include_once '../Modelo/PreOrden.php';
include_once '../Modelo/OrdenPedido.php';

function ok(array $d=[], int $c=200){ http_response_code($c); echo json_encode(['ok'=>true]+$d, JSON_UNESCAPED_UNICODE); exit; }
function err(string $m, int $c=400, array $x=[]){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$m]+$x, JSON_UNESCAPED_UNICODE); exit; }
function dni_ok($dni){ return (bool)preg_match('/^\d{8}$/', (string)$dni); }
function desc_hu002(int $cant): float { return $cant>=6?12.0:($cant>=3?5.0:0.0); }

try{
  $accion = $_GET['accion'] ?? '';
  if ($accion==='' && isset($_SERVER['PATH_INFO'])) $accion = ltrim($_SERVER['PATH_INFO'],'/');

  switch ($accion) {
    case 'metodos-entrega':
      if ($_SERVER['REQUEST_METHOD']!=='GET') err('Method Not Allowed',405);
      $met = (new MetodoEntrega())->listarActivos();
      ok(['metodos'=>$met]);

    case 'buscar-cliente':
      if ($_SERVER['REQUEST_METHOD']!=='POST') err('Method Not Allowed',405);
      $dni = trim($_POST['dni'] ?? '');
      if (!dni_ok($dni)) err('DNI inválido (8 dígitos).', 422);

      $cli = (new Cliente())->buscarPorDni($dni);
      if (!$cli) ok(['found'=>false]);

      $pre = (new PreOrden())->vigentesPorCliente($dni);
      ok(['found'=>true,'cliente'=>$cli,'preordenes'=>$pre]);

    case 'consolidar':
      if ($_SERVER['REQUEST_METHOD']!=='POST') err('Method Not Allowed',405);
      $dni = trim($_POST['dni'] ?? '');
      $ids = $_POST['ids'] ?? [];
      if (!dni_ok($dni)) err('DNI inválido (8 dígitos).', 422);
      if (!is_array($ids) || !$ids) err('Debe seleccionar al menos una preorden para generar la orden.', 422);
      $ids = array_values(array_unique(array_map('intval',$ids)));

      $preM = new PreOrden();
      $validas = $preM->filtrarVigentesDelCliente($dni,$ids);
      if (count($validas)!==count($ids)) err('Hay preórdenes no vigentes o que no pertenecen al cliente. Refresca la lista.',422);

      $items = $preM->consolidarProductos($validas);
      $cant  = array_sum(array_map(fn($r)=>(int)$r['Cantidad'],$items));
      $subt  = array_sum(array_map(fn($r)=>(float)$r['Subtotal'],$items));
      $desc  = desc_hu002($cant);
      ok([
        'items'=>$items,
        'cantidadProductos'=>$cant,
        'subtotal'=>$subt,
        'descuento'=>$desc,
        'total'=>max(0,$subt-$desc)
      ]);

    case 'registrar':
      if ($_SERVER['REQUEST_METHOD']!=='POST') err('Method Not Allowed',405);
      $dni      = trim($_POST['dni'] ?? '');
      $idsPre   = $_POST['idsPreorden'] ?? [];
      $metodoId = (int)($_POST['metodoEntregaId'] ?? 0);
      if (!dni_ok($dni)) err('DNI inválido.',422);
      if (!is_array($idsPre) || !$idsPre) err('Debe seleccionar al menos una preorden.',422);
      $idsPre = array_values(array_map('intval',$idsPre));

      $met = (new MetodoEntrega())->obtenerPorId($metodoId);
      if (!$met || (isset($met['Estado']) && $met['Estado']!=='Activo')) err('Método de entrega inválido.',422);
      $costoEntrega = (float)$met['Costo'];

      $preM = new PreOrden();
      $validas = $preM->filtrarVigentesDelCliente($dni,$idsPre);
      if (count($validas)!==count($idsPre)) err('Hay preórdenes no vigentes o que no pertenecen al cliente. Refresca la lista.',422);

      $items = $preM->consolidarProductos($validas);
      if (!$items) err('No hay ítems para registrar.',422);

      $cant = array_sum(array_map(fn($r)=>(int)$r['Cantidad'],$items));
      $subt = array_sum(array_map(fn($r)=>(float)$r['Subtotal'],$items));
      $desc = desc_hu002($cant);
      $total = max(0, $subt - $desc + $costoEntrega);

      $cli = (new Cliente())->buscarPorDni($dni);
      if (!$cli) err('Cliente no encontrado por DNI', 422);

      $ordenId = (new OrdenPedido())->crearOrdenConDetalle([
        'idCliente'=> (int)$cli['Id_Cliente'],
        'metodoEntregaId'=>$metodoId,
        'costoEntrega'=>$costoEntrega,
        'descuento'=>$desc,
        'total'=>$total,
        'items'=>$items
      ]);
      $preM->procesarYVincular($validas,$ordenId);

      ok(['ordenId'=>$ordenId,'msg'=>'Orden generada y preórdenes procesadas.']);

    default:
      err('Acción no encontrada',404,['accion'=>$accion]);
  }
} catch(Throwable $e){
  err('Error inesperado',500,['detail'=>$e->getMessage()]);
}