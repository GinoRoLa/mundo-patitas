<?php 
    include "../dbConexion.php";

    $dniCliente = $_GET['dniCliente'];

    $sql = "
        select * from t20cliente where dnicli = $dniCliente;
    ";
    
    $rs = $cn->prepare($sql); 
    $rs->execute();   

    $rows = $rs->fetchAll(PDO::FETCH_ASSOC); 

    echo json_encode($rows);
    
?>
