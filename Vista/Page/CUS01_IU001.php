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
                            <button class="style-button button-search">Buscar</button>
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
                <div class="product-filters">
                    <form method="post" id="filter-product">
                        <div class="brand">
                            <label>Marca:</label>
                            <select id="brand" name="brand-options">
                                <option value="0">Seleccionar</option>
                                <option value="2">Opción 1</option>
                                <option value="3">Opción 2</option>
                            </select>
                        </div>
                        <div class="price">
                            <label>Precio:</label>
                            <input type="text" placeholder="Mínimo">
                            <input type="text" placeholder="Máximo">
                        </div>
                        <div class="id-product">
                            <label>Código producto:</label>
                            <input type="text">
                        </div>
                        <div class="name-product">
                            <label>Nombre producto:</label>
                            <input type="text">
                        </div>
                        <div class="button-filter">
                            <button class="style-button">Filtrar</button>
                        </div>
                    </form>
                </div>
                <div class="product-table filter-product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Precio (S/)</th>
                                <th>Stock</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr>
                                <td>001</td>
                                <td>Producto A</td>
                                <td>10.50</td>
                                <td>2</td>
                                <td><input type="checkbox"></td>
                            </tr>
                            <tr>
                                <td>001</td>
                                <td>Producto A</td>
                                <td>10.50</td>
                                <td>2</td>
                                <td><input type="checkbox"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="quantity-button">
                    <div class="quantity">
                        <label>Seleccione cantidad:</label>
                        <input type="number" value="1" min="1" onkeydown="return false;">
                    </div>
                    <div class="add-product">
                        <button class="style-button button-add-product">Agregar producto</button>
                    </div>
                </div>
            </section>
            <section class="list-product">
                <div class="product-table list-product-table">
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
                        <tbody id="table-body2">
                            
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
        <script>
            const minRows = 5;
            const tbody = document.getElementById("table-body");
            const tbody2 = document.getElementById("table-body2");
            const currentRows = tbody.rows.length;
            const currentRows2 = tbody2.rows.length;
            if (currentRows < minRows) {
                for (let i = currentRows; i < minRows; i++) {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `<td colspan="5">&nbsp;</td>`;
                    tbody.appendChild(tr);
                }
            }
            if (currentRows2 < minRows) {
                for (let i = currentRows2; i < minRows; i++) {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `<td colspan="5">&nbsp;</td>`;
                    tbody2.appendChild(tr);
                }
            }
        </script>
    </body>
</html>
