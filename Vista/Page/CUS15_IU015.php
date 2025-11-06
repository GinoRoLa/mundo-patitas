<?php
$fecha = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>CUS015 · Evaluar Cotización de Proveedor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../Style/CUS15/CUS15_style.css">
</head>

<body>
  <div class="container">
    <!-- ===== Encabezado ===== -->
    <section class="has-meta">
      <h2>CUS015 · Evaluar Cotización de Proveedor</h2>

      <!-- Meta (actor/rol/fecha/hora) -->
      <div class="row row--meta meta-stack">
        <div class="meta-line">
          <div class="col">
            <label>Nombre:</label>
            <span id="lblActor">—</span>
          </div>
          <div class="col">
            <label>Rol:</label>
            <span id="lblRol">—</span>
          </div>
        </div>
        <div class="meta-line meta-line--sub">
          <div class="col">
            <label>Fecha:</label>
            <input type="date" id="dtpFecha" value="<?= $fecha ?>" disabled />
          </div>
          <div class="col">
            <label>Hora:</label>
            <input type="text" id="hora" readonly />
          </div>
        </div>
      </div>

      <!-- ===== Layout principal: UNA COLUMNA ===== -->
      <div class="layout-col">

        <!-- ========== Sección 1: Requerimientos ========== -->
        <section id="secRequerimientos">
          <h3>Solicitud requerimiento de compra</h3>

          <div class="table-scroll">
            <table class="table" id="tblRequerimientos">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Fecha</th>
                  <th>Cant. ítems</th>
                  <th>Estado</th>
                  <th>Cotizaciones</th>
                  <th>Evaluar</th>
                </tr>
              </thead>
              <tbody id="tbodyRequerimientos">
                <!-- JS cargará las filas aquí -->
              </tbody>
            </table>
          </div>

          <span class="hint">
            🟢 <b>Listo (n)</b>: cotizaciones importadas · 🟡 <b>Detectadas (n)</b>: archivos hallados · ⚪ <b>—</b>: sin cotizaciones
          </span>
        </section>

        <!-- ========== Sección 2: Detalle del requerimiento ========== -->
        <section id="secDetalle">
          <h3>Detalle Solicitud requerimiento compra</h3>
          <div class="msg" id="msgDetalle">Seleccione un requerimiento para ver los detalles</div>

          <div class="table-scroll">
            <table class="table" id="tblDetalleReq">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Descripción</th>
                  <th style="text-align:right;">Cantidad</th>
                  <th>Unidad</th>
                </tr>
              </thead>
              <tbody id="tbodyDetalleReq">
                <!-- JS cargará detalle -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- ========== Sección 3: Cotizaciones generadas ========== -->
        <section id="secCotsGeneradas">
          <h3>Solicitud de cotización generadas</h3>

          <div class="table-scroll">
            <table class="table" id="tblCotsGeneradas">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>RUC</th>
                  <th>Razón Social</th>
                  <th>Dirección</th>
                  <th>Fecha Emisión</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="tbodyCotsGeneradas">
                <!-- JS cargará cotizaciones generadas -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- ========== Sección 4: Cotizaciones recibidas ========== -->
        <section id="secCotsRecibidas">
          <h3>Solicitud de cotización recibidas</h3>

          <div class="table-scroll">
            <table class="table" id="tblCotsRecibidas">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>RUC</th>
                  <th>Razón Social</th>
                  <th>Dirección</th>
                  <th>F. Emisión</th>
                  <th>F. Recepción</th>
                </tr>
              </thead>
              <tbody id="tbodyCotsRecibidas">
                <!-- JS cargará cotizaciones recibidas -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- ========== Sección 5: Evaluación ========== -->
        <section id="secEvaluacion">
          <h3>Evaluación de proveedores</h3>

          <div class="table-scroll">
            <table class="table" id="tblEvaluacion">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Descripción</th>
                  <th style="text-align:right;">Cantidad</th>
                  <th>Asignación</th>
                  <th style="text-align:right;">Costo total</th>
                  <th>Obs</th>
                </tr>
              </thead>
              <tbody id="tbodyEvaluacion">
                <!-- JS cargará resultados de evaluación -->
              </tbody>
            </table>
          </div>

          <div class="groups-summary" id="resumenEvaluacion">
            <b>Resumen:</b> — productos evaluados · — proveedores · Costo total: <b>S/ —</b>
          </div>

          <div class="acciones">
            <button id="btnGenerarOC" class="btn-primary" type="button">Generar Órdenes de Compra</button>
            <button id="btnCancelar" class="btn btn-ghost" type="button">Cancelar</button>
          </div>
        </section>
      </div>
    </section>

    <!-- ===== Modal comparador ===== -->
    <dialog id="modalComparador" class="modal">
      <div class="modal__card">
        <h3 class="modal__title">Detalle de la evaluación</h3>
        <div class="modal__msg">
          <div class="row row--between">
            <div id="cmpProd">Producto: —</div>
            <div id="cmpCant">Cantidad: —</div>
          </div>
        </div>

        <div class="table-scroll" style="max-height: none;">
          <table id="tblComparador" class="table">
            <thead>
              <tr>
                <th>Criterio / Proveedor</th>
                <th id="cmpProvA">Proveedor A</th>
                <th id="cmpProvB">Proveedor B</th>
                <th id="cmpProvC">Proveedor C</th>
                <th>Mejor</th>
              </tr>
            </thead>
            <tbody id="tbodyComparador">
              <!-- JS cargará comparativa -->
            </tbody>
          </table>
        </div>

        <div class="modal__actions">
          <button class="btn btn-ghost" data-close>Cerrar</button>
        </div>
      </div>
    </dialog>

    <!-- ===== Modal Órdenes de Compra ===== -->
    <dialog id="modalOrdenes" class="modal">
      <div class="modal__card">
        <h3 class="modal__title">Confirmar Generación de Órdenes de Compra</h3>

        <div class="modal__msg">Se generaran las órdenes de compra</div>
        <div class="groups-list" id="listaOC">
          <!-- JS cargará las OC generadas -->
        </div>

        <div class="mail-banner">
          <div class="mail-banner-info">
            Las órdenes se enviarán automáticamente por correo electrónico a cada proveedor
          </div>
        </div>

        <div class="modal__actions">
          <button class="btn btn-ghost" data-close>Cancelar</button>
          <button class="btn-primary" id="btnConfirmarOC">✓ Confirmar y Generar</button>
        </div>
      </div>
    </dialog>

    <!-- Modal PROCESANDO -->
<dialog id="dlgProcessing" aria-label="Procesando">
  <div class="proc">
    <span class="spin" aria-hidden="true"></span>
    <div>
      <h3 id="procTitle">Procesando…</h3>
      <p id="procMsg">Por favor, espera un momento.</p>
    </div>
  </div>
</dialog>



    <!-- ===== Toasts ===== -->
    <div id="toastContainer"></div>
  </div>

  <!-- ===== Scripts Modulares ===== -->
  <script src="../Script/CUS15/apiCUS15.js"></script>
  <script src="../Script/CUS15/utils.js"></script>
  <script src="../Script/CUS15/actor.js"></script>
  <script src="../Script/CUS15/solicitudCotizacion.js"></script>
  <script src="../Script/CUS15/requerimiento.js"></script>
  <script src="../Script/CUS15/ordenCompra.js"></script>
  <script src="../Script/CUS15/main.js"></script>
  <script src="../Script/actualizarHora.js"></script>
</body>
</html>
