<?php 
    include "../dbConexion.php";

    $ordenPedido = $_GET['ordenPedido'];


    $sql = "
    select 
        t18catalogoproducto.NombreProducto,
        t60detordenpedido.cantidad,
        t18catalogoproducto.PrecioUnitario
    from t60detordenpedido 
    join t18catalogoproducto
        on t18catalogoproducto.Id_Producto = t60detordenpedido.t18CatalogoProducto_Id_Producto
    where t02OrdenPedido_Id_OrdenPedido = $ordenPedido;
    ";
    $rs = $cn->prepare($sql); 
    $rs->execute();   

    $rows = $rs->fetchAll(PDO::FETCH_ASSOC); 

    echo json_encode($rows);
    
?>
