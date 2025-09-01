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
        $marcas = $obj->listaMarcas();
        $productos = $obj->listaProductos();
        ?>
        <script>
            window.productosOriginales = <?php echo json_encode($productos); ?>;
        </script>
        <main class="container main-content">
            <?php
            include "../Componentes/TituloRolResponsableFechaHora.php";
            ?>
            <section class="customer">
                <div class="customer-crud">
                    <div class="customer-search">
                        <form method="post" id="buscarCliente" class="form-search-customer">
                            <h2>DNI:</h2>
                            <input type="text" placeholder="Ingrese DNI" name="dniCliente">
                            <button class="style-button button-search" type="submit">Buscar</button>
                        </form>
                    </div>
                </div>
                <div class="customer-information">
                    <div class="customer-details">
                        <h2>Nombre:</h2>
                        <input type="text" readonly id="nombreCliente">
                    </div>
                    <div class="customer-details">
                        <h2>Telefono:</h2>
                        <input type="text" readonly id="telefonoCliente">
                    </div>
                    <div class="customer-details">
                        <h2>Ape. paterno:</h2>
                        <input type="text" readonly id="apepatCliente">
                    </div>
                    <div class="customer-details">
                        <h2>Ape. materno:</h2>
                        <input type="text" readonly id="apematCliente">
                    </div>
                    <div class="customer-details">
                        <h2>E-mail:</h2>
                        <input type="text" readonly id="emailCliente">
                    </div>
                    <div class="customer-details">
                        <h2>Dirección:</h2>
                        <input type="text" readonly id="direccionCliente">
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
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= trim(htmlspecialchars($m['Marca'])) ?>">
                                        <?= htmlspecialchars($m['Marca']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="price">
                            <label>Precio:</label>
                            <input type="number" placeholder="Mínimo" name="price-min" id="price-min" min="1">
                            <input type="number" placeholder="Máximo" name="price-max" id="price-max" min="1">
                        </div>
                        <div class="id-product">
                            <label>Código producto:</label>
                            <input type="number" name="code" id="code" min="1">
                        </div>
                        <div class="name-product">
                            <label>Nombre producto:</label>
                            <input type="text" name="name" id="name">
                        </div>
                        <div class="button-filter">
                            <button class="style-button" type="submit">Filtrar</button>
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
                                <th>Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">

                        </tbody>
                    </table>
                </div>
                <div class="quantity-button">
                    <div class="quantity">
                        <label>Seleccione cantidad:</label>
                        <input type="number" value="1" min="1" id="cantidadProducto">
                    </div>
                    <div class="add-product">
                        <button class="style-button-disabled button-add-product" disabled>Agregar producto</button>
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
                                <td>S/. 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
            <section class="preorden-buttons">
                <div class="register">
                    <form method="post" class="form-register-preoden" id="register-preorden">
                        <button class="style-button-disabled generar-preorden-button" id="generar-preorden">Generar PreOrden</button>
                    </form>
                </div>
                <div class="salir">
                    <button class="style-button">Salir</button>
                </div>
            </section>
        </main>
        <script src="../Script/CUS01/TBodyScript.js" type="text/javascript"></script>
        <script src="../Script/buscarClienteJquery.js" type="text/javascript"></script>
        <script src="../Script/CUS01/cargarProductos.js" type="text/javascript"></script>
        <script src="../Script/CUS01/filtroProducto.js" type="text/javascript"></script>
        <script src="../Script/CUS01/addProductListPreorden.js" type="text/javascript"></script>
        <script src="../Script/CUS01/cantidadConfig.js" type="text/javascript"></script>
        <script src="../Script/CUS01/registrarPreorden.js" type="text/javascript"></script>
        <script src="../Script/CUS01/salirBoton.js" type="text/javascript"></script>
        <script src="../Script/CUS01/recargaAdvertencia.js" type="text/javascript"></script>
    </body>
</html>
