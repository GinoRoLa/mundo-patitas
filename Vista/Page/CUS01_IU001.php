<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/CUS01_IU001.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <title>CUS01 - Generar Preorden de Pedido</title>
    </head>
    <body>
        <?php
        include_once '../../Controlador/Negocio.php';
        $obj = new Negocio();
        $titulo = "IU001 - Registro preorden de pedido";
        $trabajador = "Egoavil Camacho Giro";
        $rol = "Asesor de venta";
        $parametrosComponenteTitulo = [
            "titulo" => $titulo,
            "trabajador" => $trabajador,
            "rol" => $rol
        ];
        #$mejoresVendidos = $obj->bestProducts();
        ?>
        <script>
            $(function () {
                $("#btn-enviar").click(function () {
                    var url = "buscarCliente.php";
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: $("#buscarCliente").serialize(),
                        success: function (data) {
                            $()
                        }
                    });
                });
            });
        </script>
        <main class="container main-content">
            <?php
            include "../Componentes/TituloRolResponsableFechaHora.php";
            ?>
            <section class="customer">
                <div class="customer-crud">
                    <div class="customer-search">
                        <form method="post" id="buscarCliente">
                            <h2>DNI:</h2>
                            <input type="text" placeholder="Ingrese DNI" name="dni-cliente">
                            <input type="submit" value="Buscar" id="btn-enviar">
                        </form>
                    </div>
                </div>
                <div class="customer-information">
                    <div class="customer-details">
                        <h2>Nombre:</h2>
                        <input type="text" readonly>
                    </div>
                    <div class="customer-details">
                        <h2>Telefono:</h2>
                        <input type="text" readonly>
                    </div>
                    <div class="customer-details">
                        <h2>Ape. paterno:</h2>
                        <input type="text" readonly>
                    </div>
                    <div class="customer-details">
                        <h2>Ape. materno:</h2>
                        <input type="text" readonly>
                    </div>
                    <div class="customer-details">
                        <h2>E-mail:</h2>
                        <input type="text" readonly>
                    </div>
                    <div class="customer-details">
                        <h2>Dirección:</h2>
                        <input type="text" readonly>
                    </div>
                </div>
            </section>
            <section class="product">
                <div class="product-list">
                    <h2>Ver la lista de productos</h2>
                    <button>Agregar producto</button>
                </div>
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Precio (S/)</th>
                                <th>Cantidad</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td> 
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td> 
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td> 
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>
                                <td>1</td>  
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Total</td>
                                <td>S/. 199</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
            <section class="preorden-buttons">
                <div class="register">
                    <button>Generar PreOrden</button>
                </div>
                <div class="salir">
                    <button>Generar PreOrden</button>
                </div>
            </section>
        </main>
    </body>
</html>
