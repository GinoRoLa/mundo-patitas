    <?php
final class DistritoEnvio {
  private $cn;
  public function __construct() { $this->cn = (new Conexion())->conecta(); }

  public function listarActivos(): array {
    $sql = "SELECT Id_Distrito, NombreDistrito, CostoEnvio
            FROM t77DistritoEnvio
            WHERE Estado='Activo'
            ORDER BY NombreDistrito";
    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) return [];
    $out = [];
    while ($r = mysqli_fetch_assoc($rs)) $out[] = $r;
    return $out;
  }

  public function costoPorNombre(string $nombre): ?float {
    $nombre = trim($nombre);
    if ($nombre === '') return null;

    $sql = "SELECT CostoEnvio
              FROM t77DistritoEnvio
             WHERE Estado='Activo'
               AND UPPER(NombreDistrito) = UPPER(?)
             LIMIT 1";
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $nombre);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_stmt_close($st);
    return $row ? (float)$row['CostoEnvio'] : null;
  }
}
