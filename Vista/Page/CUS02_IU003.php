<?php
include_once '../../Controlador/Conexion.php';
include_once '../../Modelo/Trabajador.php';
include_once '../../Modelo/DistritoEnvio.php';
$trabajadorRow = (new Trabajador())->buscarPorDni('22222222');

$actor = [
  'nombre' => $trabajadorRow
    ? trim(($trabajadorRow['des_nombreTrabajador'] ?? '') . ' ' . ($trabajadorRow['des_apepatTrabajador'] ?? '') . ' ' . ($trabajadorRow['des_apematTrabajador'] ?? ''))
    : '(desconocido)',
  'rol' => $trabajadorRow['cargo'] ?? '(Desconocido)'
];
$fecha = date('Y-m-d');

$t77 = (new DistritoEnvio())->listarActivos();


?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Mundo Patitas - Generar Orden</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../Style/CUS02/base.css">
  <link rel="stylesheet" href="../Style/CUS02/layout.css">
  <link rel="stylesheet" href="../Style/CUS02/components.css">
  <link rel="stylesheet" href="../Style/CUS02/CUS02_OrdenPedido.css">
</head>

<body>
  <main class="container">
    <section class="has-meta">
      <div class="row row--meta meta-stack">
        <div class="meta-line">
          <div class="col">
            <label>Nombre:</label>
            <span id="lblRol"><?= htmlspecialchars($actor['rol'] ?? '') ?></span>
          </div>
          <div class="col">
            <label>Rol:</label>
            <span id="lblActor"><?= htmlspecialchars($actor['nombre'] ?? '') ?></span>
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


      <h2>CUS02 – Generar Orden de Pedido</h2>

      <hr />
      <h3>Cliente</h3>
      <div class="grid grid--cliente">
        <label>DNI:</label>
        <div class="input-group">
          <input id="txtDni" maxlength="8" inputmode="numeric" placeholder="Ej: 12345678" />
          <button id="btnBuscar" type="button">Buscar</button>
        </div>

        <label>Teléfono:</label>
        <input id="txtTel" readonly />

        <label>Nombre:</label>
        <input id="txtNombre" readonly />

        <label>Dirección:</label>
        <input id="txtDir" readonly />

        <label>Apellido paterno:</label>
        <input id="txtApePat" readonly />

        <label>Email:</label>
        <input id="txtEmail" readonly />

        <label>Apellido materno:</label>
        <input id="txtApeMat" readonly />
      </div>
      <div id="msgCliente" class="msg"></div>

      <hr />
      <div class="row">
        <div class="col-8">
          <h3>Lista de Preórdenes</h3>
          <table id="tblPreorden" class="table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Fecha Emisión</th>
                <th>DNI Cliente</th>
                <th>Total (S/)</th>
                <th>Estado</th>
                <th>Sel</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <button id="btnAgregar">Agregar a la orden</button>
          <div id="msgPreorden" class="msg"></div>
        </div>

        <div class="col-4">
          <h3>Método de entrega</h3>
          <select id="cboEntrega"></select>
        </div>

        <!-- Panel de dirección de envío (solo visible si Delivery) -->
        <fieldset id="envioPanel" class="envio-panel" style="display:none;">
          <legend>Dirección de entrega</legend>

          <div class="envio-modo" role="radiogroup" aria-label="Modo de dirección de entrega">
            <label class="radio">
              <input type="radio" name="envioModo" value="guardada" aria-controls="envioGuardada">
              Usar dirección guardada
            </label>
            <label class="radio">
              <input type="radio" name="envioModo" value="otra" aria-controls="envioOtra" checked>
              Usar otra dirección
            </label>
          </div>

          <!-- Lista de direcciones guardadas (se llena al buscar cliente) -->
          <div id="envioGuardada" class="envio-guardada" hidden>
            <label for="cboDireccionGuardada" class="sr-only">Dirección guardada</label>
            <select id="cboDireccionGuardada">
              <!-- opciones dinámicas: cada <option> idealmente con
           data-nombre, data-tel, data-dir, data-dni, data-dist -->
            </select>
            <small class="hint">Selecciona una dirección previamente guardada para este cliente.</small>
          </div>

          <!-- Form de “otra dirección” -->
          <div id="envioOtra" class="envio-otra">
            <div class="row grid-2">
              <div>
                <label for="envioNombre">Nombre contacto</label>
                <input id="envioNombre" maxlength="120" placeholder="Ej: Juan Pérez" name="envioNombre" autocomplete="name">
              </div>
              <div>
                <label for="envioTelefono">Teléfono</label>
                <input id="envioTelefono" maxlength="20" inputmode="tel" placeholder="Ej: 999888777" name="envioTelefono" autocomplete="tel">
              </div>
            </div>

            <div class="row mt-8">
              <label for="envioDireccion">Dirección</label>
              <input id="envioDireccion" maxlength="255" placeholder="Calle/Av, número" name="envioDireccion" autocomplete="street-address">
            </div>

            <!-- 🔹 SOLO visible en “otra” -->
            <div class="row grid-2 mt-8">
              <div>
                <label for="envioReceptorDni">DNI de quien recibe</label>
                <input id="envioReceptorDni" maxlength="8" pattern="\d{8}" inputmode="numeric" placeholder="8 dígitos" autocomplete="off">
              </div>
              <div>
                <label for="envioDistrito">Distrito</label>
                <input id="envioDistrito" maxlength="120" placeholder="Distrito" autocomplete="address-level2">
                <datalist id="dlDistritos"></datalist>
                <small id="distritoHint" class="hint"></small>


              </div>
            </div>

            <div class="row mt-8">
              <label class="checkbox">
                <input type="checkbox" id="chkGuardarDireccion" name="guardarDireccionCliente" value="1" checked>
                Guardar esta dirección
              </label>
            </div>
          </div>

        </fieldset>


      </div>

      <hr />
      <h3>Lista de productos de la orden</h3>
      <table id="tblItems" class="table">
        <thead>
          <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th>Precio (S/)</th>
            <th>Cantidad</th>
            <th>Subtotal (S/)</th>
            <!-- <th>Peso unit. (kg)</th>
            <th>Peso total (kg)</th>
            <th>Vol. unit. (L)</th>
            <th>Vol. total (L)</th> -->
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <div class="totales">
        <label>Cantidad productos:</label><input id="txtCantProd" value="0" readonly />
        <!-- <label>Peso total (kg):</label><input id="txtPesoTotal" value="0" readonly />
        <label>Volumen total (L):</label><input id="txtVolumenTotal" value="0" readonly /> -->
        <label>Costo de entrega (S/):</label><input id="txtCostoEnt" value="0" readonly />
        <label>Des. aplicado (S/):</label><input id="txtDesc" value="0" readonly />
        <label>Total Pedido(S/):</label><input id="txtSubTotal" value="0" readonly />
        <label>Total a Pagar(S/):</label><input id="txtTotal" value="0" readonly />
      </div>

      <div class="acciones">
        <button id="btnRegistrar" disabled>Generar Orden</button>
        <button id="btnSalir" type="button">Salir</button>
      </div>

      <div id="msg" class="msg"></div>
    </section>

  </main>
  <!-- Modal genérico -->
  <dialog id="appDialog" class="modal">
    <form method="dialog" class="modal__card">
      <h3 id="appDialogTitle" class="modal__title">Orden generada</h3>
      <p id="appDialogMsg" class="modal__msg">Mensaje…</p>
      <div class="modal__actions">
        <button id="appDialogOk" value="ok" class="btn btn-primary">Aceptar</button>
      </div>
    </form>
  </dialog>



  <script src="../Script/CUS02/api.js"></script>
  <script src="../Script/CUS02/utils.js"></script>
  <script>
    window.T77 = <?= json_encode($t77, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="../Script/CUS02/cliente.js"></script>
  <script src="../Script/CUS02/distritos.js"></script>
  <script src="../Script/CUS02/preorden.js"></script>
  <script src="../Script/CUS02/orden.js"></script>
  <script src="../Script/CUS02/main.js"></script>
  <script src="../Script/CUS02/dialog.js"></script>
  <script src="../Script/actualizarHora.js" type="text/javascript"></script>


</body>

</html>