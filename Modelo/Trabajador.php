<?php
// /Modelo/Trabajador.php
require_once __DIR__ . '/../Controlador/Conexion.php';

final class Trabajador {
    private $cn;
    public function __construct(){ $this->cn = (new Conexion())->conecta(); }

    public function buscarPorDni(string $dni): ?array {
        $sql = "SELECT id_Trabajador, DniTrabajador, des_apepatTrabajador, des_apematTrabajador,
                       des_nombreTrabajador, cargo, estado
                  FROM t16CatalogoTrabajadores
                 WHERE DniTrabajador = ? AND estado='Activo' LIMIT 1";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "s", $dni);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs) ?: null;
        mysqli_stmt_close($st);
        return $row;
    }
}
