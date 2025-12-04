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
                    ode.DireccionSnap                  AS Direccion,
                    dte.Id_Distrito                    AS idDistrito,
                    dte.DescNombre                     AS Distrito,
                    ze.Id_Zona                         AS idZona,
                    ze.DescZona                        AS Zona,
                    op.Peso_total                      AS Peso_Kg,
                    op.Volumen_total                   AS Volumen_m3,
                    GREATEST(0, DATEDIFF(DATE_ADD(op.Fecha, INTERVAL 5 DAY), CURDATE())) AS Dias_Restantes
                FROM t59OrdenServicioEntrega ose
                INNER JOIN t02OrdenPedido op 
                    ON ose.Id_OrdenPedido = op.Id_OrdenPedido
                INNER JOIN t71OrdenDirecEnvio ode 
                    ON op.Id_OrdenPedido = ode.Id_OrdenPedido
                INNER JOIN t77DistritoEnvio dte 
                    ON ode.Id_Distrito = dte.Id_Distrito
                INNER JOIN t76ZonaEnvio ze 
                    ON dte.Id_Zona = ze.Id_Zona
                WHERE ose.Estado = 'Emitido'
                ORDER BY 
                    ze.DescZona ASC,
                    Dias_Restantes ASC,
                    op.Volumen_total ASC;
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

    function direccionAlmacen() {
        $obj = new Conexion();
        $sql = "select * from t73direccionalmacen where Id_DireccionAlmacen = 1;";
        $res = mysqli_query($obj->Conecta(), $sql) or
                die(mysqli_error($obj->Conecta()));
        $fila = mysqli_fetch_array($res);
        return $fila;
    }

    function generarOAR($jsonOAR) {
        $obj = new Conexion();
        $conn = $obj->conecta();

        // Escapar correctamente el JSON
        $jsonOrdenes = mysqli_real_escape_string($conn, $jsonOAR);

        // Llamar al procedimiento correcto
        $sql = "CALL sp_generar_orden_asignacion_reparto('$jsonOrdenes')";
        $res = mysqli_query($conn, $sql);

        if ($res) {
            // Obtener el Id_OrdenAsignacionGenerada devuelto por el procedimiento
            $row = mysqli_fetch_assoc($res);
            $idGenerado = $row['Id_OrdenAsignacionGenerada'] ?? null;

            // Limpiar posibles resultados adicionales (MySQL devuelve varios result sets con CALL)
            while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
                mysqli_store_result($conn);
            }

            // Cerrar conexión
            mysqli_close($conn);

            // Retornar el Id generado
            return $idGenerado;
        } else {
            $error = mysqli_error($conn);
            mysqli_close($conn);
            throw new Exception("Error en sp_generar_orden_asignacion_reparto: $error");
        }
    }
    
    /*public function filtrarRepartidoresPorDias($diasLimite) {
        $obj = new Conexion();
        $dias = $diasLimite - 1;
        // Calculamos la fecha objetivo: hoy + $diasLimite
        $sql = "SELECT 
                    t79.Id_AsignacionRepartidorVehiculo AS CodigoAsignacion,
                    t16.id_Trabajador AS CodigoRepartidor,
                    t78.Placa,
                    t78.Marca,
                    t78.Modelo,
                    t78.CapacidadPesoKg AS CargaUtilKg,
                    t78.Volumen AS CapacidadM3
                FROM t79AsignacionRepartidorVehiculo t79
                INNER JOIN t16CatalogoTrabajadores t16 
                    ON t79.Id_Trabajador = t16.id_Trabajador
                INNER JOIN t78Vehiculo t78 
                    ON t79.Id_Vehiculo = t78.Id_Vehiculo
                WHERE t79.Estado = 'Activo'
                  AND t79.Id_AsignacionRepartidorVehiculo NOT IN (
                        SELECT d.Id_AsignacionRepartidorVehiculo
                        FROM t80DisponibilidadVehiculo d
                        WHERE d.Fecha = DATE_ADD(CURDATE(), INTERVAL $dias DAY)
                          AND d.Estado = 'Ocupado'
                  )
                ORDER BY t16.id_Trabajador;";

        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));

        $vec = [];
        while ($f = mysqli_fetch_assoc($res)) {
            $vec[] = $f;
        }

        return $vec;
    }*/
    
    public function filtrarRepartidoresPorDias($diasLimite) { //cambiar IdRepartidor por CodigoRepartidor
        $obj = new Conexion();
        $dias = $diasLimite + 1;
        // Calculamos la fecha objetivo: hoy + $diasLimite
        $sql = "SELECT 
                    t79.Id_AsignacionRepartidorVehiculo AS CodigoAsignacion,
                    t16.id_Trabajador AS IdRepartidor,
                    t78.Placa,
                    t78.Marca,
                    t78.Modelo,
                    t78.CapacidadPesoKg AS CargaUtilKg,
                    t78.Volumen AS CapacidadM3
                FROM t79AsignacionRepartidorVehiculo t79
                INNER JOIN t16CatalogoTrabajadores t16 
                    ON t79.Id_Trabajador = t16.id_Trabajador
                INNER JOIN t78Vehiculo t78 
                    ON t79.Id_Vehiculo = t78.Id_Vehiculo
                WHERE t79.Estado = 'Activo'
                  AND EXISTS (
                      SELECT 1
                      FROM (
                          SELECT DATE_ADD(CURDATE(), INTERVAL n DAY) AS Fecha
                          FROM (
                              SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
                          ) AS dias
                          WHERE n <= $diasLimite
                      ) AS fechas
                      LEFT JOIN t80DisponibilidadVehiculo d
                          ON d.Id_AsignacionRepartidorVehiculo = t79.Id_AsignacionRepartidorVehiculo
                          AND d.Fecha = fechas.Fecha
                          AND d.Estado = 'Ocupado'
                      WHERE d.Id_AsignacionRepartidorVehiculo IS NULL
                  )
                ORDER BY t16.id_Trabajador;";

        $res = mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));

        $vec = [];
        while ($f = mysqli_fetch_assoc($res)) {
            $vec[] = $f;
        }

        return $vec;
    }

}
