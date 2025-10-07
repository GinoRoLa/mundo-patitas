<?php 
    include "../dbConexion.php";

    $ordenPedido = $_GET['ordenPedido'];
    $sql = "
        select 
            t27metodoentrega.Descripcion as 'MetodoEntrega',
            t27metodoentrega.costo as 'CostoEnvio',
            t02ordenpedido.Descuento as 'DescuentoTotal'
        from t02ordenpedido 
        join t27metodoentrega
            on t27metodoentrega.Id_MetodoEntrega = t02ordenpedido.Id_MetodoEntrega
        where Id_OrdenPedido = $ordenPedido;
    ";
    $rs = $cn->prepare($sql); 
    $rs->execute();   

    $rows = $rs->fetchAll(PDO::FETCH_ASSOC); 

    echo json_encode($rows);
    
?>
