<?php
    include "../dbConexion.php";

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    
    $itemsOrdenPedido   = $data['itemsOrdenPedido']  ?? []; 
    $idMetodoComprobante    = $data['idMetodoComprobante']    ?? 0;
    $idTrabajador = $data['idTrabajador'] ?? 0;
    $idMetodoPago = $data['idMetodoPago'] ?? 0;
    $idNotaCredito = $data['idNotaCredito'] ?? null;

    $itemsJson = json_encode($itemsOrdenPedido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sql = "CALL createComprobantePago(:a, :b, :c, :d, :e)";
    $rs  = $cn->prepare($sql);
    $rs->execute([
    ':a' => $itemsJson,
    ':b' => $idMetodoComprobante,
    ':c' => $idTrabajador,
    ':d' => $idMetodoPago,
    ':e' => $idNotaCredito
    ]);

    echo json_encode(["ok" => true, "mensaje" => "Comprobante de pago creado"]);
?>
