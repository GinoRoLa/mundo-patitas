<?php
// /Modelo/Producto.php
require_once __DIR__ . '/../Controlador/Conexion.php';

final class Producto {
    private $cn;
    public function __construct(){ $this->cn = (new Conexion())->conecta(); }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM t18CatalogoProducto WHERE Id_Producto = ? LIMIT 1";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "i", $id);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs) ?: null;
        mysqli_stmt_close($st);
        return $row;
    }
}
