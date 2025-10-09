<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS22Negocio.php';
$obj = new CUS22Negocio();

try {
    if (!isset($_POST['data'])) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos.']);
        exit;
    }

    $data = json_decode($_POST['data'], true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
        exit;
    }

    // Arrays originales
    $oseArray = $data['ose'] ?? [];
    $fechasArray = $data['fechas'] ?? [];
    $rutasArray = $data['rutas'] ?? $data['repartidor'] ?? [];

    // =====================================================
    // 🔹 t40OrdenAsignacionReparto
    // =====================================================
    $ordenAsignacion = [];
    foreach ($fechasArray as $item) {
        $ordenAsignacion[] = [
            'Id_AsignacionRepartidorVehiculo' => $item['idAsignacion'],
            'FechaProgramada' => $item['fecha']
        ];
    }

    // =====================================================
    // 🔹 t401DetalleAsignacionReparto
    // =====================================================
    $detalleAsignacion = [];
    foreach ($oseArray as $item) {
        if (isset($item['Codigo_OSE'])) {
            $detalleAsignacion[] = [
                'Id_OSE' => $item['Codigo_OSE']
            ];
        }
    }

    // =====================================================
    // 🔹 t402DetalleRuta
    // =====================================================
    $detalleRuta = [];
    foreach ($rutasArray as $item) {
        if (isset($item['Id_Distrito'], $item['DireccionSnap'], $item['Orden'], $item['RutaPolyline'])) {
            $detalleRuta[] = [
                'Id_Distrito'   => $item['Id_Distrito'],
                'DireccionSnap' => $item['DireccionSnap'],
                'Orden'         => $item['Orden'],
                'RutaPolyline'  => $item['RutaPolyline']
            ];
        }
    }

    // =====================================================
    // 🔹 Construir JSON final limpio
    // =====================================================
    $jsonFinal = [
        't40OrdenAsignacionReparto' => $ordenAsignacion,
        't401DetalleAsignacionReparto' => $detalleAsignacion,
        't402DetalleRuta' => $detalleRuta
    ];

    // =====================================================
    // 🔹 Procesar en la capa de negocio
    // =====================================================
    $idOAR = $obj->generarOAR(json_encode($jsonFinal, JSON_UNESCAPED_UNICODE));  // ✅ sin comillas

    // =====================================================
    // 🔹 Respuesta final al AJAX
    // =====================================================
    echo json_encode([
        'success' => true,
        'message' => "Orden de asignación de reparto #$idOAR generada correctamente.",
        'codigo_orden' => $idOAR,
        'data' => $jsonFinal
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
