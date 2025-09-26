<?php
final class DireccionEnvioCliente {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  // Trae todo y alias DniReceptor como ReceptorDni (para el front)
  public function listarPorClienteId(int $idCliente): array {
    $sql = "SELECT
              Id_DireccionEnvio,
              Id_Cliente,
              NombreContacto,
              TelefonoContacto,
              DniReceptor,
              Direccion,
              Distrito
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

  // También alias para consistencia con el front
  public function obtenerDeCliente(int $idCliente, int $idDireccion): ?array {
    $sql = "SELECT
              Id_DireccionEnvio,
              NombreContacto,
              TelefonoContacto,
              DniReceptor,
              Direccion,
              Distrito
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

  // Inserta TODOS los campos incluidos DniReceptor
  public function insertar(
    int $idCliente,
    string $nombre,
    string $tel,
    string $dniReceptor,
    string $dir,
    string $distrito
  ): int {
    $sql = "INSERT INTO t70DireccionEnvioCliente
              (Id_Cliente, NombreContacto, TelefonoContacto, DniReceptor, Direccion, Distrito)
            VALUES (?,?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    // i + 5 strings
    mysqli_stmt_bind_param($st, "isssss", $idCliente, $nombre, $tel, $dniReceptor, $dir, $distrito);
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /** Inserta snapshot COMPLETO en t71 (con DistritoSnap y ReceptorDniSnap) */
  private function insertarSnapshotConOrden(
    int $ordenId,
    string $nombreSnap,
    string $telSnap,
    string $dirSnap,
    string $distritoSnap,
    string $dniRecepcionSnap
  ): int {
    $sql = "INSERT INTO t71OrdenDirecEnvio
              (Id_OrdenPedido, NombreContactoSnap, TelefonoSnap, DireccionSnap, DistritoSnap, ReceptorDniSnap)
            VALUES (?,?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "isssss", $ordenId, $nombreSnap, $telSnap, $dirSnap, $distritoSnap, $dniRecepcionSnap);
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

  /**
   * GUARDADA: valida pertenencia, crea snapshot (usa distrito y DNI del catálogo por defecto)
   * Si quieres forzar que el usuario ingrese otro DNI, descomenta la validación requerida.
   */
  public function crearSnapshotDesdeGuardada(
    int $ordenId,
    int $idCliente,
    int $idDireccionEnvio,
    ?string $receptorDniSnap = null   // ← opcional: si viene vacío, usa el de t70
  ): int {
    $row = $this->obtenerDeCliente($idCliente, $idDireccionEnvio);
    if (!$row) throw new InvalidArgumentException('Dirección guardada inválida para este cliente.');

    $dni = $receptorDniSnap ?: ($row['DniReceptor'] ?? '');
    if (!preg_match('/^\d{8}$/', $dni)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    $snapId = $this->insertarSnapshotConOrden(
      $ordenId,
      $row['NombreContacto'],
      $row['TelefonoContacto'],
      $row['Direccion'],
      $row['Distrito'],
      $dni
    );
    // (si mantienes t92) $this->vincularCatalogoASnapshot($snapId, $idDireccionEnvio);
    return $snapId;
  }

  /**
   * OTRA: opcional guardar en t70 (incluye DNI) y (opcionalmente) vincular.
   */
  public function crearSnapshotDesdeOtra(
    int $ordenId,
    int $idCliente,
    string $nombre,
    string $tel,
    string $dir,
    string $distrito,
    string $receptorDniSnap,
    bool $guardarEnCatalogo = false
  ): int {
    $nombre=trim($nombre); $tel=trim($tel); $dir=trim($dir); $distrito=trim($distrito);
    if ($nombre==='' || $tel==='' || $dir==='' || $distrito==='') {
      throw new InvalidArgumentException('Faltan datos de envío (nombre, teléfono, dirección, distrito).');
    }
    if (!preg_match('/^\d{8}$/',$receptorDniSnap)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    $snapId = $this->insertarSnapshotConOrden($ordenId, $nombre, $tel, $dir, $distrito, $receptorDniSnap);

    if ($guardarEnCatalogo) {
      // Guarda también el DNI del receptor en t70
      $this->insertar($idCliente, $nombre, $tel, $receptorDniSnap, $dir, $distrito);
      // (si mantienes t92) $this->vincularCatalogoASnapshot($snapId, $idDir);
    }
    return $snapId;
  }
}
