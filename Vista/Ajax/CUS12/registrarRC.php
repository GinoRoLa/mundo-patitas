<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS12Negocio.php';

try {
    // Leer cuerpo JSON
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['json']) || !isset($input['total']) || !isset($input['preciopromedio'])) {
        throw new Exception("Datos incompletos o inválidos recibidos.");
    }

    $json = json_encode($input['json']); // Convertir nuevamente a JSON string
    $total = floatval($input['total']);
    $preciopromedio = floatval($input['preciopromedio']);

    $obj = new CUS12Negocio();
    $idRequerimiento = $obj->generarRequerimiento($json, $total, $preciopromedio);

    if ($idRequerimiento) {
        echo json_encode([
            "success" => true,
            "id" => $idRequerimiento,
            "message" => "Requerimiento generado correctamente."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No se generó el requerimiento. Verifique los datos."
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>
