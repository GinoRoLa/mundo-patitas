<?php
final class DireccionEnvioCliente {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

public function listarPorClienteId(int $idCliente): array {
    $sql = "SELECT Id_DireccionEnvio, Id_Cliente, NombreContacto, TelefonoContacto, Direccion
              FROM t70DireccionEnvioCliente
             WHERE Id_Cliente = ?
             ORDER BY Id_DireccionEnvio DESC";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idCliente);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    mysqli_stmt_close($st);
    return $out;
}


  public function obtenerDeCliente(int $idCliente, int $idDireccion): ?array {
    $sql = "SELECT Id_DireccionEnvio, NombreContacto, TelefonoContacto, Direccion
              FROM t70DireccionEnvioCliente
             WHERE Id_Cliente = ? AND Id_DireccionEnvio = ?
             LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "ii", $idCliente, $idDireccion);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_stmt_close($st);
    return $row;
  }

  public function insertar(int $idCliente, string $nombre, string $tel, string $dir): int {
    $sql = "INSERT INTO t70DireccionEnvioCliente (Id_Cliente, NombreContacto, TelefonoContacto, Direccion)
            VALUES (?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "isss", $idCliente, $nombre, $tel, $dir);
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  private function insertarSnapshotConOrden(
    int $ordenId,
    string $nombreSnap,
    string $telSnap,
    string $dirSnap
  ): int {
    $sql = "INSERT INTO t71OrdenDirecEnvio
              (Id_OrdenPedido, NombreContactoSnap, TelefonoSnap, DireccionSnap)
            VALUES (?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "isss", $ordenId, $nombreSnap, $telSnap, $dirSnap);
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /** Vincular snapshot (t71) con catálogo (t70) en t92 (opcional) */
  private function vincularCatalogoASnapshot(int $ordenDirecEnvioId, int $idDireccionEnvio): void {
    $sql = "INSERT INTO t92Ref_Snapshot_DirCatalogo (Id_OrdenDirecEnvio, Id_DireccionEnvio) VALUES (?,?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "ii", $ordenDirecEnvioId, $idDireccionEnvio);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
  }

  /** GUARDADA: valida pertenencia, crea snapshot, y vincula en t92 */
  public function crearSnapshotDesdeGuardada(int $ordenId, int $idCliente, int $idDireccionEnvio): int {
    $row = $this->obtenerDeCliente($idCliente, $idDireccionEnvio);
    if (!$row) throw new InvalidArgumentException('Dirección guardada inválida para este cliente.');

    $snapId = $this->insertarSnapshotConOrden(
      $ordenId,
      $row['NombreContacto'],
      $row['TelefonoContacto'],
      $row['Direccion']
    );
    $this->vincularCatalogoASnapshot($snapId, $idDireccionEnvio); // 👈 vínculo opcional
    return $snapId;
  }

  /** OTRA: opcional guardar en t70 y vincular; siempre snapshot completo */
  public function crearSnapshotDesdeOtra(
    int $ordenId,
    int $idCliente,
    string $nombre,
    string $tel,
    string $dir,
    bool $guardarEnCatalogo = false
  ): int {
    $nombre = trim($nombre); $tel = trim($tel); $dir = trim($dir);
    if ($nombre === '' || $tel === '' || $dir === '') {
      throw new InvalidArgumentException('Faltan datos de envío (nombre, teléfono, dirección).');
    }

    $snapId = $this->insertarSnapshotConOrden($ordenId, $nombre, $tel, $dir);

    if ($guardarEnCatalogo) {
      $idDir = $this->insertar($idCliente, $nombre, $tel, $dir);  // t70
      $this->vincularCatalogoASnapshot($snapId, $idDir);          // t92
    }

    return $snapId;
  }
}
