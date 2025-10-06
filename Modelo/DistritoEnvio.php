<?php
final class DistritoEnvio {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  public function costoPorId(int $idDistrito): ?float {
    $sql = "SELECT MontoCosto
              FROM t77DistritoEnvio
             WHERE Id_Distrito = ?
               AND UPPER(TRIM(Estado)) IN ('ACTIVO','ACTIVE')
             LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) throw new RuntimeException(mysqli_error($this->cn));
    mysqli_stmt_bind_param($st, "i", $idDistrito);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
    return $row ? (float)$row['MontoCosto'] : null;
  }

  public function idPorNombre(string $nombre): ?int {
  $nombre = trim($nombre);
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


  public function listarActivos(): array {
    $sql = "SELECT Id_Distrito, DescNombre, MontoCosto, Estado
            FROM t77DistritoEnvio
            WHERE Estado = 'Activo'
            ORDER BY DescNombre";
    $rs = mysqli_query($this->cn, $sql);
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    return $out;
  }
}

