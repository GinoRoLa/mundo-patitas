<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'Conexion.php';
require_once '../Modelo/Trabajador.php';
require_once '../Modelo/Almacen.php';
require_once '../Modelo/Asignacion.php';

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
  if ($accion === '' && isset($_SERVER['PATH_INFO'])) $accion = ltrim($_SERVER['PATH_INFO'], '/');

  switch ($accion) {
    case 'actor':
      // En producción: toma el id de la SESIÓN
      // session_start();
      // $idTrabajador = (int)($_SESSION['id_trabajador'] ?? 0);
      // DEMO: por ahora, simula con un DNI
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
      $alms  = $almM->listarPorTrabajadorId((int)$tRow['id_Trabajador']);   // array simple
      if (!$alms) $alms = [];

      $resp = [
        'actor' => $actor,
        'almacenes' => $alms,
        'almacenPorDefecto' => $alms[0] ?? null
      ];
      ok($resp);
      break;
    
    case 'buscar-asignacion':
      if ($_SERVER['REQUEST_METHOD'] !== 'GET') err('Method Not Allowed', 405);
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) err('Id de asignación inválido.', 422);

      $repo = new Asignacion();
      $enc  = $repo->obtenerEncabezado($id);
      if (!$enc) err('Asignación no encontrada.', 404);

      $pedidos = $repo->obtenerPedidos($id);

      ok([
        'asignacion' => [
          'id'              => (int)$enc['id'],
          'fechaProgramada' => $enc['fechaProgramada'],
          'fecCreacion'     => $enc['fecCreacion'],
          'estado'          => $enc['estado']
        ],
        'repartidor' => [
          'idTrabajador' => (int)$enc['idTrabajador'],
          'dni'          => $enc['dni'],
          'nombre'       => $enc['nombre'],
          'apePat'       => $enc['apePat'],
          'apeMat'       => $enc['apeMat'],
          'telefono'     => $enc['telefono'],
          'email'        => $enc['email'],
          'cargo'        => $enc['cargo']
        ],
        'vehiculo' => [
          'idVehiculo' => (int)$enc['idVehiculo'],
          'marca'      => $enc['vehMarca'],
          'placa'      => $enc['vehPlaca'],
          'modelo'     => $enc['vehModelo']
        ],
        'pedidos' => $pedidos
      ]);
      break;

    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado', 500, ['detail' => $e->getMessage()]);
}
