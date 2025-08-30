<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/CUS01_IU002.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/CUS01_IU001.css" rel="stylesheet" type="text/css"/>
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
                <h1>IU002- Registro de  Preorden de Pedido</h1>
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
            <section class="product">
                <div class="product-list">
                    <h2>Código de producto</h2>
                    <input type="text">
                    <button>Buscar producto</button>
                </div>
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Precio (S/)</th>
                                <th>Stock disponible</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="add-product">
                <div class="cantidad">
                    <h2>Cantidad:</h2>
                    <input type="number" min="1" value="1">
                </div>
                <div class="add-product-button">
                    <button>Agregar producto</button>
                </div>
                <div class="exit-product-button">
                    <button>Salir</button>
                </div>
            </section>
        </main>
    </body>
</html>
