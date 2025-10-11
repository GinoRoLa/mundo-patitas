<?php
$fecha = date('Y-m-d');
// $distritos = (new DistritoEnvio())->listarActivos();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mundo Patitas - CUS24 Orden de Salida</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- CSS -->
  <link rel="stylesheet" href="../Style/CUS24/components.css">
  <link rel="stylesheet" href="../Style/CUS24/CUS24_OrdenSalida.css">
</head>
<body>
  <main class="container">

    <!-- Meta del actor / fecha / hora -->
    <section class="has-meta">
      <div class="row row--meta meta-stack">
        <div class="meta-line">
          <div class="col">
            <label>Nombre:</label>
            <span id="lblActor"><?= htmlspecialchars($actor['nombre'] ?? '') ?></span>
          </div>
          <div class="col">
            <label>Rol:</label>
            <span id="lblRol"><?= htmlspecialchars($actor['rol'] ?? '') ?></span>
          </div>
        </div>
        <div class="meta-line meta-line--sub">
          <div class="col">
            <label>Fecha:</label>
            <input type="date" id="dtpFecha" value="<?= $fecha ?? '' ?>" disabled />
          </div>
          <div class="col">
            <label>Hora:</label>
            <input type="text" id="hora" readonly />
          </div>
        </div>
      </div>

      <h2>CUS24 – Generar Orden de Salida Entrega</h2>
      <hr />

      <!-- Orden de asignación -->
      <h3>Orden de Asignación</h3>
      <fieldset class="fieldset-asignacion">
        <div class="form-row form-row--search">
          <label for="txtAsignacion">Orden de asignación:</label>
          <div class="input-group">
            <input id="txtAsignacion" placeholder="Id Asignación (ej. 80001)" />
            <button id="btnBuscar" type="button">Buscar</button>
          </div>
        </div>
        <div id="msgAsignacion" class="msg"></div>

        <legend>Datos del Repartidor</legend>
        <div class="cols-2">
          <div class="col">
            <div class="field"><label>Nombre</label><input id="repNombre" class="input--soft" readonly /></div>
            <div class="field"><label>Apellido paterno</label><input id="repApePat" class="input--soft" readonly /></div>
            <div class="field"><label>Apellido materno</label><input id="repApeMat" class="input--soft" readonly /></div>
          </div>
          <div class="col">
            <div class="field"><label>Teléfono</label><input id="repTel" class="input--soft" readonly /></div>
            <div class="field"><label>Email</label><input id="repEmail" class="input--soft" readonly /></div>
          </div>
        </div>
      </fieldset>

      <hr />

      <!-- Pedidos a despachar (auto-incluidos) -->
      <h3>Pedidos a despachar</h3>
      <div id="msgPedidosAuto" class="msg hint">
        Todas las Órdenes de Pedido en estado <b>Pagado</b> se consideran automáticamente.
      </div>
      <div class="table-scroll">
      <table id="tblPedidos" class="table">
        <thead>
          <tr>
            <th>Código Orden Pedido</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Distrito</th>
            <th>OSE</th>
            <th>Estado</th>
            <th>Incluida</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      </div>
      <div id="msgPedidos" class="msg hint"></div>

      <hr />

      <h3>Origen</h3>
      <div class="field compact field--full" style="max-width: 720px;">
        <label for="txtOrigen">Origen:</label>
        <input id="txtOrigen" class="input--soft" readonly />
      </div>

      <hr />
      
      <!-- NUEVO: Guías a generar (pre-visualización por destino) -->
      <h3>Guías a generar (pre-visualización)</h3>
      <div id="gruposResumen" class="groups-summary">
        <!-- Ej.: Se detectaron N grupos de destino. -->
      </div>
      <div class="table-scroll">
      <div id="gruposLista" class="groups-list">
        <article class="group-card">
          <header class="group-head">
            <div class="destino">
              <div><b>DNI:</b> 12345678</div>
              <div><b>Nombre:</b> Juan Pérez</div>
              <div><b>Dirección:</b> Av. Siempre Viva 742</div>
              <div><b>Distrito:</b> Lince</div>
            </div>
            <div class="resumen">
              <span>#OP: 3</span>
              <span>#Productos: 12</span>
              <span>Unidades: 24</span>
            </div>
            <div class="estado"><span class="badge ok">Listo</span></div>
            <button class="btn btn-ghost btn-sm group-toggle" type="button">Ver detalle</button>
          </header>
          <section class="group-body" hidden>
            <h4>Consolidado de productos</h4>
            <table class="table table-compact">
              <thead>
                <tr><th>Código</th><th>Descripción</th><th>UM</th><th>Cantidad</th></tr>
              </thead>
              <tbody><!-- filas consolidadas --></tbody>
            </table>
            <div class="ops-list">
              <b>Órdenes incluidas:</b> <span>1001, 1002, 1003</span>
            </div>
            <div class="warnings">
              <!-- advertencias opcionales -->
            </div>
          </section>
        </article>
      </div>
      </div>

      <hr />

      <!-- Datos del transportista y unidad (snapshot informativo) -->
      <h3>Transportista y Unidad</h3>
      <section class="guia">
        <fieldset class="guia-box">
          <legend>Datos del transportista</legend>
          <div class="guia-grid-trans">
            <div class="field compact">
              <label for="guiaDni">DNI</label>
              <input id="guiaDni" class="input--soft" readonly />
            </div>
            <div class="field compact">
              <label for="guiaLic">Licencia</label>
              <input id="guiaLic" class="input--soft" readonly />
            </div>
            <div class="field compact field--full">
              <label for="guiaConductor">Conductor</label>
              <input id="guiaConductor" class="input--soft" readonly />
            </div>
          </div>
        </fieldset>

        <fieldset class="guia-box">
          <legend>Datos de la unidad</legend>
          <div class="guia-grid-unid">
            <div class="field compact">
              <label for="vehMarca">Marca</label>
              <input id="vehMarca" class="input--soft" readonly />
            </div>
            <div class="field compact">
              <label for="vehPlaca">Placa</label>
              <input id="vehPlaca" class="input--soft" readonly />
            </div>
            <div class="field compact">
              <label for="vehModelo">Modelo</label>
              <input id="vehModelo" class="input--soft" readonly />
            </div>
          </div>
        </fieldset>
      </section>

      <!-- Banner de correo -->
      <div id="correoBanner" class="mail-banner">
        Se enviará un correo a <b id="repEmailView">—</b> con las guías generadas.
        <!-- Opcional: <label class="copy-me"><input type="checkbox" id="mailCCMe" checked> Enviarme copia</label> -->
      </div>

      <div class="acciones">
        <button id="btnGenerar" class="btn btn-primary" disabled>Generar guías</button>
        <button id="btnSalir" class="btn">Salir</button>
      </div>

      <div id="msg" class="msg"></div>
    </section>
  </main>

  <!-- Modal genérico (extensible para listar guías resultantes) -->
  <dialog id="appDialog" class="modal">
    <form method="dialog" class="modal__card">
      <h3 id="appDialogTitle" class="modal__title">Resultado de generación</h3>
      <div id="appDialogBody" class="modal__body">
        <p id="appDialogMsg" class="modal__msg">
          Se generarán Guías de Remisión agrupadas por destino.
        </p>
        <ul id="modalGuiasList" class="guides-list"><!-- li con # de guía/destino --></ul>
        <div id="modalMailInfo" class="mail-info"><!-- Correo enviado a: ... --></div>
      </div>
      <div class="modal__actions">
        <button id="appDialogCancel" value="cancel" class="btn btn-ghost">Cancelar</button>
        <button id="appDialogOk" value="ok" class="btn btn-primary">Entendido</button>
      </div>
    </form>
  </dialog>

  <!-- JS -->
  <script src="../Script/CUS24/apiCUS24.js"></script>
  <script src="../Script/CUS24/actor.js"></script>
  <script src="../Script/CUS24/utils.js"></script>
  <!-- anchor/itemsProductos ya no se usan para selección manual; los dejamos fuera en esta versión de UI -->
  <script src="../Script/CUS24/asignacion.js"></script>
  <script src="../Script/CUS24/pedidos.js"></script>
  <script src="../Script/CUS24/salida.js"></script>
  <script src="../Script/CUS24/main.js"></script>
  <script src="../Script/dialog.js"></script>
  <script src="../Script/actualizarHora.js"></script>
</body>
</html>
