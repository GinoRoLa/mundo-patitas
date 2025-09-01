<?php

header('Content-Type: application/json');
include_once '../../../Controlador/Negocio.php';
$obj = new Negocio();
$brand = $_POST['brand-options'] ?? null;
$minPrice = $_POST['price-min'] ?? null;
$maxPrice = $_POST['price-max'] ?? null;
$code = $_POST['code'] ?? null;
$name = $_POST['name'] ?? null;

$sql = "SELECT * FROM t18catalogoproducto WHERE 1=1";
if ($brand && $brand != 0)
    $sql .= " AND Marca = '$brand'";
if ($minPrice)
    $sql .= " AND PrecioUnitario >= $minPrice";
if ($maxPrice)
    $sql .= " AND PrecioUnitario <= $maxPrice";
if ($code)
    $sql .= " AND Id_Producto = $code";
if ($name)
    $sql .= " AND NombreProducto LIKE '%$name%'";

$productosFiltrados = $obj->filtroProducto($sql);
if ($productosFiltrados) {
    echo json_encode([
        'success' => true,
        'productos' => $productosFiltrados
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado.'
    ]);
}
?>

