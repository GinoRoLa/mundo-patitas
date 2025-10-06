<?php
final class DireccionEnvioCliente {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  /* ==================== HELPERS ==================== */

  /** Devuelve Id_Distrito por nombre exacto (case/trim-insensitive). NULL si no existe/está inactivo. */
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

  /** Inserta snapshot en t71; guarda Id_Distrito (puede ser NULL si no se pudo resolver). */
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

    // tipos: i s s s s i  (el último puede ser NULL)
    mysqli_stmt_bind_param($st, "issssi",
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

  /* ==================== CRUD t70 (catálogo) ==================== */

  /** Lista direcciones del cliente; incluye Id_Distrito y nombre via JOIN para el front. */
  public function listarPorClienteId(int $idCliente): array {
    $sql = "SELECT
            t70.Id_DireccionEnvio,
            t70.Id_Cliente,
            t70.NombreContacto,
            t70.TelefonoContacto,
            t70.DniReceptor AS ReceptorDni,
            t70.Direccion,
            t70.Id_Distrito,
            t77.DescNombre AS DistritoNombre
          FROM t70DireccionEnvioCliente t70
          INNER JOIN t77DistritoEnvio t77 ON t70.Id_Distrito = t77.Id_Distrito
          WHERE t70.Id_Cliente = ?
          ORDER BY t70.Id_DireccionEnvio DESC";
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

  /** Obtiene UNA dirección del cliente; incluye Id_Distrito y nombre. */
  public function obtenerDeCliente(int $idCliente, int $idDireccion): ?array {
    $sql = "SELECT
            t70.Id_DireccionEnvio,
            t70.NombreContacto,
            t70.TelefonoContacto,
            t70.DniReceptor AS ReceptorDni,
            t70.Direccion,
            t70.Id_Distrito,                 -- ← AÑADIR
            t77.DescNombre AS DistritoNombre
          FROM t70DireccionEnvioCliente t70
          INNER JOIN t77DistritoEnvio t77 ON t70.Id_Distrito = t77.Id_Distrito
          WHERE t70.Id_Cliente = ? AND t70.Id_DireccionEnvio = ?
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

  /** Inserta en t70 usando Id_Distrito (FK). */
  public function insertar(
    int $idCliente,
    string $nombre,
    string $tel,
    string $dniReceptor,
    string $dir,
    int $idDistrito
  ): int {
    $sql = "INSERT INTO t70DireccionEnvioCliente
              (Id_Cliente, NombreContacto, TelefonoContacto, Direccion, Id_Distrito, DniReceptor)
            VALUES (?,?,?,?,?,?)";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    // i s s s i s
    mysqli_stmt_bind_param($st, "isssis",
      $idCliente, $nombre, $tel, $dir, $idDistrito, $dniReceptor
    );
    mysqli_stmt_execute($st);
    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /* ==================== SNAPSHOTS t71 ==================== */

  /** GUARDADA: usa el Id_Distrito ya guardado en t70 (no resuelve por nombre). */
  public function crearSnapshotDesdeGuardada(
    int $ordenId,
    int $idCliente,
    int $idDireccionEnvio,
    ?string $receptorDniSnap = null
  ): int {
    $row = $this->obtenerDeCliente($idCliente, $idDireccionEnvio);
    if (!$row) throw new InvalidArgumentException('Dirección guardada inválida para este cliente.');

    $dni = $receptorDniSnap ?: ($row['ReceptorDni'] ?? '');
    if (!preg_match('/^\d{8}$/', $dni)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    $idDistrito = isset($row['Id_Distrito']) ? (int)$row['Id_Distrito'] : null;

    return $this->insertarSnapshotConOrden(
      $ordenId,
      $row['NombreContacto']   ?? '',
      $row['TelefonoContacto'] ?? '',
      $row['Direccion']        ?? '',
      $dni,
      $idDistrito
    );
  }

  /**
   * OTRA: resuelve Id_Distrito por nombre ingresado; guarda snapshot con Id_Distrito.
   * Si guardarEnCatalogo = true, también inserta en t70 usando el Id_Distrito resuelto.
   */
  public function crearSnapshotDesdeOtra(
  int $ordenId, int $idCliente,
  string $nombre, string $tel, string $dir, string $distrito, string $receptorDniSnap,
  bool $guardarEnCatalogo = false
): int {
    $nombre=trim($nombre); $tel=trim($tel); $dir=trim($dir); $distritoNombre=trim($distrito);
    if ($nombre==='' || $tel==='' || $dir==='' || $distritoNombre==='') {
      throw new InvalidArgumentException('Faltan datos de envío (nombre, teléfono, dirección, distrito).');
    }
    if (!preg_match('/^\d{8}$/',$receptorDniSnap)) {
      throw new InvalidArgumentException('DNI del receptor debe tener 8 dígitos.');
    }

    // Resolver Id_Distrito a partir del nombre
    $idDistrito = $this->buscarIdDistritoPorNombre($distrito); // puede ser null para snapshot

  $snapId = $this->insertarSnapshotConOrden(
    $ordenId, $nombre, $tel, $dir, $receptorDniSnap, $idDistrito
  );

  if ($guardarEnCatalogo && $idDistrito) {
    // t70 ahora exige Id_Distrito
    $this->insertar($idCliente, $nombre, $tel, $receptorDniSnap, $dir, $idDistrito);
  }
  return $snapId;
  }
}
