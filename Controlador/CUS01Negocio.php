<?php

include_once 'Conexion.php';
class CUS01Negocio {
    
    //AGREGAR PREORDEN
    function generarPreorden($idCliente,$listaProductos){
        $obj= new Conexion();
        $jsonProductos = mysqli_real_escape_string($obj->conecta(), $listaProductos);
        $sql= "call sp_registrarPreorden($idCliente,'$jsonProductos')";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
    }
    
}

?>