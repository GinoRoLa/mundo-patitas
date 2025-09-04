<?php

include_once 'Conexion.php';

class CUS04Negocio {

    //AGREGAR PREORDEN
    function generarSalidaAlmacen($idOrdenPedido, $estado) {
        $obj = new Conexion();
        $conn = $obj->conecta();
        $sql = "call registrarMovimiento($idOrdenPedido,'$estado')";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        if ($res) {
            while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
                mysqli_store_result($conn);
            }
            return true;
        } else {
            throw new Exception("Error en registrarMovimiento: " . mysqli_error($conn));
        }
    }
}

?>