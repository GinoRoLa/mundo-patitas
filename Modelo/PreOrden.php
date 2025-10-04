<?php
// /Modelo/PreOrden.php
//include_once '../Controlador/Conexion.php';

final class PreOrden
{
  private $cn;
  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  // Preórdenes (Emitido, <24h) con total calculado
  public function vigentesPorCliente(string $dni): array
  {
    $sql = "CALL sp_preorden_vigentes_por_dni(?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));
    mysqli_stmt_bind_param($st, "s", $dni);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;

    mysqli_stmt_close($st);
    // Muy importante: consumir resultsets sobrantes de CALL
    while (mysqli_more_results($this->cn)) {
      mysqli_next_result($this->cn);
    }
    return $out;
  }

  public function filtrarVigentesDelCliente(string $dni, array $ids): array
  {
    if (empty($ids)) return [];
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $idsJson = json_encode($ids);

    $sql = "CALL sp_preorden_filtrar_vigentes_del_cliente(?, ?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));
    mysqli_stmt_bind_param($st, "ss", $dni, $idsJson);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $out = [];
    while ($r = mysqli_fetch_row($rs)) $out[] = (int)$r[0];

    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn)) {
      mysqli_next_result($this->cn);
    }
    return $out;
  }

  public function consolidarProductos(array $ids): array
  {
    if (empty($ids)) return [];
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $idsJson = json_encode($ids);

    $sql = "CALL sp_preorden_consolidar_productos(?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));
    mysqli_stmt_bind_param($st, "s", $idsJson);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;

    mysqli_stmt_close($st);
    while (mysqli_more_results($this->cn)) {
      mysqli_next_result($this->cn);
    }
    return $out;
  }

public function procesarYVincular(array $ids, int $ordenId): array {
  // normaliza a ints
  $ids = array_values(array_unique(array_map(function($v){
    if (is_array($v)) {
      if (isset($v['Id_PreOrdenPedido'])) return (int)$v['Id_PreOrdenPedido'];
      if (isset($v['id'])) return (int)$v['id'];
      return 0;
    }
    return (int)$v;
  }, $ids)));
  $ids = array_filter($ids, fn($x)=>$x>0);
  if (!$ids) { return ['vinculadas'=>0, 'marcadas'=>0]; }

  $json = json_encode($ids, JSON_UNESCAPED_UNICODE);

  $sql = "CALL sp_vincular_preordenes_a_orden(?, ?)";
  $st  = mysqli_prepare($this->cn, $sql);
  if (!$st) throw new RuntimeException('Prepare failed: ' . mysqli_error($this->cn));
  mysqli_stmt_bind_param($st, "is", $ordenId, $json);
  mysqli_stmt_execute($st);

  $out = ['vinculadas'=>0, 'marcadas'=>0];
  if ($rs = mysqli_stmt_get_result($st)) {
    $row = mysqli_fetch_assoc($rs) ?: [];
    $out['vinculadas'] = (int)($row['preordenes_vinculadas'] ?? 0);
    $out['marcadas']   = (int)($row['preordenes_marcadas'] ?? 0);
    mysqli_free_result($rs);
  }
  mysqli_stmt_close($st);

  // consume posibles resultsets extra del CALL
  while (mysqli_more_results($this->cn)) {
    mysqli_next_result($this->cn);
    if ($tmp = mysqli_store_result($this->cn)) mysqli_free_result($tmp);
  }

  return $out;
}



}
