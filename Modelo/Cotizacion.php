<?php
final class Cotizacion
{
  private $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /* ==========================================================
     1) Listar cotizaciones por requerimiento
     ========================================================== */
  public function listarPorRequerimiento(string $idReq, string $estado = 'Recibida'): array
  {
    $sql = "SELECT 
              c.Id_Cotizacion,
              c.Id_ReqEvaluacion,
              c.RUC_Proveedor,
              c.FechaEmision,
              c.FechaRecepcion,
              c.Observaciones,
              c.SubTotal,
              c.IGV,
              c.Total,
              c.Estado,
              p.des_RazonSocial AS RazonSocial,
              p.DireccionProv  AS Direccion
            FROM t86Cotizacion c
            JOIN t17CatalogoProveedor p ON p.Id_NumRuc = c.RUC_Proveedor
            WHERE c.Id_ReqEvaluacion = ? AND c.Estado = ?
            ORDER BY c.FechaRecepcion ASC, c.Id_Cotizacion ASC";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "ss", $idReq, $estado);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = [
        'Id_Cotizacion'   => (int)$r['Id_Cotizacion'],
        'Id_ReqEvaluacion'=> $r['Id_ReqEvaluacion'],
        'RUC_Proveedor'   => $r['RUC_Proveedor'],
        'RazonSocial'     => $r['RazonSocial'],
        'Direccion'       => $r['Direccion'],
        'FechaEmision'    => $r['FechaEmision'],
        'FechaRecepcion'  => $r['FechaRecepcion'],
        'Observaciones'   => $r['Observaciones'],
        'SubTotal'        => (float)$r['SubTotal'],
        'IGV'             => (float)$r['IGV'],
        'Total'           => (float)$r['Total'],
        'Estado'          => $r['Estado']
      ];
    }
    mysqli_stmt_close($st);
    return $rows;
  }

  /* ==========================================================
     2) Obtener cabecera de una cotización
     ========================================================== */
  public function obtenerCabecera(int $idCot): ?array
  {
    $sql = "SELECT 
              c.*,
              p.des_RazonSocial AS RazonSocial,
              p.DireccionProv  AS Direccion
            FROM t86Cotizacion c
            JOIN t17CatalogoProveedor p ON p.Id_NumRuc = c.RUC_Proveedor
            WHERE c.Id_Cotizacion = ?";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idCot);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
    return $row ?: null;
  }

  /* ==========================================================
     3) Listar detalle de cotización
     ========================================================== */
  public function listarDetalle(int $idCot): array
  {
    $sql = "SELECT 
              d.Id_DetalleCot,
              d.Id_Cotizacion,
              d.Id_Producto,
              d.CantidadOfertada,
              d.PrecioUnitario,
              d.TotalLinea,
              p.NombreProducto AS NombreProducto,
              u.Descripcion    AS UnidadMedida
            FROM t87DetalleCotizacion d
            JOIN t18CatalogoProducto p ON p.Id_Producto = d.Id_Producto
            JOIN t34UnidadMedida u ON u.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
            WHERE d.Id_Cotizacion = ?
            ORDER BY p.NombreProducto";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idCot);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = [
        'Id_DetalleCot'    => (int)$r['Id_DetalleCot'],
        'Id_Producto'      => (int)$r['Id_Producto'],
        'NombreProducto'   => $r['NombreProducto'],
        'UnidadMedida'     => $r['UnidadMedida'],
        'CantidadOfertada' => (float)$r['CantidadOfertada'],
        'PrecioUnitario'   => (float)$r['PrecioUnitario'],
        'TotalLinea'       => (float)$r['TotalLinea']
      ];
    }
    mysqli_stmt_close($st);
    return $rows;
  }

  /* ==========================================================
     4) Mapa de ofertas por producto (para comparador)
     ========================================================== */
  public function mapaOfertasPorProducto(string $idReq): array
  {
    $sql = "SELECT 
              c.Id_Cotizacion,
              c.RUC_Proveedor,
              d.Id_Producto,
              d.CantidadOfertada AS stock,
              d.PrecioUnitario   AS precio
            FROM t86Cotizacion c
            JOIN t87DetalleCotizacion d ON d.Id_Cotizacion = c.Id_Cotizacion
            WHERE c.Id_ReqEvaluacion = ? 
              AND c.Estado = 'Recibida'";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $map = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $pid = (int)$r['Id_Producto'];
      if (!isset($map[$pid])) $map[$pid] = [];
      $map[$pid][] = [
        'idCot'  => (int)$r['Id_Cotizacion'],
        'ruc'    => $r['RUC_Proveedor'],
        'precio' => (float)$r['precio'],
        'stock'  => (float)$r['stock']
      ];
    }
    mysqli_stmt_close($st);
    return $map;
  }

  /* ==========================================================
     5) Recalcular totales de cotización
     ========================================================== */
  public function recomputarTotales(int $idCot, float $porIGV = 18.0): void
  {
    $sqlSub = "SELECT COALESCE(SUM(TotalLinea), 0) AS sub
               FROM t87DetalleCotizacion 
               WHERE Id_Cotizacion = ?";
    $st = mysqli_prepare($this->cn, $sqlSub);
    mysqli_stmt_bind_param($st, "i", $idCot);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $sub = (float)mysqli_fetch_assoc($rs)['sub'];
    mysqli_stmt_close($st);

    $igv = round($sub * ($porIGV / 100), 2);
    $tot = round($sub + $igv, 2);

    $sqlUp = "UPDATE t86Cotizacion 
              SET SubTotal = ?, IGV = ?, Total = ?
              WHERE Id_Cotizacion = ?";
    $up = mysqli_prepare($this->cn, $sqlUp);
    mysqli_stmt_bind_param($up, "dddi", $sub, $igv, $tot, $idCot);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);
  }
}
