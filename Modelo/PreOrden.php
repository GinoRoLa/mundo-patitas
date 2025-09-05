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
  public function vigentesPorCliente(string $dni): array {
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
    while (mysqli_more_results($this->cn)) { mysqli_next_result($this->cn); }
    return $out;
}

public function filtrarVigentesDelCliente(string $dni, array $ids): array {
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
    while (mysqli_more_results($this->cn)) { mysqli_next_result($this->cn); }
    return $out;
}

public function consolidarProductos(array $ids): array {
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
    while (mysqli_more_results($this->cn)) { mysqli_next_result($this->cn); }
    return $out;
}


  // Marcar como procesadas y vincular a la orden
  public function procesarYVincular(array $idsPreorden, int $ordenId): int{
    if (empty($idsPreorden)) return 0;
    $idsPreorden = array_values(array_unique(array_map('intval', $idsPreorden)));

    $in = implode(',', array_fill(0, count($idsPreorden), '?'));
    $types = str_repeat('i', count($idsPreorden) + 1); // ordenId + ids

    $sql = "UPDATE t01PreOrdenPedido
                   SET Estado='Procesado',
                       t02OrdenPedido_Id_OrdenPedido = ?
                 WHERE Id_PreOrdenPedido IN ($in)
                   AND Estado='Emitido'";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));

    $params = array_merge([$ordenId], $idsPreorden);
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $aff = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);
    return $aff;
  }
}
