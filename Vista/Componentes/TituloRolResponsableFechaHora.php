<?php
$parametrosComponenteTitulo = isset($parametrosComponenteTitulo) ? $parametrosComponenteTitulo : [];
$titulo = $parametrosComponenteTitulo['titulo'] ?? "Título por defecto";
$trabajador = $parametrosComponenteTitulo['trabajador'] ?? "";
$rol = $parametrosComponenteTitulo['rol'] ?? "";
$fecha = new DateTime();
?>

<section class="title">
    <h1><?php echo htmlspecialchars($titulo); ?></h1>
</section>
<section class="data">
    <div class="responsable">
        <h2>Responsable:</h2>
        <input type="text" readonly value="<?php echo htmlspecialchars($trabajador); ?>">
    </div>
    <div class="rol">
        <h2>Rol:</h2>
        <input type="text" readonly value="<?php echo htmlspecialchars($rol); ?>">
    </div>
    <div class="date">
        <h2>Fecha:</h2>
        <input type="text" readonly value="<?php echo $fecha->format("d-m-Y"); ?>">
    </div>
    <div class="hour">
        <h2>Hora:</h2>
        <input type="text" readonly id="hora">
    </div>
</section>
<script src="../Script/actualizarHora.js" type="text/javascript"></script>