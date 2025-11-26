<?php
// Modelo/CUS30RecaudacionDeliveryModel.php

class CUS30RecaudacionDeliveryModel
{
  /** @var mysqli */
  private $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  // -------------------------------------------------------
  // Asignaciones pendientes por DNI de repartidor
  // -------------------------------------------------------
  public function buscarAsignacionesPendientesPorDniRepartidor(string $dniRepartidor): array
  {
    $dniEsc = mysqli_real_escape_string($this->cn, $dniRepartidor);

    $sql = "
      SELECT
  oa.Id_OrdenAsignacion,
  oa.FechaProgramada,
  oa.Estado AS EstadoRuta,
  ncd.Id_NotaCajaDelivery,
  ncd.MontoFondo,
  ncd.Estado AS EstadoNota,
  t.id_Trabajador,
  t.des_nombreTrabajador,
  t.des_apepatTrabajador,
  t.des_apematTrabajador
FROM t40OrdenAsignacionReparto oa
JOIN t79AsignacionRepartidorVehiculo ar
  ON oa.Id_AsignacionRepartidorVehiculo = ar.Id_AsignacionRepartidorVehiculo
JOIN t16CatalogoTrabajadores t
  ON ar.Id_Trabajador = t.id_Trabajador
LEFT JOIN t419NotaCajaDelivery ncd
  ON ncd.Id_OrdenAsignacion = oa.Id_OrdenAsignacion
WHERE t.DNITrabajador = '{$dniEsc}'
  AND oa.Estado IN ('Pendiente','En Ruta','Por Recaudar')
ORDER BY oa.FechaProgramada, oa.Id_OrdenAsignacion;

    ";

    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) {
      throw new Exception('Error al listar asignaciones: ' . mysqli_error($this->cn));
    }

    $rutas = [];
    while ($row = mysqli_fetch_assoc($rs)) {
      $rutas[] = [
        'Id_OrdenAsignacion' => (int)$row['Id_OrdenAsignacion'],
        'FechaProgramada'    => $row['FechaProgramada'],
        'EstadoRuta'         => $row['EstadoRuta'],
        'Id_NotaCajaDelivery'=> isset($row['Id_NotaCajaDelivery']) ? (int)$row['Id_NotaCajaDelivery'] : null,
        'MontoFondo'         => isset($row['MontoFondo']) ? (float)$row['MontoFondo'] : 0.0,
        'EstadoNota'         => $row['EstadoNota'] ?? null,
        'RepartidorId'       => (int)$row['id_Trabajador'],
        'RepartidorNombre'   => trim(($row['des_nombreTrabajador'] ?? '') . ' ' . ($row['des_apepatTrabajador'] ?? '') . ' ' . ($row['des_apematTrabajador'] ?? '')),
      ];
    }

