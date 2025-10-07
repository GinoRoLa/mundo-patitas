<?php 
    include "../dbConexion.php";

    $notaCredito = $_GET['notaCredito'];
    $sql = "
        select Total  from t21notacredito where codigoNotaCredito  = $notaCredito;
    ";
    $rs = $cn->prepare($sql); 
    $rs->execute();   

    $rows = $rs->fetchAll(PDO::FETCH_ASSOC); 

    echo json_encode($rows);
    
?>
