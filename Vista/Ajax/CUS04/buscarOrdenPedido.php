<?php

header('Content-Type: application/json');
include_once '../../../Controlador/Negocio.php';
$obj = new Negocio();
$filtro = $_POST['filtroOrden'] ?? '';
$valor = $_POST['filtroOrdenPedido'] ?? '';
$sql1 = "";
$sql2 = "";
switch ($filtro) {
    case 'notaDistribucion':
        $sql1 = "select * from ordenesCSE where Id_NotaDistribucion =$valor;";
        $sql2 = "SELECT p.Id_Producto, p.Descripcion, p.PrecioUnitario, d.Cantidad FROM t62notadistribucion nd
            INNER JOIN t02ordenpedido o ON nd.Id_OrdenPedido = o.Id_OrdenPedido INNER JOIN t01preordenpedido pre ON pre.t02OrdenPedido_Id_OrdenPedido = o.Id_OrdenPedido
            INNER JOIN t61detapreorden d ON d.t01PreOrdenPedido_Id_PreOrdenPedido = pre.Id_PreOrdenPedido INNER JOIN t18catalogoproducto p ON d.t18CatalogoProducto_Id_Producto = p.Id_Producto
            WHERE nd.Id_NotaDistribucion = $valor;";
        break;
    case 'codigoOrdenPedido':
        $sql1 = "select * from ordenesSSE where Id_OrdenPedido =$valor;";
        $sql2 = "SELECT p.Id_Producto, p.Descripcion, p.PrecioUnitario, d.Cantidad FROM t61detapreorden d INNER JOIN t18catalogoproducto p
            ON d.t18CatalogoProducto_Id_Producto = p.Id_Producto INNER JOIN t01preordenpedido pre ON d.t01PreOrdenPedido_Id_PreOrdenPedido = pre.Id_PreOrdenPedido
            INNER JOIN t02ordenpedido o ON pre.t02OrdenPedido_Id_OrdenPedido = o.Id_OrdenPedido WHERE o.Id_OrdenPedido = $valor AND o.Estado = 'Pagado' AND o.Id_MetodoEntrega = 9001;";
        break;
    case 'dniCliente':
        $sql1 = "select * from ordenesSSE where Dni_Cliente =$valor;";
        $sql2 = "SELECT p.Id_Producto, p.Descripcion, p.PrecioUnitario, d.Cantidad FROM t61detapreorden d INNER JOIN t18catalogoproducto p
            ON d.t18CatalogoProducto_Id_Producto = p.Id_Producto INNER JOIN t01preordenpedido pre ON d.t01PreOrdenPedido_Id_PreOrdenPedido = pre.Id_PreOrdenPedido
            INNER JOIN t20cliente c ON pre.t20Cliente_Id_Cliente = c.Id_Cliente INNER JOIN t02ordenpedido o ON pre.t02OrdenPedido_Id_OrdenPedido = o.Id_OrdenPedido
            WHERE c.DniCli = $valor AND o.Estado = 'Pagado' AND o.Id_MetodoEntrega = 9001;";
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Seleccione un filtro válido.']);
        exit;
}

$detalleDatosOrden = $obj-> BuscarPreOrden($sql1);
$productosOrden = $obj -> BuscarDetallePreOrden($sql2);

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

