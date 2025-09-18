<?php

header('Content-Type: application/json');
include_once '../../../Controlador/Negocio.php';
$obj = new Negocio();
$filtro = $_POST['filtroOrden'] ?? '';
$valor = $_POST['filtroOrdenPedido'] ?? '';
$sql1 = "";
$sql2 = "";
switch ($filtro) {
    case 'codigoOrdenPedido':
        $sql1 = "SELECT o.Id_OrdenPedido, c.DniCli, DATE(o.Fecha), o.Estado, o.Total, m.Descripcion FROM t02ordenpedido o INNER JOIN t20cliente c ON o.Id_Cliente = c.Id_Cliente LEFT JOIN t27metodoentrega m ON o.Id_MetodoEntrega = m.Id_MetodoEntrega where o.Estado = 'Pagado' AND o.Id_MetodoEntrega = 9001 and o.Id_OrdenPedido=$valor;";
        break;
    case 'dniCliente':
        $sql1 = "SELECT o.Id_OrdenPedido, c.DniCli, DATE(o.Fecha), o.Estado, o.Total, m.Descripcion FROM t02ordenpedido o INNER JOIN t20cliente c ON o.Id_Cliente = c.Id_Cliente LEFT JOIN t27metodoentrega m ON o.Id_MetodoEntrega = m.Id_MetodoEntrega where o.Estado = 'Pagado' AND o.Id_MetodoEntrega = 9001 and c.DniCli=$valor;";
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Seleccione un filtro válido.']);
        exit;
}

$detalleDatosOrden = $obj-> BuscarPreOrden($sql1);

if ($detalleDatosOrden && $productosOrden) {
    echo json_encode([
        'success' => true,
        'orden' => $detalleDatosOrden[0],  
        'productos' => $productosOrden
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontraron resultados.'
    ]);
}
?>

