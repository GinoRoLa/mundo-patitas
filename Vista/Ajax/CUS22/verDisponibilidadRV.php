<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS22Negocio.php';
$obj = new CUS22Negocio();

if (isset($_GET['CodigoAsignacion'])) {
    $codigoAsignacion = $_GET['CodigoAsignacion'];

    try {
        // Llama al método que devuelve los días ocupados
        $disponibilidadRV = $obj->disponibilidadRepaVehi($codigoAsignacion);

        if ($disponibilidadRV && count($disponibilidadRV) > 0) {
            // Retornamos solo los días ocupados con estado "Ocupado"
            $resultado = array_map(function($item) {
                return [
                    'fecha' => $item['Fecha'],
                    'estado' => 'Ocupado'
                ];
            }, $disponibilidadRV);

            echo json_encode($resultado);
        } else {
            // Si no hay registros, enviamos un arreglo vacío
            echo json_encode([]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Faltan parámetros']);
}
