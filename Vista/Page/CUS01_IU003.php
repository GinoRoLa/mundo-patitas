<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <link href="../Style/CUS01/CUS01_IU001.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
        <title>CUS01 - Generar Preorden de Pedido</title>
    </head>
    <body>
        <?php
        include_once '../../Controlador/Negocio.php';
        $obj = new Negocio();
        $fecha = new DateTime();

        #$mejoresVendidos = $obj->bestProducts();
        ?>
        <main class="container main-content">
            <section class="title">
                <h1>IU003- Registrar cliente</h1>
            </section>
            <section class="data">
                <div class="responsable">
                    <h2>Responsable:</h2>
                    <input type="text" readonly value="Egoavil Camacho Giro">
                </div>
                <div class="rol">
                    <h2>Rol:</h2>
                    <input type="text" readonly value="Responsable de pedidos">
                </div>
                <div class="date">
                    <h2>Fecha:</h2>
                    <input type="text" readonly value="<?php echo $fecha->format("d-m-Y"); ?>">
                </div>
            </section>
            <section class="customer">
                <div class="customer-information">
                    <div class="customer-details">
                        <h2>DNI:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>Nombre:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>Telefono:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>Ape. paterno:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>Ape. materno:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>E-mail:</h2>
                        <input type="text">
                    </div>
                    <div class="customer-details">
                        <h2>Dirección:</h2>
                        <input type="text">
                    </div>
                </div>
                <div class="customer-crud">
                    <div class="customer-search">
                        <input type="submit" value="Registrar cliente">
                    </div>
                    <div class="customer-register">
                        <input type="submit" value="Salir">
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
