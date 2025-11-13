<?php
require_once '../../Controlador/CUS13Negocio.php';
date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d');
$hora = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IU013 – Evaluar Solicitud de Requerimiento</title>
    <link rel="stylesheet" href="../Style/CUS13/CUS13_IU013.css">
</head>
<body>
<div class="container">
    <header class="header">
        <div class="header-title">
            <h1>IU013 – Evaluar solicitud de requerimiento</h1>
        </div>
        <div class="meta">
            <div class="meta-item"><strong>Responsable:</strong> Geraldine Anglas</div>
            <div class="meta-item"><strong>Rol:</strong> Analista de Compra</div>
            <div class="meta-item"><strong>Fecha:</strong> <span id="fechaTexto"><?= $fecha ?></span></div>
            <div class="meta-item"><strong>Hora:</strong> <span id="horaTexto"><?= $hora ?></span></div>
        </div>
    </header>

    <main class="main-grid">
        <!-- Partida (financiamiento) -->
        <section class="card card-partida">
            <div class="card-header">
                <h2>Partida periodo</h2>
            </div>
            <div class="card-body">
                
                <div class="form-group">
                    <label>Información de la partida:</label>
                    <div id="infoPartidaBox" class="info-box">
                        <div class="info-row">
                            <span class="info-label">Descripción:</span>
                            <span id="partidaDesc" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Monto periodo:</span>
                            <span id="partidaMonto" class="info-value">S/ 0.00</span>
                        </div>
                        <div class="info-row">
                            <span id="saldoAnteriorLabel" class="info-label">Saldo anterior:</span>
                            <span id="saldoAnteriorValor" class="info-value">S/ 0.00</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Financiamiento total:</span>
                            <span id="partidaSaldo" class="info-value text-success">S/ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solicitudes -->
        <section class="card">
            <div class="card-header">
                <h2>Solicitudes pendientes</h2>
            </div>
            <div class="card-body">
                <div class="table-wrap">
                    <table id="tablaSolicitudes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Precio Prom.</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="loading">Cargando solicitudes...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Detalle de la solicitud -->
        <section class="card">
            <div class="card-header">
                <h2>Detalle solicitud <span id="idSolicitudActual"></span></h2>
            </div>
            <div class="card-body">
                <div id="detalleVacio" class="empty-state">
                    <p>Seleccione una solicitud para ver su detalle</p>
                </div>
                <div id="detalleContenido" style="display:none;">
                    <div class="table-wrap">
                        <table id="tablaDetalleSolicitud">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3"><strong>TOTAL SOLICITADO:</strong></td>
                                    <td><strong id="totalSolicitado">S/ 0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="criterio-box">
                        <label><strong>Seleccionar criterio de evaluación:</strong></label><br>
                        <label><input type="radio" name="criterio" value="Precio" checked> 💰 Precio (menor a mayor)</label><br>
                        <label><input type="radio" name="criterio" value="Rotacion"> 📦 Rotación (mayor cantidad)</label><br>
                        <label><input type="radio" name="criterio" value="Proporcionalidad"> ⚖️ Proporcionalidad (distribución equitativa)</label>
                    </div>

                    <div class="actions">
                        <button id="btnEvaluar" class="btn btn-primary">
                            <span class="btn-text">Evaluar</span>
                            <span class="btn-loading" style="display:none;">⏳ Evaluando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Resultado / evaluacion -->
        <section class="card card-resultado">
            <div class="card-header">
                <h2>Resultado de evaluación</h2>
            </div>
            <div class="card-body">
                <div id="resultadoVacio" class="empty-state">
                    <p>Los resultados aparecerán aquí después de evaluar</p>
                </div>
                <div id="resultadoContenido" style="display:none;">
                    <div id="resultadoResumen" class="resultado-resumen"></div>
                    <div class="table-wrap">
                        <table id="tablaResultado">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant. Sol.</th>
                                    <th>Cant. Aprob.</th>
                                    <th>Precio</th>
                                    <th>Monto Aprob.</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="accionesEvaluacion" style="display:none; text-align:right; margin-top:10px;">
                        <button id="btnRegistrar" class="btn btn-primary">Registrar Evaluación</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <section class="card card-evaluadas">
            <div class="card-header">
                <h2>Solicitudes Evaluadas</h2>
            </div>
            <div class="card-body">
                <div class="table-wrap">
                    <table id="tablaEvaluadas">
                        <thead>
                        <tr>
                            <th>ID Evaluación</th>
                            <th>ID Requerimiento</th>
                            <th>Criterio</th>
                            <th>Monto Solicitado</th>
                            <th>Monto Aprobado</th>
                            <th>Saldo Después</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </section>
</div>



<!-- Modal de confirmación -->
<div id="modalConfirm" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>⚠️ Confirmar Evaluación</h3>
        <p id="modalMensaje"></p>
        <div class="modal-actions">
            <button id="btnConfirmarSi" class="btn btn-primary">Sí, Evaluar</button>
            <button id="btnConfirmarNo" class="btn btn-secondary">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modalDetalle" class="modal">
  <div class="modal-content">
    <span id="cerrarModal" class="cerrar">&times;</span>
    <div id="contenidoDetalle"></div>
  </div>
</div>


<script src="../Script/CUS13/evaluacion.js"></script>
</body>
</html>
