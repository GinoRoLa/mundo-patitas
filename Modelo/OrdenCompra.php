<?php
class OrdenCompra
{
  private mysqli $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /**
   * Genera OCs (t06/t07) por proveedor desde una adjudicación.
   * @param int|string $idReq
   * @param array      $adjud   (por proveedor o por producto; ambas formas soportadas)
   * @return array     [{ idOC, ruc, razon, subtotal, igv, total, items:[…], idempotent }]
   */
  public function crearOCsDesdeAdjudicacion($idReq, array $adjud): array
  {
    $porProv = $this->normalizarAdjudicacion($adjud);
    if (empty($porProv)) throw new Exception("Adjudicación vacía.");

    $t06TieneHuella = $this->t06TieneColumnaHuella();

    mysqli_begin_transaction($this->cn);
    try {
      $salida = [];

      foreach ($porProv as $prov) {
        $ruc   = trim((string)($prov['ruc'] ?? ''));
        $razon = trim((string)($prov['razon'] ?? ''));
        $items = $prov['items'] ?? [];
        if (!$ruc || empty($items)) continue;

        // 1) Calcular totales con %IGV desde t06 (18% por defecto).
        $porcIGV = 18.00; // default; luego lo guardamos en cabecera
        $sub = 0.0;
        foreach ($items as $it) {
          $cant = (float)($it['Cantidad'] ?? 0);
          $prec = (float)($it['Precio']   ?? 0);
          if ($cant <= 0 || $prec <= 0) throw new Exception("Ítem inválido para RUC $ruc");
          $sub += round($cant * $prec, 2);
        }
        $igv = round($sub * ($porcIGV/100.0), 2);
        $tot = round($sub + $igv, 2);

        // 2) Fingerprint estable (recomendado si existe t06.Huella)
        $fpData = [
          'idReq' => (string)$idReq,
          'ruc'   => $ruc,
          'items' => array_values(array_map(function($x){
            return [
              'Id_Producto'=>(int)$x['Id_Producto'],
              'Cantidad'   =>(float)$x['Cantidad'],
              'Precio'     =>(float)$x['Precio'],
              'Unidad'     =>(string)($x['Unidad'] ?? ''),
              'Descripcion'=>(string)($x['Descripcion'] ?? ''),
            ];
          }, $items)),
        ];
        $finger = hash('sha256', json_encode($fpData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        // 3) Idempotencia: si existe Huella, úsala; si no, heurística.
        if ($t06TieneHuella) {
          $prev = $this->buscarOcPorHuella($finger);
          if ($prev) {
            $salida[] = $prev + ['items'=>$items, 'idempotent'=>true];
            continue;
          }
        } else {
          $prev = $this->buscarOcSimilar($idReq, $ruc, $tot);
          if ($prev) {
            $salida[] = $prev + ['items'=>$items, 'idempotent'=>true];
            continue;
          }
        }

        // 4) Insert cabecera t06
        if ($t06TieneHuella) {
          $sqlCab = "INSERT INTO t06OrdenCompra
            (Fec_Emision, RUC_Proveedor, RazonSocial, Id_Requerimiento, Moneda,
             PorcentajeIGV, SubTotal, Impuesto, MontoTotal, Estado, Huella)
            VALUES (NOW(), ?, ?, ?, 'PEN', ?, 0, 0, 0, 'Emitida', ?)";
          $st = mysqli_prepare($this->cn, $sqlCab);
          mysqli_stmt_bind_param($st, "ssids", $ruc, $razon, $idReq, $porcIGV, $finger);
        } else {
          $sqlCab = "INSERT INTO t06OrdenCompra
            (Fec_Emision, RUC_Proveedor, RazonSocial, Id_Requerimiento, Moneda,
             PorcentajeIGV, SubTotal, Impuesto, MontoTotal, Estado)
            VALUES (NOW(), ?, ?, ?, 'PEN', ?, 0, 0, 0, 'Emitida')";
          $st = mysqli_prepare($this->cn, $sqlCab);
          mysqli_stmt_bind_param($st, "ssid", $ruc, $razon, $idReq, $porcIGV);
        }
        mysqli_stmt_execute($st);
        $idOC = (int)mysqli_insert_id($this->cn);
        mysqli_stmt_close($st);

        // 5) Insert detalle t07
        $sqlDet = "INSERT INTO t07DetalleOrdenCompra
  (Id_OrdenCompra, Id_Producto, Descripcion, Unidad, Cantidad, PrecioUnitario)
  VALUES (?,?,?,?,?,?)";
$sd = mysqli_prepare($this->cn, $sqlDet);
foreach ($items as $it) {
  $idp = (int)$it['Id_Producto'];
  $des = (string)($it['Descripcion'] ?? '');
  $uni = (string)($it['Unidad'] ?? null);
  $c   = max(1, (int)round((float)$it['Cantidad']));   // 👈 fuerza INT >= 1
  $p   = (float)$it['Precio'];
  mysqli_stmt_bind_param($sd, "iissid", $idOC, $idp, $des, $uni, $c, $p);
  mysqli_stmt_execute($sd);
}
mysqli_stmt_close($sd);

        // 6) Totales finales en cabecera (aunque t07.SubTotal es generado, sumamos por seguridad)
        [$subDB] = $this->sumarSubtotalesDetalle($idOC);
        $subFin = round($subDB ?? $sub, 2);
        $igvFin = round($subFin * ($porcIGV/100.0), 2);
        $totFin = round($subFin + $igvFin, 2);

        $up = mysqli_prepare($this->cn,
          "UPDATE t06OrdenCompra SET SubTotal=?, Impuesto=?, MontoTotal=? WHERE Id_OrdenCompra=?");
        mysqli_stmt_bind_param($up, "dddi", $subFin, $igvFin, $totFin, $idOC);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        // 7) Salida
        $salida[] = [
          'idOC'     => $idOC,
          'ruc'      => $ruc,
          'razon'    => $razon,
          'subtotal' => $subFin,
          'igv'      => $igvFin,
          'total'    => $totFin,
          'items'    => $items,
          'idempotent' => false,
        ];
      }

      mysqli_commit($this->cn);
      return $salida;

    } catch (\Throwable $e) {
      mysqli_rollback($this->cn);
      throw $e;
    }
  }

  /** Normaliza adjudicación tipo B (por producto) a A (por proveedor) */
  /** Normaliza adjudicación a formato A (por proveedor)
 *  A) [{ ruc, razon, items:[{Id_Producto,Descripcion,Unidad,Cantidad,Precio}, ...] }, ...]
 *  B) [{ Id_Producto/Nombre/UnidadMedida, asignacion/Asignacion:[{ruc/RUC/RUC_Proveedor, cantidad/Cantidad, precio/Precio/PrecioUnitario, costo}] }, ...]
 */
private function normalizarAdjudicacion(array $adjud): array
{
  // Si ya viene por proveedor (A)
  $esA = isset($adjud[0]) && is_array($adjud[0]) && array_key_exists('ruc', $adjud[0]) && array_key_exists('items', $adjud[0]);
  if ($esA) return $adjud;

  $porProv = [];

  foreach ($adjud as $prod) {
    if (!is_array($prod)) continue;

    // --- Producto / descripción / unidad ---
    $idp = (int)($prod['Id_Producto'] ?? $prod['idProducto'] ?? $prod['ProductoId'] ?? 0);
    $desc = (string)(
      $prod['Nombre'] ?? $prod['Descripcion'] ?? $prod['descripcion'] ?? $prod['nombre'] ?? ''
    );
    $um = (string)(
      $prod['UnidadMedida'] ?? $prod['Unidad'] ?? $prod['unidad'] ?? 'UND'
    );

    // --- Lista de asignaciones (soporta 'asignacion' o 'Asignacion') ---
    $asigs = $prod['asignacion'] ?? $prod['Asignacion'] ?? [];
    if (!is_array($asigs)) $asigs = [];

    foreach ($asigs as $a) {
      if (!is_array($a)) continue;

      // ruc / razon
      $ruc = (string)(
        $a['ruc'] ?? $a['RUC'] ?? $a['RUC_Proveedor'] ?? $a['proveedorRuc'] ?? ''
      );
      $razon = (string)(
        $a['proveedor'] ?? $a['nombre'] ?? $a['RazonSocial'] ?? $a['razon'] ?? ''
      );

      // cantidades/precios con fallbacks
      $cant = (float)($a['cantidad'] ?? $a['Cantidad'] ?? 0);
      $prec = (float)($a['precio']   ?? $a['Precio']   ?? $a['PrecioUnitario'] ?? 0);
      $costo= (float)($a['costo']    ?? $a['Costo']    ?? 0);

      if ($cant > 0 && $prec <= 0 && $costo > 0) {
        $prec = $costo / $cant; // fallback auto
      }

      // descarta realmente inválidos
      if ($ruc === '' || $cant <= 0 || $prec <= 0) continue;

      if (!isset($porProv[$ruc])) {
        $porProv[$ruc] = ['ruc' => $ruc, 'razon' => $razon, 'items' => []];
      }
      $porProv[$ruc]['items'][] = [
        'Id_Producto' => $idp,
        'Descripcion' => $desc,
        'Unidad'      => $um,
        'Cantidad'    => $cant,
        'Precio'      => $prec,
      ];
    }
  }

  return array_values($porProv);
}


  private function t06TieneColumnaHuella(): bool
  {
    $rs = mysqli_query($this->cn, "SHOW COLUMNS FROM t06OrdenCompra LIKE 'Huella'");
    $ok = $rs && mysqli_num_rows($rs) > 0;
    if ($rs) mysqli_free_result($rs);
    return $ok;
  }

  private function buscarOcPorHuella(string $fp): ?array
  {
    $q = "SELECT Id_OrdenCompra, RUC_Proveedor, RazonSocial, SubTotal, Impuesto, MontoTotal
          FROM t06OrdenCompra WHERE Huella=? LIMIT 1";
    $st = mysqli_prepare($this->cn, $q);
    mysqli_stmt_bind_param($st, "s", $fp);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$row) return null;
    return [
      'idOC'    => (int)$row['Id_OrdenCompra'],
      'ruc'     => $row['RUC_Proveedor'],
      'razon'   => $row['RazonSocial'],
      'subtotal'=> (float)$row['SubTotal'],
      'igv'     => (float)$row['Impuesto'],
      'total'   => (float)$row['MontoTotal'],
    ];
  }

  /** Heurística anti-duplicado sin huella: misma Req + RUC + Total en 24h */
  private function buscarOcSimilar($idReq, string $ruc, float $total): ?array
  {
    $q = "SELECT Id_OrdenCompra, RUC_Proveedor, RazonSocial, SubTotal, Impuesto, MontoTotal
          FROM t06OrdenCompra
          WHERE Id_Requerimiento=? AND RUC_Proveedor=? AND MontoTotal=? 
            AND Fec_Emision >= (NOW() - INTERVAL 1 DAY)
          LIMIT 1";
    $st = mysqli_prepare($this->cn, $q);
    mysqli_stmt_bind_param($st, "isd", $idReq, $ruc, $total);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$row) return null;
    return [
      'idOC'    => (int)$row['Id_OrdenCompra'],
      'ruc'     => $row['RUC_Proveedor'],
      'razon'   => $row['RazonSocial'],
      'subtotal'=> (float)$row['SubTotal'],
      'igv'     => (float)$row['Impuesto'],
      'total'   => (float)$row['MontoTotal'],
    ];
  }

