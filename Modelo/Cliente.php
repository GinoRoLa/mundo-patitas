<?php
// /Modelo/Cliente.php
//include_once '../Controlador/Conexion.php';

final class Cliente {

  function buscarPorDni($DniCliente) {
        $obj = new Conexion();
        $sql = "Select * from t20cliente where DniCli = '$DniCliente' and estado='Activo';";
        $res = mysqli_query($obj->Conecta(), $sql) or
                die(mysqli_error($obj->Conecta()));
        $fila = mysqli_fetch_array($res);
        return $fila;
    }
}
