<?php
final class Requerimiento
{
  private $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /* ==========================================================
     1) Listar requerimientos aprobados para evaluación
        → SOLO t407/t408/t86
     ========================================================== */
  public function listarAprobadosParaEvaluacion(): array
{
  $sql = "
    SELECT
      ev.Id_ReqEvaluacion,
      DATE(ev.FechaEvaluacion) AS fecha,
      ev.Estado AS estado,
      (
        SELECT COUNT(*)
        FROM t408DetalleReqEvaluado de
        WHERE de.Id_ReqEvaluacion = ev.Id_ReqEvaluacion
      ) AS items,
      (
        SELECT COUNT(*)
        FROM t86Cotizacion c
        WHERE c.Id_ReqEvaluacion = ev.Id_Requerimiento
          AND c.Estado = 'Recibida'
      ) AS cotizaciones_bd
    FROM t407RequerimientoEvaluado ev
    WHERE ev.Estado IN ('Aprobado', 'Parcialmente Aprobado')
      AND ev.Id_ReqEvaluacion = (
        SELECT e2.Id_ReqEvaluacion
        FROM t407RequerimientoEvaluado e2
        WHERE e2.Id_Requerimiento = ev.Id_Requerimiento
          AND e2.Estado IN ('Aprobado', 'Parcialmente Aprobado')
        ORDER BY e2.FechaEvaluacion DESC, e2.Id_ReqEvaluacion DESC
        LIMIT 1
      )
    ORDER BY ev.FechaEvaluacion DESC
  ";

  $rs = mysqli_query($this->cn, $sql);
  if (!$rs) return [];

  $list = [];
  while ($row = mysqli_fetch_assoc($rs)) {
    $list[] = [
      'id'        => (int)$row['Id_ReqEvaluacion'],        // 👈 el front usará ESTO
      'fecha'     => $row['fecha'],
      'items'     => (int)$row['items'],
      'estado'    => $row['estado'],
      'cotizaciones' => [
        'listas'     => (int)($row['cotizaciones_bd'] ?? 0),
        'detectadas' => 0
      ],
      'tieneEvaluacion' => true,
    ];
  }
  return $list;
}


  /* ==========================================================
     2) Obtener encabezado del requerimiento
        → SOLO t407 + t406
     ========================================================== */
  public function obtenerEncabezado(string $idEval): ?array
{
  $sql = "
    SELECT 
      ev.Id_ReqEvaluacion,
      ev.Id_Requerimiento,
      ev.FechaEvaluacion,
      ev.MontoSolicitado,
      ev.MontoAprobado,
      ev.Estado           AS EstadoEvaluacion,
      pp.CodigoPartida,
      pp.Descripcion      AS DescripcionPartida,
      pp.MontoPeriodo,
      (pp.MontoPeriodo - pp.MontoConsumido) AS SaldoDisponible
    FROM t407RequerimientoEvaluado ev
    LEFT JOIN t406PartidaPeriodo pp
      ON ev.Id_PartidaPeriodo = pp.Id_PartidaPeriodo
    WHERE ev.Id_ReqEvaluacion = ?
    LIMIT 1
  ";

  $st = mysqli_prepare($this->cn, $sql);
  mysqli_stmt_bind_param($st, "i", $idEval);
  mysqli_stmt_execute($st);
  $rs  = mysqli_stmt_get_result($st);
  $row = $rs ? mysqli_fetch_assoc($rs) : null;
  mysqli_stmt_close($st);

  if (!$row) return null;

  $total = (float)($row['MontoAprobado'] ?? 0.0);

  $result = [
    'id'             => (int)$row['Id_ReqEvaluacion'],   // id de la evaluación
    'idReqOrig'      => (int)$row['Id_Requerimiento'],   // requerimiento “padre”
    'fecha'          => $row['FechaEvaluacion'],
    'Total'          => $total,
    'PrecioPromedio' => 0.0, // si quieres, luego lo calculas desde t408
    'Estado'         => $row['EstadoEvaluacion'],
    'evaluacion'     => [
      'montoSolicitado'   => (float)$row['MontoSolicitado'],
      'montoAprobado'     => (float)$row['MontoAprobado'],
      'estado'            => $row['EstadoEvaluacion'],
      'fechaEvaluacion'   => $row['FechaEvaluacion'],
      'partida' => [
        'codigo'          => $row['CodigoPartida'],
        'descripcion'     => $row['DescripcionPartida'],
        'presupuesto'     => (float)$row['MontoPeriodo'],
        'saldoDisponible' => (float)$row['SaldoDisponible']
      ]
    ]
  ];

  return $result;
}


  /* ==========================================================
     3) Obtener detalle del requerimiento evaluado
        → BASE REAL = t408
        → NO t15; “Solicitado” = “Aprobado” si no se quiere el histórico
     ========================================================== */
  public function obtenerDetalle(string $idEval): array
{
  $sql = "
    SELECT 
      de.Id_DetalleEvaluacion AS Id_Detalle,
      de.Id_Producto,
      p.NombreProducto AS Nombre,
      u.Descripcion    AS UnidadMedida,
      de.Cantidad      AS CantidadAprobada,
      de.Precio        AS PrecioAprobado,
      (de.Cantidad * de.Precio) AS MontoAsignado,
      ev.Estado        AS EstadoProducto
    FROM t408DetalleReqEvaluado de
    INNER JOIN t407RequerimientoEvaluado ev
      ON ev.Id_ReqEvaluacion = de.Id_ReqEvaluacion
    INNER JOIN t18CatalogoProducto p 
      ON p.Id_Producto = de.Id_Producto
    LEFT JOIN t34UnidadMedida u 
      ON u.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
    WHERE de.Id_ReqEvaluacion = ?
    ORDER BY p.NombreProducto
  ";

  $st = mysqli_prepare($this->cn, $sql);
  mysqli_stmt_bind_param($st, "i", $idEval);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);

