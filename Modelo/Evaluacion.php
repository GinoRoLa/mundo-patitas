<?php
// Ubicación: /Modelo/Evaluacion.php

final class Evaluacion
{
  private mysqli $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /**
   * Ejecuta evaluación greedy optimizada: menor precio primero, luego completa con stock.
   * ✅ ACTUALIZADO: usa CantidadAprobada de t408DetalleEvaluacion
   */
  public function evaluarRequerimientoGreedy($idReq): array
  {
    $idReq = (int)$idReq;
    if ($idReq <= 0) {
      return ['ok' => false, 'error' => 'Id de requerimiento inválido'];
    }

    // 1) Traer detalle del requerimiento APROBADO (desde última evaluación)
    $detReq = $this->fetchDetalleRequerimiento($idReq);
    if (empty($detReq)) {
      return ['ok' => false, 'error' => 'El requerimiento no tiene detalle aprobado para evaluar'];
    }

    // 2) Traer TODAS las ofertas 'Recibida'
    $ofertas = $this->fetchOfertasRecibidas($idReq);
    $offersByProd = [];
    foreach ($ofertas as $row) {
      $pid = (int)$row['Id_Producto'];
      $offersByProd[$pid][] = [
        'ruc'       => $row['RUC_Proveedor'],
        'proveedor' => $row['des_RazonSocial'] ?? null,
        'idCot'     => (int)$row['Id_Cotizacion'],
        'precio'    => (float)$row['PrecioUnitario'],
        'stock'     => (float)$row['CantidadOfertada'],
      ];
    }

    $productosOut   = [];
    $proveedoresSet = [];
    $costoGlobal    = 0.0;

    // 3) Para cada producto aprobado, armar ranking y asignación greedy
    foreach ($detReq as $p) {
      $idProd   = (int)$p['Id_Producto'];
      $cantAprobada = (float)$p['CantidadAprobada']; // ← CAMBIO: ahora es CantidadAprobada
      $nombre   = $p['Nombre'] ?? '';
      $um       = $p['UnidadMedida'] ?? 'UND';

      $ofers = $offersByProd[$idProd] ?? [];

      // Ranking: precio ASC, stock DESC, RUC ASC
      usort($ofers, function ($a, $b) {
        return ($a['precio'] <=> $b['precio'])
            ?: ($b['stock']  <=> $a['stock'])
            ?: strcmp($a['ruc'], $b['ruc']);
      });

      // Copia para exponer en UI
      $rankingPrecio = [];
      foreach ($ofers as $o) {
        $rankingPrecio[] = [
          'ruc'       => $o['ruc'],
          'proveedor' => $o['proveedor'],
          'precio'    => $o['precio'],
          'stock'     => $o['stock'],
          'idCot'     => $o['idCot'],
        ];
      }

      // Asignación greedy
      $restante   = $cantAprobada;
      $asignacion = [];
      $costoTotal = 0.0;

      foreach ($ofers as $o) {
        if ($restante <= 0) break;
        $asignar = min($o['stock'], $restante);
        if ($asignar <= 0) continue;

        $costoLinea = round($asignar * $o['precio'], 2);
        $asignacion[] = [
          'ruc'       => $o['ruc'],
          'proveedor' => $o['proveedor'],
          'idCot'     => $o['idCot'],
          'cantidad'  => $asignar,
          'precio'    => $o['precio'],
          'costo'     => $costoLinea,
        ];
        $proveedoresSet[$o['ruc']] = true;

        $costoTotal += $costoLinea;
        $restante   -= $asignar;
      }

      $node = [
        'Id_Producto'      => $idProd,
        'Nombre'           => $nombre,
        'UnidadMedida'     => $um,
        'CantidadAprobada' => $cantAprobada, // ← CAMBIO: nombre coherente con BD
        'rankingPrecio'    => $rankingPrecio,
        'asignacion'       => $asignacion,
        'costoTotal'       => round($costoTotal, 2),
        'faltante'         => max(0, round($restante, 2)),
      ];

      $costoGlobal += $node['costoTotal'];
      $productosOut[] = $node;
    }

    $resumen = [
      'productosEvaluados' => count($productosOut),
      'proveedores'        => count($proveedoresSet),
      'costoTotal'         => round($costoGlobal, 2),
    ];

    return [
      'ok'        => true,
      'productos' => $productosOut,
      'resumen'   => $resumen,
    ];
  }

