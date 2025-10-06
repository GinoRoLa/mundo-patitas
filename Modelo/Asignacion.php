<?php
// /Modelo/CUS24/Asignacion.php
final class Asignacion{
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  public function obtenerEncabezado(int $idOrdenAsignacion): ?array {
    $sql = "CALL sp_cus24_get_asignacion_encabezado(?)";
    $st  = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idOrdenAsignacion);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_free_result($rs);
    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* limpiar */ }
    return $row;
  }

  public function obtenerPedidos(int $idOrdenAsignacion): array {
    $sql = "CALL sp_cus24_get_asignacion_pedidos(?)";
    $st  = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idOrdenAsignacion);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = $r;
    }
    mysqli_free_result($rs);
    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* limpiar */ }
    return $rows;
  }
}
