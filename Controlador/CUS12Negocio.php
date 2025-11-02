<?php

include_once 'Conexion.php';

class CUS12Negocio {

    //REPORTE INVENTARIO
    function reporteInvetario() {
        $obj = new Conexion();
        $sql = "SELECT * FROM vReporteInventarioGeneral;";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }
}

?>