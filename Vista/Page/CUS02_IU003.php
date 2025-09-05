<?php
include_once '../../Controlador/Conexion.php'; 
include_once '../../Modelo/Trabajador.php';
$trabajadorRow = (new Trabajador())->buscarPorDni('22222222');

$actor = [
  'nombre' => $trabajadorRow
    ? trim(($trabajadorRow['des_nombreTrabajador'] ?? '') . ' ' . ($trabajadorRow['des_apepatTrabajador'] ?? '') . ' ' . ($trabajadorRow['des_apematTrabajador'] ?? ''))
    : '(desconocido)',
  'rol' => $trabajadorRow['cargo'] ?? '(Desconocido)'
];
$fecha = date('Y-m-d');
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Mundo Patitas - Generar Orden</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../Style/CUS02/CUS02_OrdenPedido.css">
</head>

<body>
  <main class="container">

    <section>
      <h2>CUS02 – Generar Orden de Pedido</h2>
      <div class="row">
        <div class="col">
          <label>Nombre:</label>
          <span id="lblActor"><?= htmlspecialchars($actor['nombre'] ?? '') ?></span>
        </div>
        <div class="col">
          <label>Rol:</label>
          <span id="lblRol"><?= htmlspecialchars($actor['rol'] ?? '') ?></span>
        </div>
        <div class="col">
          <label>Fecha:</label>
          <input type="date" id="dtpFecha" value="<?= $fecha ?? '' ?>" disabled />
        </div>
        <div class="col">
          <label>Hora:</label>
          <input type="text" id="hora" readonly id="hora">
        </div>
      </div>

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
        </div>

        <div class="col-4">
          <h3>Método de entrega</h3>
          <select id="cboEntrega"></select>
        </div>
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
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <div class="totales">
        <label>Cant productos:</label><input id="txtCantProd" value="0" readonly />
        <label>Costo de entrega (S/):</label><input id="txtCostoEnt" value="0" readonly />
        <label>Des. aplicado (S/):</label><input id="txtDesc" value="0" readonly />
        <label>SubTotal (S/):</label><input id="txtSubTotal" value="0" readonly />
        <label>Total (S/):</label><input id="txtTotal" value="0" readonly />
      </div>

      <div class="acciones">
        <button id="btnRegistrar" disabled>Registrar Orden</button>
        <button id="btnSalir" type="button" onclick="window.location.href='/'">Salir</button>
      </div>

      <div id="msg" class="msg"></div>
    </section>

  </main>
  <script src="../Script/CUS02/api.js"></script>
  <script src="../Script/CUS02/utils.js"></script>
  <script src="../Script/CUS02/cliente.js"></script>
  <script src="../Script/CUS02/preorden.js"></script>
  <script src="../Script/CUS02/orden.js"></script>
  <script src="../Script/CUS02/main.js"></script>
  <script src="../Script/actualizarHora.js" type="text/javascript"></script>

</body>

</html>