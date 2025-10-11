<?php
final class Guia{
  private $cn;
  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /** Encabezado de la guía */
  public function obtenerGuiaEncabezado(int $idGuia): ?array {
    $sql = "SELECT
    g.Id_Guia             AS idGuia,
    g.Numero              AS numero,
    CONCAT(g.Serie,'-',LPAD(g.Numero,8,'0')) AS numeroStr,
    DATE(g.Fec_Emision)   AS fecha,
    g.RemitenteRUC        AS remitenteRuc,
    g.RemitenteRazonSocial AS remitenteRazon,
    g.DniReceptor         AS dniReceptor,
    g.DestinatarioNombre  AS destinatarioNombre,
    g.DireccionDestino    AS direccionDestino,
    g.DistritoDestino     AS distritoDestino,
    g.Conductor           AS conductor,
    g.Licencia            AS licencia,
    g.Marca               AS vehMarca,
    g.Placa               AS vehPlaca,
    '' AS vehModelo,
    g.Id_AsignacionRepartidorVehiculo AS idAsignacion,
    da.DireccionOrigen    AS direccionOrigen,
    da.NombreAlmacen      AS nombreAlmacen
FROM t72GuiaRemision g
LEFT JOIN t73DireccionAlmacen da 
    ON g.Id_DireccionAlmacen = da.Id_DireccionAlmacen
WHERE g.Id_Guia = ?";
    $st  = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idGuia);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_free_result($rs);
    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) {/* flush */}
    return $row;
  }

  /** Detalle de la guía */
  public function obtenerGuiaDetalle(int $idGuia): array {
    $sql = "SELECT
    gd.Id_Producto     AS idProducto,
    COALESCE(p.Id_Producto, gd.Id_Producto) AS codigo,
    COALESCE(gd.Descripcion, p.NombreProducto, '') AS descripcion,
    COALESCE(um.Descripcion, gd.Unidad, 'UND') AS unidad,
    gd.Cantidad        AS cantidad
FROM t74DetalleGuia gd
LEFT JOIN t18CatalogoProducto p ON p.Id_Producto = gd.Id_Producto
LEFT JOIN t34UnidadMedida um ON um.Id_UnidadMedida = p.t34UnidadMedida_Id_UnidadMedida
WHERE gd.Id_Guia = ?
ORDER BY gd.Id_DetalleGuia;";
    $st  = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idGuia);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);

    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = [
        'idProducto'  => (int)($r['idProducto'] ?? 0),
        'codigo'      => (string)($r['codigo'] ?? $r['idProducto'] ?? ''),
        'descripcion' => (string)($r['descripcion'] ?? ''),
        'unidad'      => (string)($r['unidad'] ?? ''),   // ← importante para tu PDF
        'cantidad'    => (float)($r['cantidad'] ?? 0),
      ];
    }
    mysqli_free_result($rs);
    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) {/* flush */}
    return $rows;
  }

  /** Paquete completo para el controlador HTML/PDF/Email */
  public function obtenerGuiaCompleta(int $idGuia): ?array {
    $enc = $this->obtenerGuiaEncabezado($idGuia);
    if (!$enc) return null;
    $det = $this->obtenerGuiaDetalle($idGuia);

    // Normaliza nombres esperados por el ControladorGuiaHTML
    $encabezado = [
      'id'                 => (int)$enc['idGuia'],
      'numero'             => $enc['numero'] ?? null,
      'numeroStr'          => $enc['numeroStr'] ?? null,
      'fecha'              => $enc['fecha'] ?? date('Y-m-d'),
      'remitenteRuc'       => $enc['remitenteRuc'] ?? '20123456789',
      'remitenteRazon'     => $enc['remitenteRazon'] ?? 'Mundo Patitas SAC',
      'dniReceptor'        => $enc['dniReceptor'] ?? '',
      'destinatarioNombre' => $enc['destinatarioNombre'] ?? '',
      'direccionDestino'   => $enc['direccionDestino'] ?? '',
      'distritoDestino'    => $enc['distritoDestino'] ?? '',
      'conductor'          => $enc['conductor'] ?? '',
      'direccionOrigen'    => $enc['direccionOrigen'] ?? '',
      'licencia'           => $enc['licencia'] ?? '',
      'vehMarca'           => $enc['vehMarca'] ?? '',
      'vehPlaca'           => $enc['vehPlaca'] ?? '',
      'vehModelo'          => $enc['vehModelo'] ?? '',
      'idAsignacion'       => isset($enc['idAsignacion']) ? (int)$enc['idAsignacion'] : null,
    ];

    return [
      'encabezado' => $encabezado,
      'detalle'    => $det,
    ];
  }

  public function crearGuiaSinNumerador(array $d): array
  {
    $sql = "CALL sp_cus24_crear_guia_sin_numerador(?,?,?,?,?,?,?,?,?,?,?,?,?,?,@o_id,@o_num,@o_numStr)";
    $st  = mysqli_prepare($this->cn, $sql);

    mysqli_stmt_bind_param(
      $st,
      "sssssssiisssss",
      $d['serie'],
      $d['remitenteRuc'],
      $d['remitenteRazon'],
      $d['destinatarioNombre'],
      $d['dniReceptor'],
      $d['direccionDestino'],
      $d['distritoDestino'],
      $d['idDireccionAlmacen'],   // int
      $d['idAsignacionRV'],       // int OBLIGATORIO
      $d['marca'],
      $d['placa'],
      $d['conductor'],
      $d['licencia'],
      $d['motivo']
    );

    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    $q   = $this->cn->query("SELECT @o_id AS idGuia, @o_num AS numero, @o_numStr AS numeroStr");
    $row = $q->fetch_assoc();

    return [
      'idGuia'    => (int)$row['idGuia'],
      'numero'    => (int)$row['numero'],
      'numeroStr' => $row['numeroStr'],
    ];
  }

  public function insertarDetalleDesdeOps(int $idGuia, array $ops): void
  {
    // asumiendo SP: sp_cus24_insertar_detalle_guia_from_ops(p_Id_Guia INT, p_ops_json JSON)
    $ops = array_values(array_unique(array_filter(array_map('intval', $ops), fn($v) => $v > 0)));
    $json = $this->cn->real_escape_string(json_encode($ops));
    $sql  = "CALL sp_cus24_insertar_detalle_guia_from_ops($idGuia, '$json')";
    $this->cn->query($sql);
  }

  public function obtenerEncabezado(int $idGuia): ?array {
  $sql = "SELECT g.*,
                 da.DireccionOrigen AS OrigenDireccion
          FROM t72GuiaRemision g
          LEFT JOIN t73DireccionAlmacen da
            ON da.Id_DireccionAlmacen = g.Id_DireccionAlmacen
          WHERE g.Id_Guia = ?";
  $st = $this->cn->prepare($sql);
  $st->bind_param("i", $idGuia);
  $st->execute();
  $res = $st->get_result()->fetch_assoc();
  $st->close();
  return $res ?: null;
}

public function obtenerDetalle(int $idGuia): array {
  $sql = "SELECT Id_Producto, Descripcion, Unidad, Cantidad
          FROM t74DetalleGuia
          WHERE Id_Guia = ?
          ORDER BY Id_DetalleGuia";
  $st = $this->cn->prepare($sql);
  $st->bind_param("i", $idGuia);
  $st->execute();
  $rs = $st->get_result();
  $out = [];
  while ($row = $rs->fetch_assoc()) $out[] = $row;
  $st->close();
  return $out;
}

}
