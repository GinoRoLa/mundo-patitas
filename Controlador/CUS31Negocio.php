<?php

include_once 'Conexion.php';

class CUS31Negocio {
    
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
    
    function direccionAlmacen() {
        $obj = new Conexion();
        $sql = "select * from t73direccionalmacen where Id_DireccionAlmacen = 1;";
        $res = mysqli_query($obj->Conecta(), $sql) or
                die(mysqli_error($obj->Conecta()));
        $fila = mysqli_fetch_array($res);
        return $fila;
    }
    
    function listaOrdenesPedido(){
        $obj = new Conexion();
        $sql = "SELECT 
                    g.Id_OrdenPedido AS `Codigo`,
                    d.DescNombre AS `Distrito`,
                    od.Id_Distrito AS `idDistrito`,
                    z.Id_Zona AS `idZona`,
                    z.DescZona AS `Zona`,
                    od.DireccionSnap AS `Direccion`,
                    o.Peso_total AS `Peso`,
                    o.Volumen_total AS `Volumen`,
                    c.Id_Repartidor AS `IdRepartidor`,
                    GREATEST(
                        DATEDIFF(g.FechaLimiteReprogramacion, CURDATE()),
                        0
                    ) AS `DiasRestantes`,
                    (
                        SELECT COUNT(*) 
                        FROM t172GestionNoEntregados g2 
                        WHERE g2.Id_OrdenPedido = g.Id_OrdenPedido
                    ) AS `Numero`
                FROM t172GestionNoEntregados g
                INNER JOIN t171consolidacion_entrega c 
                    ON g.Id_Consolidacion = c.ID_Consolidacion
                INNER JOIN t71OrdenDirecEnvio od 
                    ON g.Id_OrdenPedido = od.Id_OrdenPedido
                LEFT JOIN t77distritoenvio d 
                    ON od.Id_Distrito = d.Id_Distrito
                LEFT JOIN t76zonaenvio z 
                    ON d.Id_Zona = z.Id_Zona
                INNER JOIN t02ordenpedido o 
                    ON g.Id_OrdenPedido = o.Id_OrdenPedido
                ORDER BY 3,6,7;
                ";
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
                t16.id_Trabajador       AS IdRepartidor,
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
        while ($f = mysqli_fetch_assoc($res)) {
            $vec[] = $f;
        }
        return $vec;
    }
}