  /**
   * Crea estructura de adjudicación para OrdenCompra
   */
  public function prepararAdjudicacion(array $evaluacion): array
  {
    $adjud = [];
    $productos = $evaluacion['productos'] ?? [];
    foreach ($productos as $p) {
      $idProd = (int)$p['Id_Producto'];
      foreach ($p['asignacion'] as $a) {
        $ruc = $a['ruc'];
        if (!isset($adjud[$ruc])) $adjud[$ruc] = [];
        $adjud[$ruc][] = [
          'idProducto'     => $idProd,
          'cantidad'       => (float)$a['cantidad'],
          'precioUnitario' => (float)$a['precio'],
          'costo'          => (float)$a['costo'],
          'idCot'          => (int)$a['idCot'],
        ];
      }
    }
    return $adjud;
  }

  /* ==================== Helpers privados ==================== */

  /**
   * ✅ ACTUALIZADO: Obtiene productos APROBADOS desde la última evaluación (t408)
   */
  private function fetchDetalleRequerimiento(int $idReq): array
  {
    $sql = "
      SELECT 
        d.Id_Detalle,
        d.Id_Producto,
        p.NombreProducto AS Nombre,
        u.Descripcion AS UnidadMedida,
        ev.CantidadAprobada
      FROM t15DetalleRequerimientoCompra d
      JOIN t18CatalogoProducto p 
        ON p.Id_Producto = d.Id_Producto
      LEFT JOIN t34UnidadMedida u 
        ON u.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
      /* === JOIN con la ÚLTIMA evaluación === */
      INNER JOIN t408DetalleEvaluacion ev
        ON ev.Id_DetalleRequerimiento = d.Id_Detalle
       AND ev.Id_Evaluacion = (
             SELECT e2.Id_Evaluacion
             FROM t407EvaluacionRequerimiento e2
             WHERE e2.Id_Requerimiento = ?
               AND e2.ResultadoEvaluacion IN ('Aprobado','Parcialmente Aprobado')
             ORDER BY e2.FechaEvaluacion DESC, e2.Id_Evaluacion DESC
             LIMIT 1
           )
      WHERE d.Id_Requerimiento = ?
      ORDER BY p.NombreProducto
    ";

    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) {
      error_log("[Evaluacion] Error preparando fetchDetalleRequerimiento: " . mysqli_error($this->cn));
      return [];
    }

    mysqli_stmt_bind_param($st, "ii", $idReq, $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $out[] = [
        'Id_Detalle'       => (int)$r['Id_Detalle'],
        'Id_Producto'      => (int)$r['Id_Producto'],
        'Nombre'           => $r['Nombre'],
        'UnidadMedida'     => $r['UnidadMedida'] ?? 'UND',
        'CantidadAprobada' => (float)$r['CantidadAprobada'],
      ];
    }

    mysqli_stmt_close($st);
    return $out;
  }

  /**
   * Devuelve TODAS las ofertas en t86/t87 con estado 'Recibida'
   */
  private function fetchOfertasRecibidas(int $idReq): array
  {
    $sql = "SELECT 
              c.Id_Cotizacion,
              c.RUC_Proveedor,
              pr.des_RazonSocial,
              d.Id_Producto,
              CAST(d.CantidadOfertada AS DECIMAL(12,2)) AS CantidadOfertada,
              CAST(d.PrecioUnitario   AS DECIMAL(12,4)) AS PrecioUnitario
            FROM t86Cotizacion c
            JOIN t87DetalleCotizacion d ON d.Id_Cotizacion = c.Id_Cotizacion
            LEFT JOIN t17CatalogoProveedor pr ON pr.Id_NumRuc = c.RUC_Proveedor
            WHERE c.Id_Requerimiento = ?
              AND c.Estado = 'Recibida'";

    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) {
      error_log("[Evaluacion] Error preparando fetchOfertasRecibidas: " . mysqli_error($this->cn));
      return [];
    }

    mysqli_stmt_bind_param($st, "i", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $out[] = $r;
    }

    mysqli_stmt_close($st);
    return $out;
  }
}