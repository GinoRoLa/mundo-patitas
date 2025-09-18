<?php

include_once 'Conexion.php';

class CUS04Negocio {

//LISTA DE PRODUCTOS
    function listaOrdenesPedidoSSE() {
        $obj = new Conexion();
        $sql = "SELECT o.Id_OrdenPedido, c.DniCli, DATE(o.Fecha) AS Fecha, o.Estado, o.Total, m.Descripcion FROM t02ordenpedido o INNER JOIN t20cliente c ON o.Id_Cliente = c.Id_Cliente LEFT JOIN t27metodoentrega m ON o.Id_MetodoEntrega = m.Id_MetodoEntrega where o.Estado = 'Pagado' AND o.Id_MetodoEntrega = 9001;";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }

//AGREGAR PREORDEN
    function generarSalidaAlmacen($listaOrdenes, $estado) {
        $obj = new Conexion();
        $conn = $obj->conecta();

        $jsonOrdenes = mysqli_real_escape_string($conn, $listaOrdenes);

        $sql = "CALL registrarMovimiento('$jsonOrdenes','$estado')";
        $res = mysqli_query($conn, $sql);

        if ($res) {
            // limpiar resultados múltiples
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