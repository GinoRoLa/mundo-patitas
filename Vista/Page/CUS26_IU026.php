<?php
include_once '../../Controlador/CUS26Negocio.php';
$titulo = "IU026 - Registrar incidencia de entrega";
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<main class="container">
  <?php include "../Componentes/TituloRolResponsableFechaHora.php"; ?>

  <section>
    <h2>Buscar pedidos por código de distrito</h2>
    <input type="number" id="txtCodigoDistrito" placeholder="Ingrese código de distrito (Ej: 103)">
    <button id="btnBuscar">Buscar</button>
  </section>

  <section>
    <h3>Pedidos del distrito</h3>
    <div class="tablas-flex">
      <div class="tabla">
        <h4>En reparto</h4>
        <table id="tablaReparto">
          <thead>
            <tr><th>ID</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th><th>Fecha</th><th>Estado</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="tabla">
        <h4>No entregado</h4>
        <table id="tablaNoEntregado">
          <thead>
            <tr><th>ID</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th><th>Fecha</th><th>Seleccionar</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </section>

  <section id="datosIncidencia">
    <h3>Registrar incidencia</h3>
    <form id="formIncidencia" enctype="multipart/form-data">
      <label>ID Pedido:</label>
      <input type="text" name="IDPedido" id="txtIDPedido" readonly>

      <label>Cliente:</label>
      <input type="text" name="Cliente" id="txtCliente" readonly>

      <label>Dirección:</label>
      <input type="text" name="Direccion" id="txtDireccion" readonly>

      <label>Motivo:</label>
      <select name="Motivo" required>
        <option value="">Seleccione...</option>
        <option>Ausencia del receptor</option>
        <option>Rechazo del pedido</option>
        <option>Acceso restringido</option>
        <option>Dirección incorrecta</option>
        <option>Otro</option>
      </select>

      <label>Observación:</label>
      <textarea name="Observaciones" required></textarea>

      <label>Foto (evidencia):</label>
      <input type="file" name="foto" accept="image/*">

      <div class="botones">
        <button type="submit">Registrar incidencia</button>
        <button type="button" id="btnVerIncidencias">Ver incidencias</button>
      </div>
    </form>
  </section>

  <section id="tablaIncidencias" style="display:none;">
    <h3>Incidencias registradas</h3>
    <table>
      <thead><tr><th>ID</th><th>Pedido</th><th>Cliente</th><th>Motivo</th><th>Estado</th><th>Fecha</th></tr></thead>
      <tbody id="tbodyIncidencias"></tbody>
    </table>
  </section>
</main>

<script src="../Script/CUS26/buscarPedidosPorDistrito.js"></script>
<script src="../Script/CUS26/registrarIncidencia.js"></script>
<script src="../Script/CUS26/verIncidencias.js"></script>
</body>
</html>
