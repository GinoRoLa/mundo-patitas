<?php 
include_once '../../Controlador/CUS26Negocio.php';
$obj = new CUS26Negocio();
$distritos = $obj->listarDistritos();

$titulo = "IU026 - Registrar Incidencia de Entrega";
$trabajador = "Anglas Geraldine";
$rol = "Repartidor";
$parametrosComponenteTitulo = [
  "titulo" => $titulo,
  "trabajador" => $trabajador,
  "rol" => $rol
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($titulo) ?></title>
  <link rel="stylesheet" href="../Style/CUS26/CUS26_IU026.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <link href="../Style/Style.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS04/CUS04_IU004.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleTittleGeneral.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleInputGeneral.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleTbodyTable.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleButtonGeneral.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleFormularioDatos.css" rel="stylesheet" type="text/css"/>
  <link href="../Style/CUS01/StyleInputNumberSinSpinner.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<main class="container">
  <?php include "../Componentes/TituloRolResponsableFechaHora.php"; ?>

  <!-- Selección de distrito -->
  <section>
    <h2>Seleccionar distrito</h2>
    <select id="cboDistrito">
      <option value="">-- Seleccione --</option>
      <?php foreach ($distritos as $d): ?>
        <option value="<?= $d['Id_Distrito'] ?>"><?= htmlspecialchars($d['Distrito']) ?></option>
      <?php endforeach; ?>
    </select>
  </section>

  <!-- Tabla de pedidos -->
  <section class="contenedor-tablas">
    <!-- Tabla de En reparto -->
    <div class="tabla-contenedor">
      <h3>Pedidos "En reparto" (para posible reprogramación)</h3>
      <table id="tablaReparto">
        <thead>
          <tr>
            <th>ID Pedido</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Seleccionar</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Tabla de No entregado -->
    <div class="tabla-contenedor">
      <h3>Pedidos "No entregado" (para registrar incidencia)</h3>
      <table id="tablaNoEntregado">
        <thead>
          <tr>
            <th>ID Pedido</th>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Seleccionar</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </section>

  <!-- Registro de incidencia -->
  <section id="datosIncidencia">
    <h3>Registrar incidencia</h3>
    <form id="formIncidencia" enctype="multipart/form-data">
      <div class="form-grid">
        <label>ID Pedido:</label>
        <input type="text" name="IDPedido" id="txtIDPedido" readonly>

        <label>Cliente:</label>
        <input type="text" name="Cliente" id="txtCliente" readonly>

        <label>Dirección:</label>
        <input type="text" name="Direccion" id="txtDireccion" readonly>

        <label>Motivo:</label>
        <select name="Motivo" id="cboMotivo" required>
          <option value="">-- Seleccione motivo --</option>
          <option value="Ausencia del receptor">Ausencia del receptor</option>
          <option value="Rechazo del pedido">Rechazo del pedido</option>
          <option value="Acceso restringido">Acceso restringido</option>
          <option value="Dirección incorrecta">Dirección incorrecta</option>
          <option value="Otro">Otro</option>
        </select>

        <label>Observación:</label>
        <textarea name="Observaciones" required></textarea>

        <label>Foto (evidencia):</label>
        <input type="file" name="foto" accept="image/*">
      </div>

      <div class="botones">
        <button type="submit">Registrar incidencia</button>
        <button type="button" id="btnVerIncidencias">Ver incidencias registradas</button>
      </div>
    </form>
  </section>

  <!-- Tabla de incidencias registradas -->
  <section id="tablaIncidencias" style="display:none;">
    <h3>Incidencias registradas</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Pedido</th>
          <th>Cliente</th>
          <th>Motivo</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody id="tbodyIncidencias"></tbody>
    </table>
  </section>

</main>

<script src="../Script/CUS26/cargarPedidosPorDistrito.js"></script>
<script src="../Script/CUS26/registrarIncidencia.js"></script>
<script src="../Script/CUS26/verIncidencias.js"></script>
</body>
</html>
