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
        <link href="../Style/CUS31/CUS31_IU031.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="../Script/CUS31/calendarConfig.js" type="text/javascript"></script>
        <title>CUS 31 - Reprogramar Orden de Pedido</title>
    </head>
    <body>
        <?php
        include_once '../../Controlador/Negocio.php';
        include_once '../../Controlador/CUS31Negocio.php';
        $obj = new Negocio();
        $obj3 = new CUS31Negocio();
        $listaZonas = $obj3->listaZonas();
        $trabajador = $obj->buscarTrabajador(50004);
        $listaPedidos = $obj3->listaOrdenesPedido();
        $listaRV = $obj3->listaRepartidores();
        $direccionAlmacen = $obj3->direccionAlmacen();
        $titulo = "IU031 - Reprogramar Orden de Pedido";
        $nombreTrabajador = "$trabajador[4] $trabajador[2] $trabajador[3]";
        $rol = $trabajador[8];
        $parametrosComponenteTitulo = [
            "titulo" => $titulo,
            "trabajador" => $nombreTrabajador,
            "rol" => $rol
        ];
        ?>
        <script>
            window.listaOriginales = <?php echo json_encode($listaPedidos); ?>;
            window.vrOriginales = <?php echo json_encode($listaRV); ?>;
            window.direcAlmacen = <?php echo json_encode($direccionAlmacen); ?>;
        </script>
        <main class="container main-content">
            <?php
                include "../Componentes/TituloRolResponsableFechaHora.php";
            ?>
            <section class="servicioentrega">
                <form class="filtroOSE">
                    <div class="comboZonas">
                        <label>Seleccione zona:</label>
                        <select id="zonasReparto" name="zonas-combo" class="zonas">
                            <option value="0">Seleccionar</option>
                            <?php foreach ($listaZonas as $lz): ?>
                                <option value="<?= trim(htmlspecialchars($lz['Id_Zona'])) ?>">
                                    <?= htmlspecialchars($lz['DescZona']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="botonesFiltro">
                        <button id="btnFiltrar" class="style-button">Filtrar</button>
                        <button id="btnVerTodo" class="style-button">Ver todo</button>
                    </div>
                </form>
                <div class="product-table filter-order-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código OP</th>
                                <th>Distrito</th>
                                <th>Zona</th>
                                <th>Peso (kg)</th>
                                <th>Volumen (m3)</th>
                                <th>Dias restantes</th>
                                <th>Numero r.</th>
                                <th>Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">

                        </tbody>
                    </table>
                </div>
            </section>
            <section class="repartidorVehiculo">
                <form class="verDisponibilidad">
                    <div class="buscarRepartidor">
                        <label>Ingrese código de repartidor:</label>
                        <input class="input-style" placeholder="Ingrese código">
                    </div>
                    <div class="botonBuscar">
                        <button class="style-button">Buscar</button>
                        <button class="style-button">Ver todo</button>
                    </div>
                </form>
                <div class="product-table filter-repartidor-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código repartidor</th>
                                <th>Placa</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Cargar útil (kg)</th>
                                <th>Capacidad (m3)</th>
                                <th>Disponibilidad</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-rv">

                        </tbody>
                    </table>
                </div>
                <div id="calendar"></div>
            </section>
            <section class="detalleSeleccion">
                <div class="repartidor-information">
                    <div class="repartidor-details">
                        <h2>Código repartidor:</h2>
                        <input type="text" readonly id="nombreCliente">
                    </div>
                    <div class="repartidor-details">
                        <h2>Placa:</h2>
                        <input type="text" readonly id="telefonoCliente">
                    </div>
                    <div class="repartidor-details">
                        <h2>Marca:</h2>
                        <input type="text" readonly id="apepatCliente">
                    </div>
                    <div class="repartidor-details">
                        <h2>Modelo:</h2>
                        <input type="text" readonly id="apematCliente">
                    </div>
                    <div class="repartidor-details">
                        <h2>Fecha seleccionada:</h2>
                        <input type="text" readonly id="emailCliente">
                    </div>
                    <div class="repartidor-details">
                        <button class="style-button button-change">Cambiar repartidor</button>
                    </div>
                </div>
                <div id="resumenSeleccion" class="resumen-ose"></div>
                <div class="product-table details-repartidor-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Código OP</th>
                                <th>Distrito</th>
                                <th>Zona</th>
                                <th>Peso (kg)</th>
                                <th>Volumen (m3)</th>
                                <th>Dias restantes</th>
                                <th>Numero r.</th>
                                <th>Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="table-body-rv-selectd">

                        </tbody>
                    </table>
                </div>
                <div class="rutaTrazada">
                    <h2>Ruta trazada:</h2>
                    <textarea class="rutaTxt" id="ruta" name="ruta" rows="5" cols="10" readonly style="resize: none;"></textarea>
                </div>
            </section>
            <section class="botonesCUS">
                <button id="btnGenerarOrden" class="style-button-disabled" disabled>Generar orden de asignación</button>
                <button class="style-button">Cancelar</button>
            </section>
        </main>
        <script src="../Script/CUS31/cargarOPedido.js" type="text/javascript"></script>
        <script src="../Script/CUS31/cargarRepartidores.js" type="text/javascript"></script>
    </body>
</html>
