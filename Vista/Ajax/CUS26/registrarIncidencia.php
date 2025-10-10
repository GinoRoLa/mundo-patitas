<?php
header('Content-Type: application/json');
include_once '../../../Controlador/CUS26Negocio.php';

try {
    if (
        empty($_POST['IDPedido']) ||
        empty($_POST['Cliente']) ||
        empty($_POST['Direccion']) ||
        empty($_POST['Motivo']) ||
        empty($_POST['Observaciones'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
        exit;
    }

    $data = [
        'IDPedido'      => intval($_POST['IDPedido']),
        'Cliente'       => $_POST['Cliente'],
        'Direccion'     => $_POST['Direccion'],
        'Motivo'        => $_POST['Motivo'],
        'Observaciones' => $_POST['Observaciones'],
        'Estado'        => ($_POST['Motivo'] === 'Ausencia del receptor' || $_POST['Motivo'] === 'Acceso restringido')
            ? 'Reprogramada'
            : 'No entregado'
    ];

    // Si hay imagen
    if (!empty($_FILES['foto']['name'])) {
        $ruta = '../../../Uploads/Incidencias/';
        if (!file_exists($ruta)) mkdir($ruta, 0777, true);

        $nombreArchivo = time() . "_" . basename($_FILES['foto']['name']);
        $rutaDestino = $ruta . $nombreArchivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $data['Foto'] = $nombreArchivo;
        }
    }

    $obj = new CUS26Negocio();
    $res = $obj->registrarIncidencia($data);

    if ($res) {
        echo json_encode(['success' => true, 'message' => 'Incidencia registrada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar incidencia.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