    return $rutas;
  }

  // -------------------------------------------------------
  // Cabecera de asignación + nota de caja
  // -------------------------------------------------------
  public function obtenerCabeceraAsignacion(int $idOrdenAsignacion): ?array
  {
    $sql = "
      SELECT
        oa.Id_OrdenAsignacion,
        oa.FechaProgramada,
        oa.Estado AS EstadoRuta,
        ar.Id_Trabajador,
        t.des_nombreTrabajador,
        t.des_apepatTrabajador,
        t.des_apematTrabajador,
        ncd.Id_NotaCajaDelivery,
        ncd.MontoFondo,
        ncd.Estado AS EstadoNota
      FROM t40OrdenAsignacionReparto oa
      JOIN t79AsignacionRepartidorVehiculo ar
        ON oa.Id_AsignacionRepartidorVehiculo = ar.Id_AsignacionRepartidorVehiculo
      JOIN t16CatalogoTrabajadores t
        ON ar.Id_Trabajador = t.id_Trabajador
      LEFT JOIN t419NotaCajaDelivery ncd
        ON ncd.Id_OrdenAsignacion = oa.Id_OrdenAsignacion
      WHERE oa.Id_OrdenAsignacion = {$idOrdenAsignacion}
      LIMIT 1
    ";

    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) {
      throw new Exception('Error al obtener cabecera: ' . mysqli_error($this->cn));
    }
    $row = mysqli_fetch_assoc($rs);
    if (!$row || empty($row['Id_NotaCajaDelivery'])) {
      return null;
    }

    return [
      'Id_OrdenAsignacion' => (int)$row['Id_OrdenAsignacion'],
      'FechaProgramada'    => $row['FechaProgramada'],
      'EstadoRuta'         => $row['EstadoRuta'],
      'Id_Trabajador'      => (int)$row['Id_Trabajador'],
      'RepartidorNombre'   => trim(($row['des_nombreTrabajador'] ?? '') . ' ' . ($row['des_apepatTrabajador'] ?? '') . ' ' . ($row['des_apematTrabajador'] ?? '')),
      'Id_NotaCajaDelivery'=> (int)$row['Id_NotaCajaDelivery'],
      'MontoFondo'         => (float)$row['MontoFondo'],
      'EstadoNota'         => $row['EstadoNota'],
    ];
  }

  // -------------------------------------------------------
  // Pedidos delivery + contraentrega de la asignación
  // (filtrado por descripción de método, tú ya luego puedes
  //  afinar por forma de pago si tienes campo)
  // -------------------------------------------------------
  public function listarPedidosDeliveryContraentrega(int $idOrdenAsignacion): array
{
    $idOrdenAsignacion = (int)$idOrdenAsignacion;

    $sql = "
      SELECT
        op.Id_OrdenPedido,
        COALESCE(dop.Total, op.Total)       AS MontoPedido,
        dop.EfectivoCliente,
        dop.Vuelto                          AS VueltoProgramado,
        cli.des_nombreCliente,
        cli.des_apepatCliente,
        cli.des_apematCliente,
        me.Descripcion                      AS MetodoEntrega,
        op.Estado
      FROM t401DetalleAsignacionReparto dar
      JOIN t59OrdenServicioEntrega ose
        ON dar.Id_OSE = ose.Id_OSE
      JOIN t02OrdenPedido op
        ON ose.Id_OrdenPedido = op.Id_OrdenPedido
      JOIN t20Cliente cli
        ON op.Id_Cliente = cli.Id_Cliente
      JOIN t27MetodoEntrega me
        ON op.Id_MetodoEntrega = me.Id_MetodoEntrega
      LEFT JOIN T501DetalleOPCE dop
        ON dop.IdOrdenPedido = op.Id_OrdenPedido
      WHERE dar.Id_OrdenAsignacion = {$idOrdenAsignacion}
        AND (me.Descripcion LIKE '%delivery%' OR me.Descripcion LIKE '%Delivery%')
    ";

    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) {
      throw new Exception('Error al listar pedidos: ' . mysqli_error($this->cn));
    }

    $pedidos = [];
    while ($p = mysqli_fetch_assoc($rs)) {

      $montoPedido = isset($p['MontoPedido']) ? (float)$p['MontoPedido'] : 0.0;
      $efectivo    = isset($p['EfectivoCliente']) ? (float)$p['EfectivoCliente'] : 0.0;
      $vueltoProg  = isset($p['VueltoProgramado']) ? (float)$p['VueltoProgramado'] : 0.0;

      // Por si en algún momento no se llenó Vuelto en T501,
      // podemos inferirlo como efectivo - total (solo si efectivo >= total).
      if ($vueltoProg <= 0 && $efectivo >= $montoPedido) {
        $vueltoProg = $efectivo - $montoPedido;
      }
      
      $pedidos[] = [
        'Id_OrdenPedido'      => (int)$p['Id_OrdenPedido'],
        'Cliente'             => trim(
                                  ($p['des_nombreCliente'] ?? '') . ' ' .
                                  ($p['des_apepatCliente'] ?? '') . ' ' .
                                  ($p['des_apematCliente'] ?? '')
                                ),
        'MontoPedido'         => $montoPedido,
        'MetodoEntrega'       => $p['MetodoEntrega'],
        'EfectivoCliente'     => $efectivo,
        'VueltoProgramado'    => $vueltoProg,
        // Este campo ya lo puede usar directamente el front para la columna
        // "Monto Esperado en caja por pedido" cuando el estado sea Entregado.
        'MontoEsperadoCaja'   => $montoPedido + $vueltoProg,
        'EstadoPedido'        => $p['Estado'] ?? 'Pendiente',
      ];
    }

    return $pedidos;
}


  // -------------------------------------------------------
  // Verificar si ya hay recaudación para la asignación
  // -------------------------------------------------------
  public function existeRecaudacionParaAsignacion(int $idOrdenAsignacion): bool
  {
    $sql = "SELECT COUNT(*) AS Cnt FROM t420RecaudacionDelivery WHERE Id_OrdenAsignacion = {$idOrdenAsignacion}";
    $rs  = mysqli_query($this->cn, $sql);
    if (!$rs) {
      throw new Exception('Error verificando recaudación existente: ' . mysqli_error($this->cn));
    }
    $row = mysqli_fetch_assoc($rs);
    return ((int)$row['Cnt'] > 0);
  }

  // -------------------------------------------------------
  // Obtener monto de fondo de nota de caja
  // -------------------------------------------------------
  public function obtenerMontoFondoNotaCaja(int $idNotaCajaDelivery, int $idOrdenAsignacion): ?float
  {
    $sql = "
      SELECT MontoFondo
      FROM t419NotaCajaDelivery
      WHERE Id_NotaCajaDelivery = {$idNotaCajaDelivery}
        AND Id_OrdenAsignacion = {$idOrdenAsignacion}
      LIMIT 1
    ";
    $rs = mysqli_query($this->cn, $sql);
    if (!$rs) {
      throw new Exception('Error consultando nota de caja: ' . mysqli_error($this->cn));
    }
    $row = mysqli_fetch_assoc($rs);
    if (!$row) return null;
    return (float)$row['MontoFondo'];
  }

  // -------------------------------------------------------
  // Cerrar recaudación:
  // - t420, t421
  // - t422 si faltante
  // - estados t419 y t40
  // -------------------------------------------------------
  public function cerrarRecaudacion(array $cab, array $rowsDetalle): array
  {
    $idOrdenAsignacion      = (int)$cab['Id_OrdenAsignacion'];
    $idTrabajadorRepartidor = (int)$cab['Id_TrabajadorRepartidor'];
    $idNotaCajaDelivery     = (int)$cab['Id_NotaCajaDelivery'];
    $mF                      = (float)$cab['MontoFondoRetirado'];
    $mV                      = (float)$cab['MontoVentasEsperado'];
    $mVu                     = (float)$cab['MontoVueltoEsperado'];
    $mEf                     = (float)$cab['MontoEfectivoEntregado'];
    $dif                     = (float)$cab['DiferenciaGlobal'];
    $estadoRec               = (string)$cab['EstadoRec'];

    mysqli_begin_transaction($this->cn);
    try {
      // 1) Insertar t420
      $estEsc = mysqli_real_escape_string($this->cn, $estadoRec);

      $sqlIns420 = "
        INSERT INTO t420RecaudacionDelivery (
          Id_OrdenAsignacion,
          Id_Trabajador,
          Id_NotaCajaDelivery,
          FechaRecaudacion,
          MontoFondoRetirado,
          MontoVentasEsperado,
          MontoVueltoEsperado,
          MontoEfectivoEntregado,
          Diferencia,
          Estado
        ) VALUES (
          {$idOrdenAsignacion},
          {$idTrabajadorRepartidor},
          {$idNotaCajaDelivery},
          NOW(),
          {$mF},
          {$mV},
          {$mVu},
          {$mEf},
          {$dif},
          '{$estEsc}'
        )
      ";
      if (!mysqli_query($this->cn, $sqlIns420)) {
        throw new Exception('Error insertando recaudación (t420): ' . mysqli_error($this->cn));
      }
      $idRecaudacion = (int)mysqli_insert_id($this->cn);

      // 2) Insertar detalle t421
      foreach ($rowsDetalle as $rowDet) {
        $idOp = (int)$rowDet['Id_OrdenPedido'];
        $mp   = (float)$rowDet['MontoPedido'];
        $mc   = (float)$rowDet['MontoCobrado'];
        $mv   = (float)$rowDet['MontoVueltoEntregado'];
        $df   = (float)$rowDet['Diferencia'];
        $ep   = mysqli_real_escape_string($this->cn, $rowDet['EstadoPedido']);

        $sqlIns421 = "
          INSERT INTO t421RecaudacionDeliveryPedido (
            Id_Recaudacion,
            Id_OrdenPedido,
            MontoPedido,
            MontoCobrado,
            MontoVueltoEntregado,
            Diferencia,
            EstadoPedido
          ) VALUES (
            {$idRecaudacion},
            {$idOp},
            {$mp},
            {$mc},
            {$mv},
            {$df},
            '{$ep}'
          )
        ";
        if (!mysqli_query($this->cn, $sqlIns421)) {
          throw new Exception('Error insertando detalle recaudación (t421): ' . mysqli_error($this->cn));
        }
      }

      // 3) t422 si faltante
      $notaDescuentoData = null;
      if ($estadoRec === 'Faltante') {
        $montoDescuento = abs($dif);
        $hoy            = date('Y-m-d');
        $periodo        = date('Y-m');
        $motivo         = "Faltante en recaudación delivery Id_Recaudacion={$idRecaudacion}";
        $motivoEsc      = mysqli_real_escape_string($this->cn, $motivo);

        $sqlIns422 = "
          INSERT INTO t422NotaDescuentoPlanilla (
            Id_Recaudacion,
            id_Trabajador,
            FechaEmision,
            PeriodoPlanilla,
            MontoDescuento,
            Motivo,
            Estado
          ) VALUES (
            {$idRecaudacion},
            {$idTrabajadorRepartidor},
            '{$hoy}',
            '{$periodo}',
            {$montoDescuento},
            '{$motivoEsc}',
            'Pendiente'
          )
        ";
        if (!mysqli_query($this->cn, $sqlIns422)) {
          throw new Exception('Error insertando nota de descuento (t422): ' . mysqli_error($this->cn));
        }
        $idNotaDesc = (int)mysqli_insert_id($this->cn);
        $notaDescuentoData = [
          'Id_NotaDescuento' => $idNotaDesc,
          'MontoDescuento'   => $montoDescuento,
          'PeriodoPlanilla'  => $periodo,
        ];
      }

      // 4) Actualizar t419 estado
      $estadoNota = 'Liquidada';
      if ($estadoRec === 'Con Faltante') {
        $estadoNota = 'Con Faltante';
      } elseif ($estadoRec === 'Con Sobrante') {
        $estadoNota = 'Con Sobrante';
      }
      $estadoNotaEsc = mysqli_real_escape_string($this->cn, $estadoNota);
      $sqlUpd419 = "
        UPDATE t419NotaCajaDelivery
        SET Estado = '{$estadoNotaEsc}'
        WHERE Id_NotaCajaDelivery = {$idNotaCajaDelivery}
      ";
      if (!mysqli_query($this->cn, $sqlUpd419)) {
        throw new Exception('Error actualizando estado NotaCajaDelivery (t419): ' . mysqli_error($this->cn));
      }

      // 5) Actualizar t40 estado
      $estadoRuta = ($estadoRec === 'Cuadrado') ? 'Recaudado' : 'Recaudado con obs';
      $estadoRutaEsc = mysqli_real_escape_string($this->cn, $estadoRuta);
      $sqlUpd40 = "
        UPDATE t40OrdenAsignacionReparto
        SET Estado = '{$estadoRutaEsc}'
        WHERE Id_OrdenAsignacion = {$idOrdenAsignacion}
      ";
      if (!mysqli_query($this->cn, $sqlUpd40)) {
        throw new Exception('Error actualizando estado OrdenAsignacion (t40): ' . mysqli_error($this->cn));
      }

      mysqli_commit($this->cn);

      return [
        'Id_Recaudacion' => $idRecaudacion,
        'NotaDescuento'  => $notaDescuentoData,
      ];
    } catch (Throwable $e) {
      mysqli_rollback($this->cn);
      throw $e;
    }
  }
}
