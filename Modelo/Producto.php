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
    $rows[] = [
      'op'            => (int)$r['idOP'],
      'idDet'         => (int)$r['idDet'],
      'idProd'        => (int)$r['idProducto'],
      'codigo'        => (string)$r['idProducto'],
      'descripcion'   => trim(($r['nombreProducto'] ?? '').' - '.($r['descripcion'] ?? '')),
      'marca'         => $r['marca'] ?? '',
      'precio'        => (float)$r['precio'],
      'cantidad'      => (int)$r['cantidad'],

      // 👇 nuevos
      'receptorDni'   => $r['receptorDni'] ?? null,
      'receptorNombre'=> $r['receptorNombre'] ?? null,
      'direccionSnap' => $r['direccionSnap'] ?? null,
      'idDistrito'    => isset($r['idDistrito']) ? (int)$r['idDistrito'] : null,
    ];
  }
  mysqli_stmt_close($st);
  while(mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* noop */ }

  return $rows;
}

}
