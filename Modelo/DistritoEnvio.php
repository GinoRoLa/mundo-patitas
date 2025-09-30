<?php
/* final class DistritoEnvio {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  public function listarActivos(): array {
    $sql = "SELECT Id_Distrito, DescNombre, MontoCosto
            FROM t77DistritoEnvio
            WHERE Estado='Activo'
            ORDER BY DescNombre;";
    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) return [];
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    return $out;
  }

  public function costoPorNombre(string $nombre): ?float {
    $nombre = trim($nombre);
    if ($nombre === '') return null;

    $sql = "SELECT MontoCosto
              FROM t77DistritoEnvio
             WHERE Estado='Activo'
               AND UPPER(DescNombre) = UPPER(?)
             LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $nombre);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_stmt_close($st);
    return $row ? (float)$row['MontoCosto'] : null;
  }
} */

final class DistritoEnvio {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  private function norm(string $s): string {
    $s = strtoupper(trim($s));
    // normaliza tildes comunes (rápido y suficiente para tus distritos)
    $rep = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
    return strtr($s, $rep);
  }

  public function costoPorNombre(string $nombre): ?float {
    $nombre = $this->norm($nombre);

    // intentamos match exacto normalizado
    $sql = "SELECT MontoCosto FROM t77DistritoEnvio
            WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(DescNombre,
                  'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N')) = ?";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "s", $nombre);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
    if ($row) return (float)$row['MontoCosto'];

    // fallback: LIKE por las dudas
    $like = "%{$nombre}%";
    $sql = "SELECT MontoCosto FROM t77DistritoEnvio
            WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(DescNombre,
                  'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ü','U'),'Ñ','N')) LIKE ?
            ORDER BY CHAR_LENGTH(DescNombre) ASC
            LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
    mysqli_stmt_bind_param($st, "s", $like);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);

    return $row ? (float)$row['MontoCosto'] : null;
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

