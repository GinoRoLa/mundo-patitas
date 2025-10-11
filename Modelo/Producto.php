<?php
// /Modelo/Producto.php
//include_once '../Controlador/Conexion.php';

final class Producto {
    private $cn;
    public function __construct(){ $this->cn = (new Conexion())->conecta(); }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM t18CatalogoProducto WHERE Id_Producto = ? LIMIT 1";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "i", $id);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs) ?: null;
        mysqli_stmt_close($st);
        return $row;
    }

    public function itemsPorOrden(int $idOP): array {
  $rows = [];

  $st = mysqli_prepare($this->cn, "CALL sp_cus24_get_items_por_orden(?)");
  mysqli_stmt_bind_param($st, "i", $idOP);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);

  while ($r = mysqli_fetch_assoc($rs)) {
    // Normaliza textos y valores
    $nombre = trim((string)($r['nombreProducto'] ?? ''));
    $desc   = trim((string)($r['descripcion'] ?? ''));
    $concat = $nombre && $desc ? "$nombre - $desc" : ($nombre ?: $desc);

    $rows[] = [
      'op'            => (int)($r['idOP'] ?? 0),
      'idDet'         => (int)($r['idDet'] ?? 0),
      'idProd'        => (int)($r['idProducto'] ?? 0),
      'codigo'        => (string)($r['idProducto'] ?? ''),
      'descripcion'   => $concat,
      'marca'         => (string)($r['marca'] ?? ''),
      'precio'        => isset($r['precio']) ? (float)$r['precio'] : 0.0,
      'cantidad'      => isset($r['cantidad']) ? (float)$r['cantidad'] : 0,
      'unidad'        => (string)($r['unidad'] ?? ''),
      'receptorDni'   => $r['receptorDni']    ?? null,
      'receptorNombre'=> $r['receptorNombre'] ?? null,
      'direccionSnap' => $r['direccionSnap']  ?? null,
      'idDistrito'    => isset($r['idDistrito']) ? (int)$r['idDistrito'] : null,
    ];
  }

  // Limpieza de recursos
  mysqli_free_result($rs);
  mysqli_stmt_close($st);
  while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* drain */ }

  return $rows;
}


}
