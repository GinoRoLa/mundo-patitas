<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <link href="../Style/CUS01/StyleTittleGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleInputGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleTittleGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleInputGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleTbodyTable.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS01/StyleButtonGeneral.css" rel="stylesheet" type="text/css"/>
        <link href="../Style/CUS12/CUS12_IU012.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <title>CUS 12 - Generar requerimiento de compra</title>
    </head>
    <body>
        <?php
        include_once '../../Controlador/Negocio.php';
        include_once '../../Controlador/CUS12Negocio.php';
        $obj = new Negocio();
        $obj2 = new CUS12Negocio();
        $trabajador = $obj->buscarTrabajador(50004);
        $reporteInventario = $obj2 ->reporteInvetario();
        $titulo = "IU012 - Generar requerimiento de compra";
        $nombreTrabajador = "$trabajador[4] $trabajador[2] $trabajador[3]";
        $rol = $trabajador[8];
        $parametrosComponenteTitulo = [
            "titulo" => $titulo,
            "trabajador" => $nombreTrabajador,
            "rol" => $rol
        ];
        ?>
        <script>
            window.reporteInventario = <?php echo json_encode($reporteInventario); ?>;
        </script>
        <main class="container main-content">
            <?php
            include "../Componentes/TituloRolResponsableFechaHora.php";
            ?>
            <section class="product-stock-section">
                <div class="product-table product-stock-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Producto</th>
                                <th>Descripción</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>Stock Actual (Unidades)</th>
                                <th>Precio Promedio(S/.)</th>
                                <th>Cantidad a solicitar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">

                        </tbody>
                    </table>
                </div>
            </section>
            <section class="detalleRequerimiento">
                <div class="detail-requirement">
                    <div class="detail">
                        <h2>Total requerimiento:</h2>
                        <input id="total" class="input-style" placeholder="Total Requerimiento" readonly>
                    </div>
                    <div class="detail">
                        <h2>Precio promedio requerimiento:</h2>
                        <input id="precioPromedio" class="input-style" placeholder="Precio promedio" readonly>
                    </div>
                </div>
            </section>
            <section class="botonesCUS">
                <button id="btnGenerarRequerimiento" class="style-button">Generar requerimiento</button>
                <button class="style-button">Cancelar</button>
            </section>
        </main>
        <script src="../Script/CUS12/cargarReporte.js" type="text/javascript"></script>
        <script src="../Script/CUS12/registrarRequerimiento.js" type="text/javascript"></script>
    </body>
</html>
