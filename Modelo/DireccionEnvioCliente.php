<?php
final class DireccionEnvioCliente {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  public function listarPorClienteId(int $idCliente): array {
    $sql = "SELECT Id_DireccionEnvio, NombreContacto, TelefonoContacto, Direccion
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

  /** Inserta snapshot por orden en t71OrdenDirecEnvio. Retorna Id_OrdenDirecEnvio. */
  public function insertarSnapshotOrden(int $ordenId,?int $idDireccionEnvio,string $nombreSnap,string $telSnap,string $dirSnap
  ): int {
    $sql = "INSERT INTO t71OrdenDirecEnvio
              (Id_OrdenPedido, Id_DireccionEnvio, NombreContactoSnap, TelefonoSnap, DireccionSnap)
            VALUES (?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    // Nota: pasar NULL en bind_param es válido; MySQL guardará NULL
    mysqli_stmt_bind_param($st, "iisss", $ordenId, $idDireccionEnvio, $nombreSnap, $telSnap, $dirSnap);
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /** Toma una dirección GUARDADA del cliente, valida pertenencia y crea snapshot. */
  public function crearSnapshotDesdeGuardada(int $ordenId, int $idCliente, int $idDireccionEnvio): int {
    $row = $this->obtenerDeCliente($idCliente, $idDireccionEnvio);
    if (!$row) {
      throw new InvalidArgumentException('Dirección guardada inválida para este cliente.');
    }
    return $this->insertarSnapshotOrden(
      $ordenId,
      $idDireccionEnvio,
      $row['NombreContacto'],
      $row['TelefonoContacto'],
      $row['Direccion']
    );
  }

  /**
   * Usa una "otra dirección" (no guardada). Valida campos; si $guardarEnCatalogo=true,
   * inserta en t70 y usa ese id. Siempre crea el snapshot en t71.
   */
  public function crearSnapshotDesdeOtra(
    int $ordenId,
    int $idCliente,
    string $nombre,
    string $tel,
    string $dir,
    bool $guardarEnCatalogo = false
  ): int {
    $nombre = trim($nombre);
    $tel    = trim($tel);
    $dir    = trim($dir);
    if ($nombre === '' || $tel === '' || $dir === '') {
      throw new InvalidArgumentException('Faltan datos de envío (nombre, teléfono, dirección).');
    }

    $idDireccionEnvio = null;
    if ($guardarEnCatalogo) {
      $idDireccionEnvio = $this->insertar($idCliente, $nombre, $tel, $dir);
    }

    return $this->insertarSnapshotOrden($ordenId, $idDireccionEnvio, $nombre, $tel, $dir);
  }
}
