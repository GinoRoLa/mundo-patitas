<?php
// /Modelo/CUS24/Asignacion.php
final class Asignacion {
  private $cn;

  public function __construct() {
    $this->cn = (new Conexion())->conecta();
  }

  /* ===================== Encabezado / Pedidos (SP existentes) ===================== */
  public function obtenerEncabezado(int $idOrdenAsignacion): ?array {
    $sql = "CALL sp_cus24_get_asignacion_encabezado(?)";
    $st  = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idOrdenAsignacion);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_free_result($rs);
    mysqli_stmt_close($st);
    // limpiar múltiples resultsets del CALL
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* drain */ }
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
    // limpiar múltiples resultsets del CALL
    while (mysqli_more_results($this->cn) && mysqli_next_result($this->cn)) { /* drain */ }
    return $rows;
  }

  /* ===================== NUEVO: apoyo para recomputar estado t40 ===================== */

  /**
   * Cuenta cuántas OP siguen en estado 'Pagado' dentro de la asignación (t40).
   * Sirve para decidir si la t40 queda 'Parcial' o 'Despachada'.
   */
  public function contarPedidosPendientes(int $idOrdenAsignacion): int {
    $sql = "
      SELECT COUNT(*) AS cnt
      FROM t401DetalleAsignacionReparto d401
      JOIN t59OrdenServicioEntrega t59
           ON t59.Id_OSE = d401.Id_OSE
      JOIN t02OrdenPedido t02
           ON t02.Id_OrdenPedido = t59.Id_OrdenPedido
      WHERE d401.Id_OrdenAsignacion = ?
        AND t02.Estado = 'Pagado'
    ";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) return 0;

    mysqli_stmt_bind_param($st, "i", $idOrdenAsignacion);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_free_result($rs);
    mysqli_stmt_close($st);

    return (int)($row['cnt'] ?? 0);
  }

  /**
   * Actualiza el estado de la t40.
   * Valores permitidos: 'Pendiente', 'Parcial', 'Despachada'.
   */
  public function actualizarEstado(int $idOrdenAsignacion, string $estado): bool {
    // Normaliza y valida estado
    $estado = trim($estado);
    $permitidos = ['Pendiente','Parcial','Despachada'];
    if (!in_array($estado, $permitidos, true)) return false;

    $sql = "UPDATE t40OrdenAsignacionReparto SET Estado = ? WHERE Id_OrdenAsignacion = ?";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) return false;

    mysqli_stmt_bind_param($st, "si", $estado, $idOrdenAsignacion);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    return (bool)$ok;
  }
}
