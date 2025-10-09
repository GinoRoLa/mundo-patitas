<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'Conexion.php';
require_once '../Modelo/Trabajador.php';
require_once '../Modelo/Almacen.php';
require_once '../Modelo/Asignacion.php';
require_once '../Modelo/Producto.php';
require_once '../Modelo/Guia.php';

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
      //if (!$enc) err('Asignación no encontrada.', 404);

      $pedidos = $repo->obtenerPedidos($id);
      $licNum    = $enc['numLicencia']     ?? null;
      $licEstado = $enc['licenciaEstado']  ?? null;

      ok([
        'asignacion' => [
          'id'              => (int)$enc['id'],
          'idAsignacionRV'  => (int)$enc['idAsignacionRV'],   // <-- AQUI
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

          // ✔️ campo plano para compatibilidad con tu front:
          'licencia'     => $licNum,

          // ✔️ bloque detallado si lo quieres aprovechar:
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

      $dni = null;
      $nom = null;
      $dir = null;
      $dist = null;
      if (!empty($items)) {
        $dni = $items[0]['receptorDni']   ?? null;
        $nom = $items[0]['receptorNombre'] ?? null;
        $dir = $items[0]['direccionSnap'] ?? null;
        $dist = $items[0]['idDistrito']    ?? null;
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

    case 'generar-salida':
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Method Not Allowed', 405);

      $in = json_decode(file_get_contents('php://input'), true) ?? [];

      // Validaciones mínimas
      if (empty($in['ops']) || !is_array($in['ops'])) err('Lista de OPs vacía o inválida', 422);
      if (empty($in['origen']['id'])) err('Origen inválido', 422);
      if (empty($in['anchor']['dni']) || empty($in['anchor']['direccion'])) err('Anchor (dni/dirección) inválido', 422);
      if (!isset($in['asignacionRV']) || !$in['asignacionRV']) err('Falta Id_AsignacionRepartidorVehiculo', 422); // <-- AQUI

      // 1) Registrar salidas
      $almacenM = new Almacen();
      $almacenM->registrarSalida($in['ops']); // SP

      // 2) Crear guía
      $guiaM = new Guia();
      $guia  = $guiaM->crearGuiaSinNumerador([
        'serie'              => '001',
        'remitenteRuc'       => '20123456789',
        'remitenteRazon'     => 'Mundo Patitas SAC',
        'destinatarioNombre' => $in['destinatarioNombre'] ?? '',
        'dniReceptor'        => $in['anchor']['dni'] ?? '',
        'direccionDestino'   => $in['anchor']['direccion'] ?? '',
        'distritoDestino'    => $in['anchor']['distrito'] ?? '',
        'idDireccionAlmacen' => (int)$in['origen']['id'],
        'idAsignacionRV'     => (int)$in['asignacionRV'],
        'marca'              => $in['vehiculo']['marca'] ?? '',
        'placa'              => $in['vehiculo']['placa'] ?? '',
        'conductor'          => $in['transportista']['conductor'] ?? '',
        'licencia'           => $in['transportista']['licencia'] ?? '',
        'motivo'             => 'Venta'
      ]);


      // 3) Insertar el detalle de la guía a partir de las OPs
      $guiaM->insertarDetalleDesdeOps($guia['idGuia'], $in['ops']);

      // 4) (Opcional) Cargar encabezado de la asignación para devolver meta SIN warnings
      $asigId = (int)($in['asignacionId'] ?? 0);
      $asignacionMeta = [
        'id'             => 0,
        'idAsignacionRV' => null,
        'fechaProgramada' => null,
        'fecCreacion'    => null,
        'estado'         => null,
      ];
      if ($asigId > 0) {
        $repo = new Asignacion();
        $enc  = $repo->obtenerEncabezado($asigId); // devuelve array o null
        if ($enc) {
          $asignacionMeta = [
            'id'             => (int)($enc['id'] ?? 0),
            'idAsignacionRV' => $enc['idAsignacionRV'] ?? null,
            'fechaProgramada' => $enc['fechaProgramada'] ?? null,
            'fecCreacion'    => $enc['fecCreacion'] ?? null,
            'estado'         => $enc['estado'] ?? null,
          ];
        }
      }

      ok([
        'asignacion'    => $asignacionMeta,
        'guiaId'        => $guia['idGuia'],
        'guiaNumero'    => $guia['numero'],
        'guiaNumeroStr' => $guia['numeroStr'],
      ]);
      break;


    default:
      err('Acción no encontrada', 404, ['accion' => $accion]);
  }
} catch (Throwable $e) {
  err('Error inesperado', 500, ['detail' => $e->getMessage()]);
}
