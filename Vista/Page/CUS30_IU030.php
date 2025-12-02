<?php
$fecha = date('Y-m-d');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mundo Patitas – CUS30 Recaudación Delivery</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../Style/CUS30/CUS30_style.css">
  <link rel="stylesheet" href="../Style/CUS30/toast.css">
</head>

<body>
<main class="container">
  <!-- ========================================================= -->
  <!-- BLOQUE META / ACTOR -->
  <!-- ========================================================= -->
  <section class="has-meta">
    <div class="row row--meta meta-stack">
      <div class="meta-line">
        <div class="col">
          <label>Nombre:</label>
          <span id="lblActor"></span>
        </div>
        <div class="col">
          <label>Rol:</label>
          <span id="lblRol"></span>
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

    <h2>CUS30 – Recaudación de Delivery</h2>

    <!-- ========================================================= -->
    <!-- BLOQUE A: Buscar asignaciones pendientes -->
    <!-- ========================================================= -->
    <hr />
    <h3>Buscar asignaciones de delivery</h3>

    <div class="grid grid--busqueda">
      <label>DNI Repartidor:</label>
      <div class="input-group">
        <input id="txtDniRepartidor" maxlength="8" inputmode="numeric" placeholder="Ej: 22334455" />
        <button id="btnBuscarAsignaciones" type="button">Buscar</button>
      </div>
    </div>

    <div id="msgAsignaciones" class="msg"></div>

    <table id="tblAsignaciones" class="table mt-16">
      <thead>
        <tr>
          <th>ID Asignación</th>
          <th>Fecha</th>
          <th>Repartidor</th>
          <th>Fondo (S/)</th>
          <th>Estado Nota Caja</th>
          <th>Estado Ruta</th>
          <th>Ver</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <!-- ========================================================= -->
    <!-- BLOQUE B: Cabecera recaudación -->
    <!-- ========================================================= -->
    <hr />
    <h3>Datos de la Recaudación</h3>

    <div id="panelCabecera" class="cabecera" hidden>
      <div class="row grid-3">
        <div>
          <label>ID Asignación</label>
          <input id="txtCabIdAsignacion" readonly />
        </div>
        <div>
          <label>ID Nota Caja</label>
          <input id="txtCabIdNotaCaja" readonly />
        </div>
        <div>
          <label>Fondo Retirado (S/)</label>
          <input id="txtCabFondo" readonly />
        </div>
      </div>

      <div class="row grid-3 mt-8">
        <div>
          <label>Repartidor</label>
          <input id="txtCabRepartidor" readonly />
        </div>
        <div>
          <label>Estado Nota</label>
          <input id="txtCabEstadoNota" readonly />
        </div>
        <div>
          <label>Estado Ruta</label>
          <input id="txtCabEstadoRuta" readonly />
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- BLOQUE C: Detalle de pedidos -->
    <!-- ========================================================= -->
    <hr />
    <h3>Pedidos Delivery Contraentrega</h3>

    <table id="tblPedidos" class="table">
      <thead>
        <tr>
          <th>ID Pedido</th>
          <th>Cliente</th>
          <th>Monto Pedido (S/)</th>
          <th>Vuelto Caja Asignado (S/)</th>
          <th>Esperado Retorno (S/)</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <div id="msgPedidos" class="msg"></div>

    <!-- ========================================================= -->
    <!-- BLOQUE D: Resumen y cierre -->
    <!-- ========================================================= -->
    <hr />
    <h3>Resumen</h3>

    <div class="grid grid-4 resumen">
  <div>
    <label>Total Pedido (S/)</label>
    <input id="txtResVentas" readonly />
  </div>
  <div>
    <label>Total Vuelto (S/)</label>
    <input id="txtResVuelto" readonly />
  </div>
  <div>
    <label>Monto Esperado Retorno (S/)</label>
    <input id="txtResEsperado" readonly />
  </div>
  <div>
    <label for="txtResEfectivo">Efectivo Contado (S/)</label>
    <input id="txtResEfectivo" inputmode="decimal" disabled />

    <!-- NUEVO: checkbox para habilitar edición -->
    <label class="chk-inline" style="display:block; margin-top:4px; font-size:0.85rem;">
      <input type="checkbox" id="chkEditarEfectivo" />
      Editar efectivo
    </label>
  </div>
</div>


    <div class="grid grid-2 mt-16">
      <div>
        <label>Diferencia</label>
        <input id="txtResDiferencia" readonly />
      </div>
      <div class="acciones">
        <button id="btnCerrarRecaudacion" disabled>Cerrar recaudación</button>
        <button id="btnSalir" class="btn btn-ghost" type="button">Salir</button>
      </div>
    </div>

    <div id="msgCierre" class="msg"></div>

  </section>
</main>

<!-- ========================================================= -->
<!-- MODAL general de mensajes -->
<!-- ========================================================= -->
<dialog id="dlgMsg" class="modal">
  <form method="dialog" class="modal__card">
    <h3 id="dlgMsgTitle" class="modal__title">Mensaje</h3>
    <p id="dlgMsgBody" class="modal__msg"></p>
    <div class="modal__actions">
      <button value="ok" class="btn btn-primary">Aceptar</button>
    </div>
  </form>
</dialog>

<!-- MODAL: Confirmar cierre de recaudación -->
<dialog id="dlgConfirmRecaudacion" class="modal">
  <form method="dialog" class="modal__card">
    <h3 class="modal__title">Confirmar cierre de recaudación</h3>
    <p id="dlgConfBody" class="modal__msg"></p>
    <div class="modal__actions">
      <button id="btnConfirmRecaudacionCancel" class="btn" value="cancel">
        Cancelar
      </button>
      <button id="btnConfirmRecaudacionOk" class="btn btn-primary" value="ok">
        Confirmar
      </button>
    </div>
  </form>
</dialog>


<!-- ========================================================= -->
<!-- SCRIPTS -->
<!-- ========================================================= -->
<script src="../Script/CUS30/api30.js"></script>
<script src="../Script/CUS30/utils30.js"></script>
<script src="../Script/CUS30/actor30.js"></script>
<script src="../Script/CUS30/asignaciones30.js"></script>
<script src="../Script/CUS30/pedidos30.js"></script>
<script src="../Script/CUS30/resumen30.js"></script>
<script src="../Script/CUS30/dialog30.js"></script>
<script src="../Script/CUS30/main30.js"></script>
<script src="../Script/actualizarHora.js"></script>

</body>
</html>
