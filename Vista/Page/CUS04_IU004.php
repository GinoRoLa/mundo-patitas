<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>CUS04 - Preparar pedido de venta</title>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS04/CUS04_IU004.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleTittleGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleInputGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleTbodyTable.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleButtonGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleFormularioDatos.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleInputNumberSinSpinner.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <?php
        include_once '../../Controlador/Negocio.php';
        $obj = new Negocio();
        $titulo = "IU004 - Preparar pedido de venta";
        $trabajador = "Egoavil Camacho Giro";
        $rol = "Responsanble de almacén";
        $parametrosComponenteTitulo = [
            "titulo" => $titulo,
            "trabajador" => $trabajador,
            "rol" => $rol
        ];
        ?>
        <main class="container main-content">
            <?php
            include "../Componentes/TituloRolResponsableFechaHora.php";
            ?>
            <section class="orden-pedido">
                <form method="post" id="buscarPreorden" class="form-search-orden">
                    <div class="orden-cse">
                        <label class="labelText">Con servicio de entrega:</label>
                        <label class="checkbox-container">
                            <input type="radio" name="filtroOrden" value="notaDistribucion">
                            <span class="checkmark"></span>
                            <span class="textSpanCheckmark">Nota de distribución</span>
                        </label>
                    </div>
                    <div class="orden-sse">
                        <label class="labelText">Sin servicio de entrega:</label>
                        <div class="checkbox-option">
                            <label class="checkbox-container">
                                <input type="radio" name="filtroOrden" value="codigoOrdenPedido">
                                <span class="checkmark"></span>
                                <span class="textSpanCheckmark">Código de orden pedido</span>
                            </label>
                            <label class="checkbox-container">
                                <input type="radio" name="filtroOrden" value="dniCliente">
                                <span class="checkmark"></span>
                                <span class="textSpanCheckmark">DNI cliente</span>
                            </label>
                        </div>
                    </div>
                    <div class="input-search">
                        <input type="number" name="filtroOrdenPedido" class="input-style-number-spinner" min="0">
                        <button class="style-button button-search-orden" type="submit">Buscar</button>
                    </div>                        
                </form>
                <div class="form-data-information">
                    <div class="form-data-details">
                        <h2>Código Orden:</h2>
                        <input type="text" readonly id="codigoOrden">
                    </div>
                    <div class="form-data-details">
                        <h2>Total:</h2>
                        <input type="text" readonly id="totalOrden">
                    </div>
                    <div class="form-data-details">
                        <h2>Fecha:</h2>
                        <input type="text" readonly id="fechaOrden">
                    </div>
                    <div class="form-data-details">
                        <h2>DNI cliente:</h2>
                        <input type="text" readonly id="dniCliente">
                    </div>
                </div>
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Precio (S/)</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="table-body3">

                        </tbody>
                    </table>
                </div>
            </section>
            <section class="register-movimiento-buttons">
                <div class="register">
                    <form method="post" class="form-register-orden" id="register-orden">
                        <button class="style-button-disabled generar-preorden-button" id="generar-orden" disabled>Generar salida de almacén</button>
                    </form>
                </div>
                <div class="salir">
                    <button class="style-button">Salir</button>
                </div>
            </section>
        </main>
        <script src="../Script/CUS01/TBodyScript.js" type="text/javascript"></script>
        <script src="../Script/CUS01/salirBoton.js" type="text/javascript"></script>
        <script src="../Script/CUS04/buscarOrdenPedidoJQuery.js" type="text/javascript"></script>
        <script src="../Script/CUS04/registarSalidaAlmacen.js" type="text/javascript"></script>
    </body>
</html>
