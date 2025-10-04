<?php
final class DireccionEnvioCliente {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  /* ==================== HELPERS ==================== */

  /** Devuelve Id_Distrito por nombre (match por UPPER(TRIM(DescNombre))) o null si no existe/está inactivo. */
  private function buscarIdDistritoPorNombre(?string $nombre): ?int {
    $nombre = trim((string)$nombre);
    if ($nombre === '') return null;

    $sql = "SELECT Id_Distrito
              FROM t77DistritoEnvio
             WHERE UPPER(TRIM(DescNombre)) = UPPER(TRIM(?))
               AND UPPER(TRIM(Estado)) IN ('ACTIVO','ACTIVE')
             LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));
    mysqli_stmt_bind_param($st, "s", $nombre);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_stmt_close($st);
    return $row ? (int)$row['Id_Distrito'] : null;
  }

  /** Inserta snapshot en t71 (sin DistritoSnap: solo guarda Id_Distrito FK si se pudo resolver). */
  private function insertarSnapshotConOrden(
    int $ordenId,
    string $nombreSnap,
    string $telSnap,
    string $dirSnap,
    string $dniRecepcionSnap,
    ?int $idDistritoSnap
  ): int {
    $sql = "INSERT INTO t71OrdenDirecEnvio
              (Id_OrdenPedido, NombreContactoSnap, TelefonoSnap, DireccionSnap, ReceptorDniSnap, Id_Distrito)
            VALUES (?,?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }

    // tipos: i s s s s i  (MySQL aceptará NULL en el último si la columna permite NULL)
    mysqli_stmt_bind_param(
      $st, "issssi",
      $ordenId, $nombreSnap, $telSnap, $dirSnap, $dniRecepcionSnap, $idDistritoSnap
    );
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /** (opcional) Enlace snapshot ↔ catálogo */
  private function vincularCatalogoASnapshot(int $ordenDirecEnvioId, int $idDireccionEnvio): void {
    $sql = "INSERT INTO t92Ref_Snapshot_DirCatalogo (Id_OrdenDirecEnvio, Id_DireccionEnvio) VALUES (?,?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "ii", $ordenDirecEnvioId, $idDireccionEnvio);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
  }


  public function listarPorClienteId(int $idCliente): array {
    $sql = "SELECT
              Id_DireccionEnvio,
              Id_Cliente,
              NombreContacto,
              TelefonoContacto,
              DniReceptor AS ReceptorDni,
              Direccion,
              Distrito AS DistritoNombre
            FROM t70DireccionEnvioCliente
            WHERE Id_Cliente = ?
            ORDER BY Id_DireccionEnvio DESC";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "i", $idCliente);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    mysqli_stmt_close($st);
    return $out;
  }

  public function obtenerDeCliente(int $idCliente, int $idDireccion): ?array {
    $sql = "SELECT
              Id_DireccionEnvio,
              NombreContacto,
              TelefonoContacto,
              DniReceptor AS ReceptorDni,
              Direccion,
              Distrito AS DistritoNombre
            FROM t70DireccionEnvioCliente
            WHERE Id_Cliente = ? AND Id_DireccionEnvio = ?
            LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "ii", $idCliente, $idDireccion);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_stmt_close($st);
    return $row;
  }

  public function insertar(
    int $idCliente, string $nombre, string $tel, string $dniReceptor, string $dir, string $distrito
  ): int {
    $sql = "INSERT INTO t70DireccionEnvioCliente
              (Id_Cliente, NombreContacto, TelefonoContacto, DniReceptor, Direccion, Distrito)
            VALUES (?,?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "isssss", $idCliente, $nombre, $tel, $dniReceptor, $dir, $distrito);
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /* ==================== SNAPSHOTS t71 ==================== */

  public function crearSnapshotDesdeGuardada(
    int $ordenId, int $idCliente, int $idDireccionEnvio, ?string $receptorDniSnap = null
  ): int {
    $row = $this->obtenerDeCliente($idCliente, $idDireccionEnvio);
    if (!$row) throw new InvalidArgumentException('Dirección guardada inválida para este cliente.');

    $dni = $receptorDniSnap ?: ($row['ReceptorDni'] ?? '');
    if (!preg_match('/^\d{8}$/', $dni)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    // Resolver Id_Distrito a partir del nombre almacenado en t70
    $idDistrito = $this->buscarIdDistritoPorNombre($row['DistritoNombre'] ?? null);

    return $this->insertarSnapshotConOrden(
      $ordenId,
      $row['NombreContacto']   ?? '',
      $row['TelefonoContacto'] ?? '',
      $row['Direccion']        ?? '',
      $dni,
      $idDistrito
    );
  }

  public function crearSnapshotDesdeOtra(
    int $ordenId, int $idCliente,
    string $nombre, string $tel, string $dir, string $distrito, string $receptorDniSnap,
    bool $guardarEnCatalogo = false
  ): int {
    $nombre=trim($nombre); $tel=trim($tel); $dir=trim($dir); $distrito=trim($distrito);
    if ($nombre==='' || $tel==='' || $dir==='' || $distrito==='') {
      throw new InvalidArgumentException('Faltan datos de envío (nombre, teléfono, dirección, distrito).');
    }
    if (!preg_match('/^\d{8}$/',$receptorDniSnap)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    // Resolver Id_Distrito a partir del nombre ingresado por el usuario
    $idDistrito = $this->buscarIdDistritoPorNombre($distrito);

    $snapId = $this->insertarSnapshotConOrden(
      $ordenId, $nombre, $tel, $dir, $receptorDniSnap, $idDistrito
    );

    if ($guardarEnCatalogo) {
      $this->insertar($idCliente, $nombre, $tel, $receptorDniSnap, $dir, $distrito);
      // Si quieres, enlaza snapshot↔catálogo:
      // $this->vincularCatalogoASnapshot($snapId, $idDir);
    }
    return $snapId;
  }
}
