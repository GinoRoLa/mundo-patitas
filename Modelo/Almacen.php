<?php
final class Almacen
{
    private $cn;
    public function __construct()
    {
        $this->cn = (new Conexion())->conecta();
    }

    /** Devuelve almacenes activos del trabajador: id, nombre, dirección, distrito */
    public function listarPorTrabajadorId(int $idTrabajador): array
    {
        $sql = "SELECT a.Id_DireccionAlmacen AS id,
       a.NombreAlmacen       AS nombre,
       a.DireccionOrigen     AS direccion,
       a.Id_Distrito         AS idDistrito,
       d.DescNombre          AS distritoNombre
       FROM t73DireccionAlmacen a
       JOIN t94TrabajadoresAlmacenes ta
       ON ta.Id_DireccionAlmacen = a.Id_DireccionAlmacen
       JOIN t77DistritoEnvio d ON d.Id_Distrito = a.Id_Distrito
       WHERE ta.id_Trabajador = ?
       AND a.Estado = 'Activo'
       ORDER BY a.NombreAlmacen;
       ";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "i", $idTrabajador);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $rows = [];
        while ($r = mysqli_fetch_assoc($rs)) {
            $rows[] = [
                'id'              => (int)$r['id'],
                'nombre'          => $r['nombre'],
                'direccion'       => $r['direccion'],
                'idDistrito'      => (int)$r['idDistrito'],
                'distritoNombre'  => $r['distritoNombre'],
            ];
        }
        mysqli_stmt_close($st);
        return $rows;
    }
}
