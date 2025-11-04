<?php

include_once 'Conexion.php';

class CUS12Negocio {

    //REPORTE INVENTARIO
    function reporteInvetario() {
        $obj = new Conexion();
        $vec = array(); // Por defecto vacío

        try {
            $conexion = $obj->conecta();
            if (!$conexion) {
                throw new Exception("Error de conexión a la base de datos.");
            }

            $sql = "SELECT * FROM vReporteInventarioGeneral;";
            $res = mysqli_query($conexion, $sql);

            if (!$res) {
                // Error en la consulta o vista no existe
                throw new Exception("No se pudo ejecutar la consulta o la vista no existe.");
            }

            while ($f = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
                $vec[] = $f;
            }
        } catch (Exception $e) {
            // Log en consola del servidor (no rompe la vista)
            error_log("❌ Error en reporteInvetario(): " . $e->getMessage());
            $vec = []; // Devolvemos array vacío para no romper la interfaz
        }

        return $vec;
    }
    
    function generarRequerimiento($jsonRequerimiento,$total,$preciopromedio) {
        $obj = new Conexion();
        $conn = $obj->conecta();

        // Escapar correctamente el JSON
        $jsonOrdenes = mysqli_real_escape_string($conn, $jsonRequerimiento);

        // Llamar al procedimiento correcto
        $sql = "CALL sp_GenerarRequerimientoCompra('$jsonOrdenes',$total,$preciopromedio)";
        $res = mysqli_query($conn, $sql);

        if ($res) {
            // Obtener el Id_OrdenAsignacionGenerada devuelto por el procedimiento
            $row = mysqli_fetch_assoc($res);
            $idGenerado = $row['Id_RequerimientoGenerado'] ?? null;

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
            throw new Exception("Error en sp_GenerarRequerimientoCompra: $error");
        }
    }
}

?>