  $rows = [];
  while ($r = mysqli_fetch_assoc($rs)) {
    $cantAprob = (float)$r['CantidadAprobada'];
    $precAprob = (float)$r['PrecioAprobado'];
    $montoAsig = (float)$r['MontoAsignado'];

    $rows[] = [
      'Id_Detalle'         => (int)$r['Id_Detalle'],
      'Id_Producto'        => (int)$r['Id_Producto'],
      'Nombre'             => $r['Nombre'],
      'UnidadMedida'       => $r['UnidadMedida'] ?? 'UND',

      'CantidadAprobada'   => $cantAprob,
      'PrecioAprobado'     => $precAprob,
      'MontoAsignado'      => $montoAsig,
      'EstadoProducto'     => $r['EstadoProducto'] ?? 'Pendiente',

      // si ya no quieres “solicitado”, lo igualamos:
      'CantidadSolicitada' => $cantAprob,
      'PrecioSolicitado'   => $precAprob,
      'TipoAprobacion'     => 'Evaluado',
    ];
  }
  mysqli_stmt_close($st);
  return $rows;
}


public function actualizarEstado(string $idEval, string $nuevoEstado): void
{
  $sql = "UPDATE t407RequerimientoEvaluado
          SET Estado = ?
          WHERE Id_ReqEvaluacion = ?";
  $st = mysqli_prepare($this->cn, $sql);
  mysqli_stmt_bind_param($st, "si", $nuevoEstado, $idEval);
  mysqli_stmt_execute($st);
  mysqli_stmt_close($st);
}


  /* ==========================================================
     5) Recalcular totales desde t408 (solo cálculo, sin UPDATE)
     ========================================================== */
  public function recomputarTotales(string $idEval): array
{
  $sql = "
    SELECT 
      COALESCE(SUM(de.Cantidad * de.Precio), 0) AS total,
      COALESCE(AVG(de.Precio), 0)              AS prom
    FROM t408DetalleReqEvaluado de
    WHERE de.Id_ReqEvaluacion = ?
  ";

  $st = mysqli_prepare($this->cn, $sql);
  mysqli_stmt_bind_param($st, "i", $idEval);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);
  $r  = $rs ? mysqli_fetch_assoc($rs) : ['total' => 0, 'prom' => 0];
  mysqli_stmt_close($st);

  return [
    'total' => (float)($r['total'] ?? 0),
    'prom'  => (float)($r['prom']  ?? 0),
  ];
}


  /* ==========================================================
     6) Obtener última evaluación presupuestaria (t407/t406)
     ========================================================== */
  public function obtenerUltimaEvaluacion(string $idReq): ?array
  {
    $sql = "
      SELECT 
        ev.Id_ReqEvaluacion,
        ev.FechaEvaluacion,
        ev.CriterioEvaluacion,
        ev.MontoSolicitado,
        ev.MontoAprobado,
        ev.SaldoRestantePeriodo,
        ev.Estado,
        ev.Observaciones,
        pp.CodigoPartida,
        pp.Descripcion AS PartidaDescripcion,
        pp.Mes AS PartidaMes
      FROM t407RequerimientoEvaluado ev
      INNER JOIN t406PartidaPeriodo pp 
        ON ev.Id_PartidaPeriodo = pp.Id_PartidaPeriodo
      WHERE ev.Id_Requerimiento = ?
      ORDER BY ev.FechaEvaluacion DESC, ev.Id_ReqEvaluacion DESC
      LIMIT 1
    ";
    
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$row) return null;

    return [
      'id'              => (int)$row['Id_ReqEvaluacion'],
      'fechaEvaluacion' => $row['FechaEvaluacion'],
      'criterio'        => $row['CriterioEvaluacion'],
      'montoSolicitado' => (float)$row['MontoSolicitado'],
      'montoAprobado'   => (float)$row['MontoAprobado'],
      'saldoRestante'   => (float)$row['SaldoRestantePeriodo'],
      'estado'          => $row['Estado'],
      'observaciones'   => $row['Observaciones'],
      'partida' => [
        'codigo'      => $row['CodigoPartida'],
        'descripcion' => $row['PartidaDescripcion'],
        'mes'         => $row['PartidaMes']
      ]
    ];
  }

  /* ==========================================================
     7) Verificar si tiene evaluación presupuestaria
     ========================================================== */
  public function tieneEvaluacionPresupuestal(string $idReq): bool
  {
    $sql = "SELECT EXISTS(
              SELECT 1 
              FROM t407RequerimientoEvaluado 
              WHERE Id_Requerimiento = ?
            ) AS tiene";
    
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    return $row ? (bool)$row['tiene'] : false;
  }
}
