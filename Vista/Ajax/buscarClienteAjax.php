<?php

header('Content-Type: application/json');
include_once '../../Controlador/Negocio.php';
$obj = new Negocio();

if (isset($_POST['dniCliente'])) {
    
    $DniCliente = $_POST['dniCliente'];
    $Cliente = $obj ->BuscarCliente($DniCliente);
    if ($Cliente) {
        echo json_encode([
            'success' => true,
            'cliente' => $Cliente
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Cliente no encontrado.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'DNI no recibido.'
    ]);
}

?>

