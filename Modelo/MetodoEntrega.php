<?php
// /Modelo/MetodoEntrega.php
//include_once '../Controlador/Conexion.php';

final class MetodoEntrega {
    private $cn;
    public function __construct(){ $this->cn = (new Conexion())->conecta(); }

    public function listarActivos(): array {
        $sql = "SELECT Id_MetodoEntrega, Descripcion, Estado
                  FROM t27MetodoEntrega
                 WHERE Estado='Activo'
                 ORDER BY Id_MetodoEntrega";
        $rs = mysqli_query($this->cn, $sql) or die(mysqli_error($this->cn));
        $out=[]; while($r=mysqli_fetch_assoc($rs)) $out[]=$r; return $out;
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT Id_MetodoEntrega, Descripcion, Estado
                  FROM t27MetodoEntrega
                 WHERE Id_MetodoEntrega = ?
                 LIMIT 1";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "i", $id);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs) ?: null;
        mysqli_stmt_close($st);
        return $row;
    }
}
