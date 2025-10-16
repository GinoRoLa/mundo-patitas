<?php 
require_once '../../Controlador/CUS26Negocio.php';
date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d');

$negocio = new CUS26Negocio();
$consol = $negocio->listarNoEntregadas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CUS026 – Gestionar Pedidos No Entregados</title>
    <link rel="stylesheet" href="../Style/CUS26/CUS26_IU026.css">
</head>
<body>
<div class="contenedor">
    <h2>CUS026 – Gestionar Pedidos No Entregados</h2>

    <div class="encabezado">
        <label>Responsable:</label>
        <input type="text" value="Geraldine Anglas" readonly>
        <label>Fecha:</label>
        <input type="text" value="<?php echo $fecha; ?>" readonly>
    </div>

    <div class="tabla-contenedor">
        <table id="tablaConsolidacion">
            <thead>
                <tr>
                    <th>ID Consolidación</th>
                    <th>ID Pedido</th>
                    <th>ID Cliente</th>
                    <th>Nombre Cliente</th>
                    <th>Fecha Consolidación</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($consol as $fila): ?>
                <tr class='fila' 
                    data-id='<?php echo $fila['ID_Consolidacion']; ?>' 
                    data-idpedido='<?php echo $fila['Id_OrdenPedido']; ?>' 
                    data-idcliente='<?php echo $fila['Id_Cliente']; ?>' 
                    data-nombrecliente='<?php echo $fila['NombreCliente']; ?>' 
                    data-fecha='<?php echo $fila['Fecha']; ?>'
                    data-estado='<?php echo $fila['Estado']; ?>'>
                    <td><?php echo $fila['ID_Consolidacion']; ?></td>
                    <td><?php echo $fila['Id_OrdenPedido']; ?></td>
                    <td><?php echo $fila['Id_Cliente']; ?></td>
                    <td><?php echo $fila['NombreCliente']; ?></td>
                    <td><?php echo $fila['Fecha']; ?></td>
                    <td><?php echo $fila['Estado']; ?></td>
                    <td>
                        <input type="radio" name="seleccionPedido" class="radio-seleccionar">
                    </td>
                </tr>
                <?php endforeach; ?>

            </tbody>

        </table>
    </div>

    <!-- Formulario Reprogramación siempre visible -->
    <div id="formReprogramacion" class="formulario">
        <h4>Reprogramación de Pedido</h4>
        <input type="hidden" id="idConsolidacionRep">
        <label>ID Pedido:</label>
        <input type="text" id="idPedidoRep" readonly>
        <label>ID Cliente:</label>
        <input type="text" id="idClienteRep" readonly>
        <label>Nombre del Cliente:</label>
        <input type="text" id="nombreClienteRep" readonly>
        <label>Fecha Consolidación:</label>
        <input type="date" id="fechaConsolRep" readonly>
        <label>Estado:</label>
        <input type="text" id="estadoRep" readonly>
        <button type="button" id="btnRegistrarReprogramacion">Registrar Reprogramación</button>
    </div>

    <h3>Gestión de Pedidos Reprogramados</h3>
    <div class="tabla-contenedor">
        <table id="tablaGestionReprogramados">
            <thead>
                <tr>
                    <th>ID Gestión</th>
                    <th>ID Consolidación</th>
                    <th>ID Pedido</th>
                    <th>ID Cliente</th>
                    <th>Nombre Cliente</th>
                    <th>Fecha Reprogramación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="bodyTablaGestion"></tbody>
        </table>
    </div>

    <h3>Pedidos Cambiados</h3>
    <div class="tabla-contenedor">
        <table id="tablaPedidos">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>ID Cliente</th>
                    <th>Fecha Pedido</th>
                    <th>Estado Pedido</th>
                </tr>
            </thead>
            <tbody id="bodyTablaPedidos"></tbody>
        </table>
    </div>

    <h3>Ordenes de Servicio de Entrega</h3>
    <div class="tabla-contenedor">
        <table id="tablaOSE">
            <thead>
                <tr>
                    <th>ID OSE</th>
                    <th>ID Pedido</th>
                    <th>Estado OSE</th>
                </tr>
            </thead>
            <tbody id="bodyTablaOSE"></tbody>
        </table>
    </div>

</div>

<script src="../Script/CUS26/registrarReprogramacion.js"></script>
<script src="../Script/CUS26/listarGestion.js"></script>

</body>
</html>


