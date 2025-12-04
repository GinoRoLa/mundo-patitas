<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS31Negocio.php';
$obj = new CUS31Negocio();

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

    // ✅ Nombres de claves del JS actual
    $ordenesArray = $data['ordenes'] ?? [];
    $fechaObj = $data['fecha'] ?? null;
    $rutasArray = $data['ruta'] ?? [];

    // =====================================================
    // 🔹 t110OrdenAsignacionReprogramacion (cabecera)
    // =====================================================
    $ordenAsignacionReprog = [];
    if ($fechaObj && isset($fechaObj['idAsignacion'], $fechaObj['fecha'])) {
        $ordenAsignacionReprog[] = [
            'Id_AsignacionRepartidorVehiculo' => (int)$fechaObj['idAsignacion'],
            'FechaProgramada' => $fechaObj['fecha']
        ];
    }

    // =====================================================
    // 🔹 t111DetalleAsignacionReprogramacion (detalle órdenes)
    // =====================================================
    $detalleAsignacionReprog = [];
    foreach ($ordenesArray as $item) {
        if (isset($item['Codigo'])) {  // Tu estructura usa 'Codigo'
            $detalleAsignacionReprog[] = [
                'Id_OPedido' => (int)$item['Codigo']
            ];
        }
    }

    // =====================================================
    // 🔹 t112DetalleRutaReprogramacion (ruta)
    // =====================================================
    $detalleRutaReprog = [];
    foreach ($rutasArray as $item) {
        if (isset($item['Id_Distrito'], $item['DireccionSnap'], $item['Orden'], $item['RutaPolyline'])) {
            $detalleRutaReprog[] = [
                'Id_Distrito' => (int)$item['Id_Distrito'],
                'DireccionSnap' => $item['DireccionSnap'],
                'Orden' => (int)$item['Orden'],
                'RutaPolyline' => $item['RutaPolyline']
            ];
        }
    }

    // =====================================================
    // 🔹 JSON final para tus tablas t110/t111/t112
    // =====================================================
    $jsonFinal = [
        't110OrdenAsignacionReprogramacion' => $ordenAsignacionReprog,
        't111DetalleAsignacionReprogramacion' => $detalleAsignacionReprog,
        't112DetalleRutaReprogramacion' => $detalleRutaReprog
    ];

    // Log para depuración
    error_log("JSON Final para generarOAR: " . json_encode($jsonFinal));

    // =====================================================
    // 🔹 Procesar en negocio (ajusta el método si es necesario)
    // =====================================================
    $idOAR = $obj->generarOAR(json_encode($jsonFinal, JSON_UNESCAPED_UNICODE));

    echo json_encode([
        'success' => true,
        'message' => "Orden de reprogramación #$idOAR generada correctamente.",
        'codigo_orden' => $idOAR,
        'data' => $jsonFinal
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en generarOrdenReprogramacionProxy: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