  private function sumarSubtotalesDetalle(int $idOC): array
  {
    $q = "SELECT COALESCE(SUM(SubTotal),0) AS sub FROM t07DetalleOrdenCompra WHERE Id_OrdenCompra=?";
    $st = mysqli_prepare($this->cn, $q);
    mysqli_stmt_bind_param($st, "i", $idOC);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : ['sub'=>0];
    mysqli_stmt_close($st);
    return [(float)$row['sub']];
  }

  public function obtenerParaPDF(int $idOC): array
{
  // Cabecera + proveedor
  $qCab = "SELECT oc.Id_OrdenCompra, oc.Fec_Emision, oc.RUC_Proveedor, oc.RazonSocial,
                oc.Id_Requerimiento, oc.Moneda, oc.PorcentajeIGV,
                oc.SubTotal, oc.Impuesto, oc.MontoTotal,
                p.Id_NumRuc,
                p.des_RazonSocial AS ProvRazon,
                p.DireccionProv  AS ProvDireccion,
                p.Correo         AS ProvCorreo,
                p.Telefono       AS ProvTelefono
         FROM t06OrdenCompra oc
         LEFT JOIN t17CatalogoProveedor p ON p.Id_NumRuc = oc.RUC_Proveedor
         WHERE oc.Id_OrdenCompra=?";
  $st = mysqli_prepare($this->cn, $qCab);
  mysqli_stmt_bind_param($st, "i", $idOC);
  mysqli_stmt_execute($st);
  $rs  = mysqli_stmt_get_result($st);
  $cab = $rs ? mysqli_fetch_assoc($rs) : null;
  mysqli_stmt_close($st);
  if (!$cab) throw new Exception("OC no encontrada (#$idOC)");

  // Detalle
  $qDet = "SELECT d.Id_Producto, d.Descripcion, d.Unidad, d.Cantidad, d.PrecioUnitario,
                  (d.Cantidad * d.PrecioUnitario) AS Total
           FROM t07DetalleOrdenCompra d
           WHERE d.Id_OrdenCompra=?
           ORDER BY d.Id_Detalle";
  $sd = mysqli_prepare($this->cn, $qDet);
  mysqli_stmt_bind_param($sd, "i", $idOC);
  mysqli_stmt_execute($sd);
  $rd = mysqli_stmt_get_result($sd);
  $det = [];
  while ($r = mysqli_fetch_assoc($rd)) $det[] = $r;
  mysqli_stmt_close($sd);

  // Cliente (tu empresa) – puedes reemplazar por tabla de parámetros si la tienes
  $empresa = [
    'RazonSocial' => 'Mundo Patitas S.A.C.',
    'RUC'         => '20123456789',
    'Direccion'   => 'Av. Siempre Viva 123, Lima',
    'Telefono'    => '(01) 555-0000',
    'Correo'      => 'mundopatitas.venta@gmail.com',
  ];

  return [
    'encabezado' => $cab,
    'detalle'    => $det,
    'empresa'    => $empresa,
    'proveedor'  => [
      'RazonSocial' => $cab['ProvRazon'] ?: $cab['RazonSocial'],
      'RUC'         => $cab['RUC_Proveedor'],
      'Direccion'   => $cab['ProvDireccion'] ?? '',
      'Telefono'    => $cab['ProvTelefono'] ?? '',
      'Correo'      => $cab['ProvCorreo']   ?? '',
    ],
  ];
}

public function listarPorRequerimientoConContacto($idReq): array
{
  $query = "
    SELECT 
      oc.Id_OrdenCompra,
      oc.RUC_Proveedor,
      oc.RazonSocial,
      oc.Moneda,
      oc.MontoTotal,
      oc.Estado,
      p.Correo        AS ProveedorCorreo,
      p.Telefono      AS ProveedorTelefono,
      p.DireccionProv AS ProveedorDireccion,
      p.des_RazonSocial AS ProveedorRazonSocial
    FROM t06OrdenCompra oc
    LEFT JOIN t17CatalogoProveedor p ON p.Id_NumRuc = oc.RUC_Proveedor
    WHERE oc.Id_Requerimiento = ?
    ORDER BY oc.Id_OrdenCompra
  ";

  $stmt = mysqli_prepare($this->cn, $query);
  if (!$stmt) {
    throw new Exception("Error preparando query: " . mysqli_error($this->cn));
  }

  $idReqInt = (int)$idReq;
  mysqli_stmt_bind_param($stmt, "i", $idReqInt);
  mysqli_stmt_execute($stmt);

  $result = mysqli_stmt_get_result($stmt);
  $ordenes = [];

  while ($row = mysqli_fetch_assoc($result)) {
    $ordenes[] = $row;
  }

  mysqli_stmt_close($stmt);
  return $ordenes;
}


/**
 * Extrae email y nombre del proveedor desde una fila de OC
 * @param array $ocRow Fila con datos de la OC y proveedor
 * @return array [email, nombre]
 */
public function correoNombreProveedor(array $ocRow): array
{
  // Prioridad: datos de t17CatalogoProveedor, luego de t06OrdenCompra
  $email = trim((string)($ocRow['ProveedorCorreo'] ?? $ocRow['Correo'] ?? ''));
  
  $nombre = trim((string)(
    $ocRow['ProveedorRazonSocial'] ?? 
    $ocRow['des_RazonSocial'] ?? 
    $ocRow['RazonSocial'] ?? 
    ''
  ));
  
  // Si no hay nombre, usar RUC como fallback
  if ($nombre === '' && !empty($ocRow['RUC_Proveedor'])) {
    $nombre = "Proveedor RUC " . $ocRow['RUC_Proveedor'];
  }
  
  return [$email, $nombre];
}

/**
 * Cuenta cuántos ítems tiene una OC
 * @param int $idOC
 * @return int
 */
public function contarItems(int $idOC): int
{
  $query = "SELECT COUNT(*) as total FROM t07DetalleOrdenCompra WHERE Id_OrdenCompra = ?";
  $stmt = mysqli_prepare($this->cn, $query);
  mysqli_stmt_bind_param($stmt, "i", $idOC);
  mysqli_stmt_execute($stmt);
  
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  
  return (int)($row['total'] ?? 0);
}

/**
 * Obtiene resumen de una OC para logs/mensajes
 * @param int $idOC
 * @return array|null
 */
public function obtenerResumen(int $idOC): ?array
{
  $query = "
    SELECT 
      Id_OrdenCompra,
      RUC_Proveedor,
      RazonSocial,
      MontoTotal,
      Estado,
      Fec_Emision
    FROM t06OrdenCompra
    WHERE Id_OrdenCompra = ?
  ";
  
  $stmt = mysqli_prepare($this->cn, $query);
  mysqli_stmt_bind_param($stmt, "i", $idOC);
  mysqli_stmt_execute($stmt);
  
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  mysqli_stmt_close($stmt);
  
  return $row ?: null;
}

}
