<?php
require_once '../../Controlador/CUS29Negocio.php';
date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d');
$hora = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CUS29 - Consolidación de ContraEntrega</title>
    <link rel="stylesheet" href="../Style/CUS29/CUS29_IU29.css">
</head>
<body>
<div class="contenedor">
    <!-- HEADER -->
    <header class="header">
        <div class="header-content">
            <h1>CUS29 - Consolidación de ContraEntrega</h1>
            <div class="meta">
                <span class="meta-item"><strong>Responsable:</strong> Geraldine Anglas</span>
                <span class="meta-item"><strong>Rol:</strong> Repartidor</span>
                <span class="meta-item"><strong>Fecha:</strong> <span id="fechaTexto"><?= $fecha ?></span></span>
                <span class="meta-item"><strong>Hora:</strong> <span id="horaTexto"><?= $hora ?></span></span>
            </div>
        </div>
    </header>

    <!-- BUSCAR ORDEN ASIGNACIÓN -->
    <section class="card card-buscar">
        <h2>Buscar Orden de Asignación</h2>
        <div class="buscar-contenido">
            <div class="input-group">
                <label for="idOrdenAsignacion">ID Orden Asignación:</label>
                <input type="number" id="idOrdenAsignacion" placeholder="Ingrese ID de la orden" class="input-principal">
                <button id="btnBuscarOrden" class="btn btn-primary">Buscar Orden</button>
            </div>
            
            <!-- INFO REPARTIDOR Y ORDEN -->
            <div class="info-orden info-vacia" id="infoOrden">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">🆔 ID Orden:</span>
                        <span class="value" id="txtIdOrden">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">👤 Repartidor:</span>
                        <span class="value" id="txtRepartidor">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">🚗 Vehículo:</span>
                        <span class="value" id="txtVehiculo">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">📋 ID Nota Caja:</span>
                        <span class="value" id="txtNotaCaja">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">📅 Fecha Programada:</span>
                        <span class="value" id="txtFechaProgramada">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">📊 Estado:</span>
                        <span class="value" id="txtEstadoOrden">-</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- LISTA DE PEDIDOS PENDIENTES -->
    <section class="card card-pedidos seccion-deshabilitada" id="seccionPedidos">
        <div class="header-pedidos">
            <h2>Pedidos de la Asignación</h2>
            <div class="progreso-entregas" id="progresoEntregas">
                <span class="progreso-texto">Esperando orden...</span>
            </div>
        </div>
        <p class="instruccion">Seleccione un pedido para registrar su entrega</p>
        <div class="tabla-container">
            <table class="tabla-pedidos">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Dirección</th>
                        <th>Monto Total</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyPedidos">
                    <tr>
                        <td colspan="6" class="td-vacio">
                            <div class="mensaje-vacio">
                                <span class="icono-vacio">📋</span>
                                <p>Busque una orden de asignación para ver los pedidos</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- FORMULARIO DE CONSOLIDACIÓN -->
    <div id="formularioConsolidacion" class="seccion-deshabilitada">
        
        <!-- INFO PEDIDO SELECCIONADO -->
        <section class="card card-pedido-actual">
            <h2>Pedido Seleccionado</h2>
            <div class="pedido-actual-info pedido-vacio" id="pedidoActualInfo">
                <div class="mensaje-vacio">
                    <span class="icono-vacio">📦</span>
                    <p>Seleccione un pedido de la lista para comenzar el registro</p>
                </div>
                <div class="info-grid" id="infoPedidoActual" style="display:none;">
                    <div class="info-item">
                        <span class="label">ID Pedido:</span>
                        <span class="value" id="txtPedidoActual">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">ID Detalle Asignación:</span>
                        <span class="value" id="txtDetalleAsignacion">-</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Monto Esperado:</span>
                        <span class="value monto-esperado">S/ <span id="txtMontoEsperado">0.00</span></span>
                    </div>
                </div>
                <button id="btnCancelarPedido" class="btn btn-secondary" style="display:none;">❌ Cancelar y volver a lista</button>
            </div>
        </section>

        <!-- CONTROL DE VUELTO Y REGISTRO DE PAGO -->
        <div class="grid-dos-columnas">
            <!-- CONTROL DE VUELTO -->
            <section class="card card-vuelto">
                <h2>Control de Vuelto</h2>
                <div id="infoVueltoDisponible" class="info-vuelto">
                    <p class="texto-espera">Cargando información del vuelto...</p>
                </div>
            </section>

            <!-- REGISTRO DE PAGO -->
            <section class="card card-pago">
                <h2>Registro de Pago</h2>
                <div class="pago-contenido">
                    <div class="form-group">
                        <label for="montoRecibido">Monto recibido del cliente:</label>
                        <input type="number" id="montoRecibido" step="0.10" placeholder="0.00" class="input-monto">
                    </div>
                    <button id="btnCalcularVuelto" class="btn btn-secondary">Calcular Vuelto</button>
                    <div class="vuelto-resultado">
                        <span class="label-vuelto">Vuelto a entregar:</span>
                        <span class="valor-vuelto">S/ <span id="txtVuelto">0.00</span></span>
                    </div>
                </div>
            </section>
        </div>

        <!-- DENOMINACIONES RECIBIDAS -->
        <section class="card card-tabla">
            <h2>Denominaciones Recibidas del Cliente</h2>
            <p class="instruccion">Registre el detalle de billetes y monedas recibidos del cliente</p>
            <button id="btnAddDenomRecibida" class="btn btn-add">➕ Agregar Denominación</button>
            <div class="tabla-container">
                <table id="tablaDenomRecibida" class="tabla-denominaciones">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Denominación (S/)</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <!-- DENOMINACIONES DE VUELTO -->
        <section class="card card-tabla">
            <h2>Denominaciones de Vuelto Entregado</h2>
            <p class="instruccion">Registre el detalle de billetes y monedas que entregó como vuelto</p>
            <button id="btnAddDenomVuelto" class="btn btn-add">➕ Agregar Denominación</button>
            <div class="tabla-container">
                <table id="tablaDenomVuelto" class="tabla-denominaciones">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Denominación (S/)</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <!-- INCIDENCIAS -->
        <section class="card card-incidencias">
            <h2>Registro de Incidencias</h2>
            <p class="instruccion">Solo registre si hubo algún problema durante la entrega</p>
            <div class="incidencias-contenido">
                <div class="form-group">
                    <label for="tipoIncidencia">Tipo de incidencia:</label>
                    <select id="tipoIncidencia" class="input-select">
                        <option value="">-- Seleccione tipo de incidencia --</option>
                        <option value="CLIENTE_NO_ESTABA">Cliente no estaba en domicilio</option>
                        <option value="DIRECCION_INCORRECTA">Dirección incorrecta o no encontrada</option>
                        <option value="CLIENTE_SIN_DINERO">Cliente no tiene dinero</option>
                        <option value="CLIENTE_RECHAZA">Cliente rechaza el pedido</option>
                        <option value="PRODUCTO_DAÑADO">Producto llegó dañado</option>
                        <option value="OTRO">Otro motivo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="descIncidencia">Descripción detallada:</label>
                    <textarea id="descIncidencia" rows="4" placeholder="Describa lo sucedido..." class="input-textarea"></textarea>
                </div>
                <button id="btnRegistrarIncidencia" class="btn btn-warning">⚠️ Marcar Incidencia</button>
                <div id="estadoIncidencia" class="estado-incidencia" style="display:none;"></div>
            </div>
        </section>

        <!-- BOTÓN FINALIZAR -->
        <div class="card-finalizar">
            <button id="btnFinalizar" class="btn-finalizar">
                <span>✅ Finalizar Consolidación de Este Pedido</span>
            </button>
        </div>
    </div>

</div>

<script src="../Script/CUS29/CUS29.js"></script>
</body>
</html>