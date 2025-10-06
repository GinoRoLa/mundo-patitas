<?php

$fecha = date('Y-m-d');
//$distritos = (new DistritoEnvio())->listarActivos(); // por si se usa en validaciones front
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Mundo Patitas - CUS24 Orden de Salida</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- CSS -->
  <!--   <link rel="stylesheet" href="../Style/CUS24/base.css">
  <link rel="stylesheet" href="../Style/CUS24/layout.css">
  <link rel="stylesheet" href="../Style/CUS24/theme-blue.css"> 
  <link rel="stylesheet" href="../Style/CUS24/components.css">-->
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

        <!-- Dos columnas -->
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
      <!-- Pedidos a retirar -->
      <h3>Pedidos a retirar</h3>
      <table id="tblPedidos" class="table">
        <thead>
          <tr>
            <th>Código Orden Pedido</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Distrito</th>
            <th>OSE</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <div id="msgPedidos" class="msg hint">Seleccione solo pedidos de la misma dirección. Si intenta mezclar direcciones, se mostrará el mensaje E1.</div>
      <hr />
      <!-- Detalle de ítems -->
      <div class="row row--between">
        <h3>Detalle de ítems de Pedido</h3>
        <button id="btnLimpiar" class="btn btn-ghost" disabled>Limpiar</button>
      </div>
      <table id="tblItems" class="table">
        <thead>
          <tr>
            <th>OP</th>
            <th>Código</th>
            <th>Descripción</th>
            <th>Cantidad</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <hr />
      <!-- Guía de Remisión (snapshot) -->
      <h3>Guía de Remisión (por dirección activa)</h3>

      <section class="guia">
        <!-- Origen / Destino -->
        <div class="guia-row guia-row--top">
          <div class="field compact">
            <label for="txtOrigen">Origen:</label>
            <input id="txtOrigen" class="input--soft" readonly />
          </div>
          <div class="field compact">
            <label for="txtDireccionActiva">Destino:</label>
            <input id="txtDireccionActiva" class="input--soft" readonly placeholder="Sin dirección activa" />
          </div>
        </div>

        <!-- Datos del transportista -->
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

        <!-- Datos de la unidad -->
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


      <div class="acciones">
        <button id="btnGenerar" class="btn btn-primary" disabled>Generar salida</button>
        <button id="btnSalir" class="btn">Salir</button>
      </div>

      <div id="msg" class="msg"></div>

    </section>
  </main>

  <!-- Modal genérico -->
  <dialog id="appDialog" class="modal">
    <form method="dialog" class="modal__card">
      <h3 id="appDialogTitle" class="modal__title">Confirmación</h3>
      <p id="appDialogMsg" class="modal__msg">Se generará la Orden de Salida y la Guía de Remisión.</p>
      <div class="modal__actions">
        <button id="appDialogCancel" value="cancel" class="btn btn-ghost">Cancelar</button>
        <button id="appDialogOk" value="ok" class="btn btn-primary">Confirmar</button>
      </div>
    </form>
  </dialog>

  <!-- JS -->
  <script src="../Script/CUS24/apiCUS24.js"></script>
  <script src="../Script/CUS24/actor.js"></script>
  <script src="../Script/CUS24/utils.js"></script>
  <script src="../Script/CUS24/asignacion.js"></script>
  <script src="../Script/CUS24/pedidos.js"></script>
  <!-- <script src="../Script/CUS24/guia.js"></script>
  <script src="../Script/CUS24/salida.js"></script> -->
  <script src="../Script/CUS24/main.js"></script>
  <script src="../Script/dialog.js"></script>
  <script src="../Script/actualizarHora.js"></script>
</body>

</html>