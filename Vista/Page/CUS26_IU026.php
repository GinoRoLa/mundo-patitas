<?php 
require_once '../../Controlador/CUS26Negocio.php';
date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d');

$negocio = new CUS26Negocio();
$consol = $negocio->listarNoEntregados();
$pedidos = $negocio->obtenerPedidosNoEntregados();

if(!$consol){
    $consol=[];
}
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

    <div class="header-info">
        <div>Responsable: <span id="responsable">Anglas Sotelo Geraldine</span></div>
        <div>Rol: <span id="rol">Supervisor</span></div>
        <div>Fecha: <span id="fecha">2025-10-17</span></div>
        <div>Hora: <span id="hora">10:25:36</span></div>
    </div>

    <div class="tabla-contenedor">
        <table id="tablaConsolidacion">
            <thead>
                <tr>
                    <th>ID Consolidación</th>
                    <th>ID Pedido</th>
                    <th>ID Cliente</th>
                    <th>Nombre Cliente</th>
                    <th>Observaciones</th>
                    <th>Fecha Consolidación</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pedidos as $fila): ?>
                <?php $obs = $fila['Observaciones']; ?>
                <tr class='fila'
                    data-id='<?php echo $fila['ID_Consolidacion']; ?>'
                    data-idpedido='<?php echo $fila['Id_OrdenPedido']; ?>'
                    data-idcliente='<?php echo $fila['Id_Cliente']; ?>'
                    data-nombrecliente='<?php echo htmlspecialchars($fila['NombreCliente'], ENT_QUOTES, 'UTF-8'); ?>'
                    data-observaciones="<?php echo htmlspecialchars($fila['Observaciones'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-fecha='<?php echo $fila['Fecha']; ?>'
                    data-estado='<?php echo $fila['Estado']; ?>'
                    data-idose='<?php echo isset($fila['Id_OSE']) ? $fila['Id_OSE']: ''; ?>'
                    data-feccreacion='<?php echo $fila['FecCreacionOSE']; ?>'
                    data-estadoose='<?php echo $fila['EstadoOSE'];?>'>
                    
                    <td><?php echo $fila['ID_Consolidacion']; ?></td>
                    <td><?php echo $fila['Id_OrdenPedido']; ?></td>
                    <td><?php echo $fila['Id_Cliente']; ?></td>
                    <td><?php echo htmlspecialchars($fila['NombreCliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($fila['Observaciones'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $fila['Fecha']; ?></td>
                    <td><?php echo $fila['Estado']; ?></td>
                    <td>
                        <input type="radio" name="decision_<?php echo $fila['ID_Consolidacion']; ?>" class="radio-reprogramar" value="Reprogramación"> Reprogramar
                        <input type="radio" name="decision_<?php echo $fila['ID_Consolidacion']; ?>" class="radio-devolucion" value="Devolución"> Devolución
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
        <label>Observaciones:</label>
        <textarea id="observacionesRep" readonly></textarea>
        <label>Fecha Reprogramación:</label>
        <input type="date" id="fechaConsolRep" value="<?php echo $fecha;?>" readonly>
        <label>Estado:</label>
        <input type="text" id="estadoRep" readonly>
        <button type="button" id="btnRegistrarReprogramacion">Registrar Reprogramación</button>
    </div>

    <h3>Detalle de Pedido (t02)</h3>
            <div>
                <label>Id Pedido:</label>
                <input type="text" id="detalleIdPedido" readonly>
                <label>Id Cliente:</label>
                <input type="text" id="detalleIdCliente" readonly>
                <label>Fecha:</label>
                <input type="date" id="detalleFecha">
                <label>Estado:</label>
                <input type="text" id="detalleEstado" readonly>
            </div>

            <h3>Detalle de Orden de Servicio de Entrega (t59)</h3>
            <div>
                <label>Id OSE:</label>
                <input type="text" id="detalleIdOSE" readonly>
                <label>Id Pedido:</label>
                <input type="text" id="detalleIdPedidoOSE" readonly>
                <label>FecCreacion:</label>
                <input type="date" id="detalleFecCreacion">
                <label>Estado:</label>
                <input type="text" id="detalleEstadoOSE" readonly>
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
                    <th>Decisión</th>
                    <th>Motivo</th>
                    <th>Fecha Reprogramación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="bodyTablaGestion"></tbody>
        </table>
    </div>

    

</div>

<script src="../Script/CUS26/registrarReprogramacion.js"></script>
<script src="../Script/CUS26/listarGestion.js"></script>

</body>
</html>


