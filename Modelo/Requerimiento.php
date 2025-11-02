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
        (SIN romper: mismos campos + extras útiles)
     ========================================================== */
  public function listarAprobadosParaEvaluacion(): array
  {
    $sql = "SELECT 
              r.Id_Requerimiento AS id,
              DATE_FORMAT(r.FechaRequerimiento, '%Y-%m-%d') AS fecha,
              r.Estado AS estado,
              (SELECT COUNT(*) FROM t15DetalleRequerimientoCompra d 
                WHERE d.Id_Requerimiento = r.Id_Requerimiento) AS items,
              (SELECT COUNT(*) FROM t86Cotizacion c 
                WHERE c.Id_Requerimiento = r.Id_Requerimiento 
                  AND c.Estado = 'Recibida') AS cotizaciones_bd,
              /* extra: saber si ya tiene alguna evaluación */
              EXISTS(SELECT 1 FROM t407EvaluacionRequerimiento e
                      WHERE e.Id_Requerimiento = r.Id_Requerimiento) AS tiene_eval
            FROM t14RequerimientoCompra r
            WHERE r.Estado IN ('Aprobado','Parcialmente Aprobado')
            ORDER BY r.FechaRequerimiento DESC";
    
    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) return [];
    
    $list = [];
    while ($row = mysqli_fetch_assoc($rs)) {
      $list[] = [
        'id'     => (int)$row['id'],
        'fecha'  => $row['fecha'],
        'items'  => (int)$row['items'],
        'estado' => $row['estado'],
        'cotizaciones' => [
          'listas'     => (int)($row['cotizaciones_bd'] ?? 0),
          'detectadas' => 0
        ],
        // extra no usado por el front actual, pero disponible
        'tieneEvaluacion' => ((int)$row['tiene_eval'] === 1)
      ];
    }
    return $list;
  }

  /* ==========================================================
     2) Obtener encabezado (igual que antes)
     ========================================================== */
  public function obtenerEncabezado(string $idReq): ?array
  {
    $sql = "SELECT 
              Id_Requerimiento AS id,
              FechaRequerimiento AS fecha,
              Total,
              PrecioPromedio,
              Estado
            FROM t14RequerimientoCompra
            WHERE Id_Requerimiento = ?";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    if (!$row) return null;

    return [
      'id'             => $row['id'],
      'fecha'          => $row['fecha'],
      'Total'          => isset($row['Total']) ? (float)$row['Total'] : 0.0,
      'PrecioPromedio' => isset($row['PrecioPromedio']) ? (float)$row['PrecioPromedio'] : 0.0,
      'Estado'         => $row['Estado'],
    ];
  }

  /* ==========================================================
     3) Obtener detalle del requerimiento
        → MISMAS claves que consume el front + campos extra de t408
     ========================================================== */
  public function obtenerDetalle(string $idReq): array
{
  $sql = "
    SELECT 
      d.Id_Detalle,
      d.Id_Producto,
      p.NombreProducto  AS Nombre,
      u.Descripcion     AS UnidadMedida,
      ev.CantidadAprobada,
      ev.PrecioAprobado,
      ev.MontoAsignado,
      ev.EstadoProducto
    FROM t15DetalleRequerimientoCompra d
    JOIN t18CatalogoProducto p 
      ON p.Id_Producto = d.Id_Producto
    JOIN t34UnidadMedida u 
      ON u.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
    /* === SOLO detalle de la ÚLTIMA evaluación (INNER JOIN obligatorio) === */
    JOIN t408DetalleEvaluacion ev
      ON ev.Id_DetalleRequerimiento = d.Id_Detalle
     AND ev.Id_Evaluacion = (
           SELECT e2.Id_Evaluacion
           FROM t407EvaluacionRequerimiento e2
           WHERE e2.Id_Requerimiento = d.Id_Requerimiento
             AND e2.ResultadoEvaluacion IN ('Aprobado','Parcialmente Aprobado')
           ORDER BY e2.FechaEvaluacion DESC, e2.Id_Evaluacion DESC
           LIMIT 1
         )
    WHERE d.Id_Requerimiento = ?
    ORDER BY p.NombreProducto
  ";

  $st = mysqli_prepare($this->cn, $sql);
  mysqli_stmt_bind_param($st, "s", $idReq);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);

  $rows = [];
  if ($rs) {
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = [
        'Id_Detalle'       => (int)$r['Id_Detalle'],
        'Id_Producto'      => (int)$r['Id_Producto'],
        'Nombre'           => $r['Nombre'],
        'UnidadMedida'     => $r['UnidadMedida'],
        // ← ÚNICA cantidad que expondremos al front:
        'CantidadAprobada' => (float)$r['CantidadAprobada'],
        'PrecioAprobado'   => isset($r['PrecioAprobado']) ? (float)$r['PrecioAprobado'] : null,
        'MontoAsignado'    => isset($r['MontoAsignado'])  ? (float)$r['MontoAsignado']  : null,
        'EstadoProducto'   => $r['EstadoProducto'] ?? null,
      ];
    }
    mysqli_stmt_close($st);
  }
  return $rows;
}


  /* ==========================================================
     4) Actualizar estado (igual)
     ========================================================== */
  public function actualizarEstado(string $idReq, string $nuevoEstado): void
  {
    $sql = "UPDATE t14RequerimientoCompra 
            SET Estado = ?
            WHERE Id_Requerimiento = ?";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "ss", $nuevoEstado, $idReq);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
  }

  /* ==========================================================
     5) Recalcular totales (igual)
     ========================================================== */
  public function recomputarTotales(string $idReq): void
  {
    $sql = "SELECT 
              COALESCE(SUM(d.Cantidad * d.PrecioPromedio), 0) AS total,
              COALESCE(AVG(d.PrecioPromedio), 0) AS prom
            FROM t15DetalleRequerimientoCompra d
            WHERE d.Id_Requerimiento = ?";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $r  = $rs ? mysqli_fetch_assoc($rs) : ['total' => 0, 'prom' => 0];
    mysqli_stmt_close($st);

    $total = (float)($r['total'] ?? 0);
    $prom  = (float)($r['prom'] ?? 0);

    $sqlUp = "UPDATE t14RequerimientoCompra 
              SET Total = ?, PrecioPromedio = ?
              WHERE Id_Requerimiento = ?";
    $up = mysqli_prepare($this->cn, $sqlUp);
    mysqli_stmt_bind_param($up, "dds", $total, $prom, $idReq);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);
  }
}
