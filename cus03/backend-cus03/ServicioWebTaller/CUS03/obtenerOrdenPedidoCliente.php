<?php 
    include "../dbConexion.php";

    $clienteElegido = $_GET['clienteElegido'];


    $sql = "
        select 
            t02ordenpedido.Id_OrdenPedido,
            t02ordenpedido.Fecha,
            t02ordenpedido.Total,
            t02ordenpedido.estado
        from t02ordenpedido
        join t20cliente
            on t20cliente.id_cliente=t02ordenpedido.Id_Cliente
        where t20cliente.DniCli = $clienteElegido
        and t02ordenpedido.estado = 'generada'
        ;
    ";
    $rs = $cn->prepare($sql); 
    $rs->execute();   

    $rows = $rs->fetchAll(PDO::FETCH_ASSOC); 

    echo json_encode($rows);
    
?>
