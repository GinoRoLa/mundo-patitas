<?php
class OrdenCompra
{
  private mysqli $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /** Verifica que una evaluación exista y esté Aprobado/Parcialmente Aprobado */
  private function evaluacionEsValida(int $idEval): bool
  {
    $sql = "
    SELECT 1
    FROM t407RequerimientoEvaluado
    WHERE Id_ReqEvaluacion = ?
      AND Estado IN ('Aprobado','Parcialmente Aprobado')
    LIMIT 1
  ";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idEval);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    return (bool)$row;
  }


  /**
   * Genera OCs (t06/t07) por proveedor desde una adjudicación.
   * $idEval = Id_ReqEvaluacion (t407)
   *
   * @param int|string $idEval Id_ReqEvaluacion
   * @param array      $adjud  (por proveedor o por producto; ambas formas soportadas)
   * @return array     [{ idOC, ruc, razon, subtotal, igv, total, items:[…], idempotent }]
   * @throws Exception
   */
  public function crearOCsDesdeAdjudicacion($idEval, array $adjud): array
{
  $idEval = (int)$idEval;
  if ($idEval <= 0) {
    throw new Exception("Id_ReqEvaluacion inválido.");
  }

  if (!$this->evaluacionEsValida($idEval)) {
    throw new Exception("Evaluación no está en estado Aprobado/Parcialmente Aprobado.");
  }

  $porProv = $this->normalizarAdjudicacion($adjud);
  if (empty($porProv)) {
    throw new Exception("Adjudicación vacía.");
  }

  $erroresCobertura = $this->validarCoberturaCompleta($idEval, $porProv);
  if (!empty($erroresCobertura)) {
    $msg = "No se puede generar las OCs: las cantidades adjudicadas no cubren totalmente lo aprobado.";
    throw new Exception($msg . " Detalle: " . json_encode($erroresCobertura));
  }

  $t06TieneHuella = $this->t06TieneColumnaHuella();
  mysqli_begin_transaction($this->cn);

  try {
    $salida = [];
    $tiempoEntrega = 15; // 🔹 Valor por defecto

    foreach ($porProv as $prov) {
      $ruc   = trim((string)($prov['ruc'] ?? ''));
      $razon = trim((string)($prov['razon'] ?? ''));
      $items = $prov['items'] ?? [];
      if (!$ruc || empty($items)) continue;

      $idCot = $this->buscarCotizacionPorReqYRuc($idEval, $ruc);

      // 2) Calcular totales
      $porcIGV = 18.00;
      $sub = 0.0;
      foreach ($items as $it) {
        $cant = (float)($it['Cantidad'] ?? 0);
        $prec = (float)($it['Precio'] ?? 0);
        if ($cant <= 0 || $prec <= 0) {
          throw new Exception("Ítem inválido para RUC $ruc");
        }
        $sub += round($cant * $prec, 2);
      }
      $igv = round($sub * ($porcIGV / 100.0), 2);
      $tot = round($sub + $igv, 2);

      // 3) Fingerprint
      $fpData = [
        'idEval' => $idEval,
        'ruc'    => $ruc,
        'items'  => array_values(array_map(fn($x) => [
          'Id_Producto' => (int)$x['Id_Producto'],
          'Cantidad'    => (float)$x['Cantidad'],
          'Precio'      => (float)$x['Precio'],
          'Unidad'      => (string)($x['Unidad'] ?? ''),
          'Descripcion' => (string)($x['Descripcion'] ?? '')
        ], $items))
      ];
      $finger = hash('sha256', json_encode($fpData, JSON_UNESCAPED_UNICODE));

      // 4) Idempotencia
      if ($t06TieneHuella) {
        $prev = $this->buscarOcPorHuella($finger);
        if ($prev) {
          $salida[] = $prev + ['items' => $items, 'idempotent' => true];
          continue;
        }
      } else {
        $prev = $this->buscarOcSimilar($idEval, $ruc, $tot);
        if ($prev) {
          $salida[] = $prev + ['items' => $items, 'idempotent' => true];
          continue;
        }
      }

      $serie = date('Y');
      $numeroTmp = '';

      // 5) Insert cabecera t06 (añadido NumeroOrdenCompra en el INSERT)
      if ($t06TieneHuella) {
        $sqlCab = "INSERT INTO t06OrdenCompra
          (Fec_Emision, Serie, NumeroOrdenCompra,
           RUC_Proveedor, RazonSocial, Id_ReqEvaluacion, Id_Cotizacion,
           TiempoEntregaDias, Moneda, PorcentajeIGV, SubTotal, Impuesto, MontoTotal, Estado, Huella)
          VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, 'PEN', ?, 0, 0, 0, 'Emitida', ?)";

        $st = mysqli_prepare($this->cn, $sqlCab);
        // tipos: Serie(s), NumeroTmp(s), RUC(s), Razon(s), IdEval(i), IdCot(i), TiempoEntrega(i), PorcIGV(d), Huella(s)
        mysqli_stmt_bind_param(
          $st,"ssssiiids",$serie,$numeroTmp,$ruc,$razon,$idEval,$idCot,$tiempoEntrega,$porcIGV,$finger);
      } else {
        $sqlCab = "INSERT INTO t06OrdenCompra
          (Fec_Emision, Serie, NumeroOrdenCompra,
           RUC_Proveedor, RazonSocial, Id_ReqEvaluacion, Id_Cotizacion,
           TiempoEntregaDias, Moneda, PorcentajeIGV, SubTotal, Impuesto, MontoTotal, Estado)
          VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, 'PEN', ?, 0, 0, 0, 'Emitida')";

        $st = mysqli_prepare($this->cn, $sqlCab);
        // tipos: Serie(s), NumeroTmp(s), RUC(s), Razon(s), IdEval(i), IdCot(i), TiempoEntrega(i), PorcIGV(d)
        mysqli_stmt_bind_param(
          $st,
          "ssssiiid",$serie,$numeroTmp,$ruc,$razon,$idEval,$idCot,$tiempoEntrega,$porcIGV);
      }

      mysqli_stmt_execute($st);
      $idOC = (int)mysqli_insert_id($this->cn);
      mysqli_stmt_close($st);

      // 5b) Generar Número real y actualizar
      $numeroOC = 'OC' . $serie . '-' . str_pad((string)$idOC, 4, '0', STR_PAD_LEFT);

      $up = mysqli_prepare(
        $this->cn,
        "UPDATE t06OrdenCompra SET NumeroOrdenCompra=? WHERE Id_OrdenCompra=?"
      );
      mysqli_stmt_bind_param($up, "si", $numeroOC, $idOC);
      mysqli_stmt_execute($up);
      mysqli_stmt_close($up);

      // 6) Insert detalle
      $sqlDet = "INSERT INTO t07DetalleOrdenCompra
        (Id_OrdenCompra, Id_Producto, Descripcion, Unidad, Cantidad, PrecioUnitario)
        VALUES (?,?,?,?,?,?)";
      $sd = mysqli_prepare($this->cn, $sqlDet);
      foreach ($items as $it) {
        $idp = (int)$it['Id_Producto'];
        $des = (string)($it['Descripcion'] ?? '');
        $uni = (string)($it['Unidad'] ?? null);
        $c   = max(1, (int)round((float)$it['Cantidad']));
        $p   = (float)$it['Precio'];
        mysqli_stmt_bind_param($sd, "iissid", $idOC, $idp, $des, $uni, $c, $p);
        mysqli_stmt_execute($sd);
      }
      mysqli_stmt_close($sd);

      // 7) Totales
      [$subDB] = $this->sumarSubtotalesDetalle($idOC);
      $subFin = round($subDB ?? $sub, 2);
      $igvFin = round($subFin * ($porcIGV / 100.0), 2);
      $totFin = round($subFin + $igvFin, 2);

      $up = mysqli_prepare(
        $this->cn,
        "UPDATE t06OrdenCompra SET SubTotal=?, Impuesto=?, MontoTotal=? WHERE Id_OrdenCompra=?"
      );
      mysqli_stmt_bind_param($up, "dddi", $subFin, $igvFin, $totFin, $idOC);
      mysqli_stmt_execute($up);
      mysqli_stmt_close($up);

      $salida[] = [
        'idOC'       => $idOC,
        'ruc'        => $ruc,
        'razon'      => $razon,
        'subtotal'   => $subFin,
        'igv'        => $igvFin,
        'total'      => $totFin,
        'items'      => $items,
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

  /**
   * Normaliza adjudicación a formato A (por proveedor)
   *  A) [{ ruc, razon, items:[{Id_Producto,Descripcion,Unidad,Cantidad,Precio}, ...] }, ...]
   *  B) [{ Id_Producto/Nombre/UnidadMedida, asignacion/Asignacion:[{ruc/RUC/RUC_Proveedor, cantidad/Cantidad, precio/Precio/PrecioUnitario, costo}] }, ...]
   */
  private function normalizarAdjudicacion(array $adjud): array
  {
    // Si ya viene por proveedor (A)
    $esA = isset($adjud[0]) && is_array($adjud[0])
      && array_key_exists('ruc', $adjud[0])
      && array_key_exists('items', $adjud[0]);
    if ($esA) return $adjud;

    $porProv = [];

    foreach ($adjud as $prod) {
      if (!is_array($prod)) continue;

      // Producto / descripción / unidad
      $idp = (int)($prod['Id_Producto'] ?? $prod['idProducto'] ?? $prod['ProductoId'] ?? 0);
      $desc = (string)(
        $prod['Nombre'] ?? $prod['Descripcion'] ?? $prod['descripcion'] ?? $prod['nombre'] ?? ''
      );
      $um = (string)(
        $prod['UnidadMedida'] ?? $prod['Unidad'] ?? $prod['unidad'] ?? 'UND'
      );

      // Lista de asignaciones (soporta 'asignacion' o 'Asignacion')
      $asigs = $prod['asignacion'] ?? $prod['Asignacion'] ?? [];
      if (!is_array($asigs)) $asigs = [];

      foreach ($asigs as $a) {
        if (!is_array($a)) continue;

        $ruc = (string)(
          $a['ruc'] ?? $a['RUC'] ?? $a['RUC_Proveedor'] ?? $a['proveedorRuc'] ?? ''
        );
        $razon = (string)(
          $a['proveedor'] ?? $a['nombre'] ?? $a['RazonSocial'] ?? $a['razon'] ?? ''
        );

        $cant = (float)($a['cantidad'] ?? $a['Cantidad'] ?? 0);
        $prec = (float)($a['precio']   ?? $a['Precio']   ?? $a['PrecioUnitario'] ?? 0);
        $costo = (float)($a['costo']    ?? $a['Costo']    ?? 0);

        if ($cant > 0 && $prec <= 0 && $costo > 0) {
          $prec = $costo / $cant;
        }

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

  /** Resuelve la última evaluación aprobada/parcial para un Id_Requerimiento */
  private function obtenerUltimaEvaluacionId(int $idReq): ?int
  {
    $sql = "
      SELECT Id_ReqEvaluacion
      FROM t407RequerimientoEvaluado
      WHERE Id_Requerimiento = ?
        AND Estado IN ('Aprobado', 'Parcialmente Aprobado')
      ORDER BY FechaEvaluacion DESC, Id_ReqEvaluacion DESC
      LIMIT 1
    ";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    return $row ? (int)$row['Id_ReqEvaluacion'] : null;
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
      'subtotal' => (float)$row['SubTotal'],
      'igv'     => (float)$row['Impuesto'],
      'total'   => (float)$row['MontoTotal'],
    ];
  }

  /** Heurística anti-duplicado sin huella: misma evaluación + RUC + Total en 24h */
  private function buscarOcSimilar(int $idEval, string $ruc, float $total): ?array
  {
    $q = "SELECT Id_OrdenCompra, RUC_Proveedor, RazonSocial, SubTotal, Impuesto, MontoTotal
          FROM t06OrdenCompra
          WHERE Id_ReqEvaluacion=? AND RUC_Proveedor=? AND MontoTotal=? 
            AND Fec_Emision >= (NOW() - INTERVAL 1 DAY)
          LIMIT 1";
    $st = mysqli_prepare($this->cn, $q);
    mysqli_stmt_bind_param($st, "isd", $idEval, $ruc, $total);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$row) return null;
    return [
      'idOC'    => (int)$row['Id_OrdenCompra'],
      'ruc'     => $row['RUC_Proveedor'],
      'razon'   => $row['RazonSocial'],
      'subtotal' => (float)$row['SubTotal'],
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
    $row = $rs ? mysqli_fetch_assoc($rs) : ['sub' => 0];
    mysqli_stmt_close($st);
    return [(float)$row['sub']];
  }

  /** Cabecera + detalle para PDF */
  public function obtenerParaPDF(int $idOC): array
  {
    // === CABECERA PRINCIPAL ===
    $qCab = "SELECT 
      oc.Id_OrdenCompra,
      oc.Fec_Emision,
      oc.RUC_Proveedor,
      oc.RazonSocial,
      oc.Id_ReqEvaluacion,
      ev.Id_Requerimiento,
      oc.Moneda,
      oc.PorcentajeIGV,
      oc.SubTotal,
      oc.Impuesto,
      oc.MontoTotal,
      oc.TiempoEntregaDias,
      p.Id_NumRuc,
      p.des_RazonSocial AS ProvRazon,
      p.DireccionProv  AS ProvDireccion,
      p.Correo         AS ProvCorreo,
      p.Telefono       AS ProvTelefono,
      c.NroCotizacionProv,
      oc.NumeroOrdenCompra,
      c.Id_Cotizacion AS IdCotizacionInterna
    FROM t06OrdenCompra oc
    LEFT JOIN t407RequerimientoEvaluado ev
      ON ev.Id_ReqEvaluacion = oc.Id_ReqEvaluacion
    LEFT JOIN t17CatalogoProveedor p
      ON p.Id_NumRuc = oc.RUC_Proveedor
    LEFT JOIN t86Cotizacion c
      ON c.Id_Cotizacion = oc.Id_Cotizacion
    WHERE oc.Id_OrdenCompra = ?";

    $st = mysqli_prepare($this->cn, $qCab);
    mysqli_stmt_bind_param($st, "i", $idOC);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $cab = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$cab) throw new Exception("OC no encontrada (#$idOC)");

    // === DETALLE ===
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

    // === DIRECCIÓN DE ALMACÉN (para empresa) ===
    $qDir = "SELECT DireccionOrigen FROM t73DireccionAlmacen
           WHERE Estado='Activo' ORDER BY Id_DireccionAlmacen ASC LIMIT 1";
    $resDir = mysqli_query($this->cn, $qDir);
    $filaDir = $resDir ? mysqli_fetch_assoc($resDir) : null;
    $dirEmpresa = $filaDir['DireccionOrigen'] ?? 'Av. Siempre Viva 123, Lima'; // fallback

    // === DATOS DE LA EMPRESA ===
    $empresa = [
      'RazonSocial' => 'Mundo Patitas S.A.C.',
      'RUC'         => '20123456789',
      'Direccion'   => $dirEmpresa,
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


  /**
   * Lista OCs de un requerimiento lógico (Id_Requerimiento),
   * aunque t06 ahora guarda Id_ReqEvaluacion.
   */
  public function listarPorEvaluacionConContacto($idEval): array
  {
    $query = "
    SELECT 
      oc.Id_OrdenCompra,
      oc.Id_ReqEvaluacion,
      oc.Serie,
      oc.NumeroOrdenCompra,
      oc.RUC_Proveedor,
      oc.RazonSocial,
      oc.Moneda,
      oc.MontoTotal,
      oc.Estado,
      p.Correo          AS ProveedorCorreo,
      p.Telefono        AS ProveedorTelefono,
      p.DireccionProv   AS ProveedorDireccion,
      p.des_RazonSocial AS ProveedorRazonSocial
    FROM t06OrdenCompra oc
    LEFT JOIN t17CatalogoProveedor p
      ON p.Id_NumRuc = oc.RUC_Proveedor
    WHERE oc.Id_ReqEvaluacion = ?
    ORDER BY oc.Id_OrdenCompra
  ";

    $stmt = mysqli_prepare($this->cn, $query);
    $idEvalInt = (int)$idEval;
    mysqli_stmt_bind_param($stmt, "i", $idEvalInt);
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
    $email = trim((string)($ocRow['ProveedorCorreo'] ?? $ocRow['Correo'] ?? ''));
    $nombre = trim((string)(
      $ocRow['ProveedorRazonSocial'] ??
      $ocRow['des_RazonSocial'] ??
      $ocRow['RazonSocial'] ??
      ''
    ));
    if ($nombre === '' && !empty($ocRow['RUC_Proveedor'])) {
      $nombre = "Proveedor RUC " . $ocRow['RUC_Proveedor'];
    }
    return [$email, $nombre];
  }

  /** Cuenta cuántos ítems tiene una OC */
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

  /** Obtiene resumen de una OC para logs/mensajes */
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

  /**
   * Valida que la adjudicación cubra EXACTAMENTE las cantidades aprobadas en t408
   * para la evaluación $idEval (Id_ReqEvaluacion).
   *
   * @return array lista de errores por producto (vacío si todo OK)
   */
  private function validarCoberturaCompleta(int $idEval, array $porProv): array
  {
    // 1) Sumar lo adjudicado por producto
    $adjudPorProd = [];  // [Id_Producto => cantidadTotalAdjudicada]

    foreach ($porProv as $prov) {
      $items = $prov['items'] ?? [];
      foreach ($items as $it) {
        $idProd = (int)($it['Id_Producto'] ?? 0);
        $cant   = (float)($it['Cantidad']    ?? 0);
        if ($idProd <= 0 || $cant <= 0) continue;

        if (!isset($adjudPorProd[$idProd])) {
          $adjudPorProd[$idProd] = 0.0;
        }
        $adjudPorProd[$idProd] += $cant;
      }
    }

    // 2) Leer cantidades APROBADAS en t408 para esa evaluación
    $sql = "
    SELECT 
      de.Id_Producto,
      SUM(de.Cantidad) AS CantidadAprobada
    FROM t408DetalleReqEvaluado de
    WHERE de.Id_ReqEvaluacion = ?
    GROUP BY de.Id_Producto
  ";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idEval);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $errores = [];
    while ($row = $rs ? mysqli_fetch_assoc($rs) : null) {
      if (!$row) break;

      $idProd   = (int)$row['Id_Producto'];
      $aprobada = (float)$row['CantidadAprobada'];
      $adjud    = (float)($adjudPorProd[$idProd] ?? 0);

      if ($adjud < $aprobada) {
        $errores[] = [
          'Id_Producto'      => $idProd,
          'CantidadAprobada' => $aprobada,
          'CantidadAdjudicada' => $adjud,
          'tipo'             => 'faltante',
        ];
      } elseif ($adjud > $aprobada) {
        $errores[] = [
          'Id_Producto'      => $idProd,
          'CantidadAprobada' => $aprobada,
          'CantidadAdjudicada' => $adjud,
          'tipo'             => 'exceso',
        ];
      }
    }
    mysqli_stmt_close($st);

    return $errores;
  }

  private function buscarCotizacionPorReqYRuc(int $idEval, string $ruc): ?int
  {
    $sql = "SELECT Id_Cotizacion
          FROM t86Cotizacion
          WHERE Id_ReqEvaluacion = ? AND RUC_Proveedor = ?
          ORDER BY FechaEmision DESC
          LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "is", $idEval, $ruc);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);
    return $row ? (int)$row['Id_Cotizacion'] : null;
  }
}
