<?php
/**
 * Modelo: SolicitudCotizacion
 * Gestiona las solicitudes de cotización a proveedores (tabla t100)
 */
final class SolicitudCotizacion
{
  private $cn;

  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  /**
   * Listar solicitudes de cotización por requerimiento
   * @param string $idReq - Id del requerimiento
   * @return array - Lista de solicitudes
   */
  public function listarPorRequerimiento(string $idReq): array
  {
    $sql = "SELECT 
              s.IDsolicitud,
              s.Id_ReqEvaluacion,
              s.RUC,
              s.Empresa,
              s.Correo,
              s.FechaEmision,
              s.FechaCierre,
              s.Estado
            FROM t100Solicitud_Cotizacion_Proveedor s
            WHERE s.Id_ReqEvaluacion = ?
            ORDER BY s.FechaEmision DESC, s.IDsolicitud ASC";

    $st = mysqli_prepare($this->cn, $sql);
    if (!$st) {
      throw new Exception("Error al preparar consulta: " . mysqli_error($this->cn));
    }

    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $rows[] = [
        'IDsolicitud'       => (int)$r['IDsolicitud'],
        'Id_ReqEvaluacion'  => $r['Id_ReqEvaluacion'],
        'RUC'               => $r['RUC'],
        'Empresa'           => $r['Empresa'],
        'Correo'            => $r['Correo'],
        'FechaEmision'      => $r['FechaEmision'],
        'FechaCierre'       => $r['FechaCierre'],
        'Estado'            => $r['Estado']
      ];
    }
    mysqli_stmt_close($st);
    return $rows;
  }

  /**
   * Obtener una solicitud específica por ID
   * @param int $idSolicitud
   * @return array|null
   */
  public function obtenerPorId(int $idSolicitud): ?array
  {
    $sql = "SELECT 
              s.IDsolicitud,
              s.Id_ReqEvaluacion,
              s.RUC,
              s.Empresa,
              s.Correo,
              s.FechaEmision,
              s.FechaCierre,
              s.Estado
            FROM t100Solicitud_Cotizacion_Proveedor s
            WHERE s.IDsolicitud = ?";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "i", $idSolicitud);
    mysqli_stmt_execute($st);
    $rs  = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
    
    return $row ?: null;
  }

  /**
   * Crear una nueva solicitud de cotización
   * @param array $datos - ['Id_ReqEvaluacion', 'RUC', 'Empresa', 'Correo']
   * @return int - ID de la solicitud creada
   */
  public function crear(array $datos): int
  {
    $sql = "INSERT INTO t100Solicitud_Cotizacion_Proveedor 
            (Id_ReqEvaluacion, RUC, Empresa, Correo, FechaEmision, FechaCierre, Estado)
            VALUES (?, ?, ?, ?, NOW(), NOW() + INTERVAL 10 DAY, 'Pendiente')";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param(
      $st,
      "ssss",
      $datos['Id_ReqEvaluacion'],
      $datos['RUC'],
      $datos['Empresa'],
      $datos['Correo']
    );
    
    if (!mysqli_stmt_execute($st)) {
      mysqli_stmt_close($st);
      throw new Exception("Error al crear solicitud: " . mysqli_error($this->cn));
    }

    $id = mysqli_insert_id($this->cn);
    mysqli_stmt_close($st);
    return $id;
  }

  /**
   * Actualizar estado de una solicitud
   * @param int $idSolicitud
   * @param string $nuevoEstado - 'Pendiente', 'Enviada', 'Respondida', 'Vencida'
   */
  public function actualizarEstado(int $idSolicitud, string $nuevoEstado): void
  {
    $sql = "UPDATE t100Solicitud_Cotizacion_Proveedor 
            SET Estado = ?
            WHERE IDsolicitud = ?";
    
    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "si", $nuevoEstado, $idSolicitud);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
  }

  /**
   * Contar solicitudes por estado para un requerimiento
   * @param string $idReq
   * @return array - ['Pendiente' => n, 'Enviada' => n, ...]
   */
  public function contarPorEstado(string $idReq): array
  {
    $sql = "SELECT Estado, COUNT(*) as total
            FROM t100Solicitud_Cotizacion_Proveedor
            WHERE Id_ReqEvaluacion = ?
            GROUP BY Estado";

    $st = mysqli_prepare($this->cn, $sql);
    mysqli_stmt_bind_param($st, "s", $idReq);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $conteo = [];
    while ($r = mysqli_fetch_assoc($rs)) {
      $conteo[$r['Estado']] = (int)$r['total'];
    }
    mysqli_stmt_close($st);
    return $conteo;
  }
}