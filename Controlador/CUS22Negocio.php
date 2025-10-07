<?php

include_once 'Conexion.php';

class CUS22Negocio {

    function listaZonas() {
        $obj = new Conexion();
        $sql = "select * from t76zonaenvio where Estado = 'Activo';";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }

    function listaOSE() {
        $obj = new Conexion();
        $sql = "SELECT 
                    ose.Id_OSE                         AS Codigo_OSE,
                    op.Id_OrdenPedido                  AS Codigo_OP,
                    dte.DescNombre                     AS Distrito,
                    ze.DescZona                        AS Zona,
                    op.Peso_total                      AS Peso_Kg,
                    op.Volumen_total                   AS Volumen_m3,
                    GREATEST(0, DATEDIFF(DATE_ADD(op.Fecha, INTERVAL 5 DAY), CURDATE())) AS Dias_Restantes
                FROM t59OrdenServicioEntrega ose
                INNER JOIN t02OrdenPedido op 
                    ON ose.Id_OrdenPedido = op.Id_OrdenPedido
                INNER JOIN t70DireccionEnvioCliente decl 
                    ON op.Id_Cliente = decl.Id_Cliente
                INNER JOIN t77DistritoEnvio dte 
                    ON decl.Id_Distrito = dte.Id_Distrito
                INNER JOIN t76ZonaEnvio ze 
                    ON dte.Id_Zona = ze.Id_Zona
                ORDER BY 
                    ze.DescZona ASC,
                    Dias_Restantes ASC;";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }
    
    function listaRepartidores() {
        $obj = new Conexion();
        $sql = "SELECT 
                t79.Id_AsignacionRepartidorVehiculo AS CodigoAsignacion,
                t16.id_Trabajador       AS CodigoRepartidor,
                t78.Placa               AS Placa,
                t78.Marca               AS Marca,
                t78.Modelo              AS Modelo,
                t78.CapacidadPesoKg     AS CargaUtilKg,
                t78.Volumen             AS CapacidadM3
              FROM t79AsignacionRepartidorVehiculo AS t79
              INNER JOIN t16CatalogoTrabajadores AS t16
                ON t79.Id_Trabajador = t16.id_Trabajador
              INNER JOIN t78Vehiculo AS t78
                ON t79.Id_Vehiculo = t78.Id_Vehiculo
              WHERE t79.Estado = 'Activo' ORDER BY t16.id_Trabajador;";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }
    
    function disponibilidadRepaVehi($CodigoAsignacion) {
        $obj = new Conexion();
        $sql = "select * from t80disponibilidadvehiculo where Id_AsignacionRepartidorVehiculo = $CodigoAsignacion;";
        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec = array();
        while ($f = mysqli_fetch_array($res)) {
            $vec[] = $f;
        }
        return $vec;
    }
}
