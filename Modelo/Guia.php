<?php
final class Guia
{
  private $cn;
  public function __construct()
  {
    $this->cn = (new Conexion())->conecta();
  }

  public function crearGuiaSinNumerador(array $d): array
  {
    $sql = "CALL sp_cus24_crear_guia_sin_numerador(?,?,?,?,?,?,?,?,?,?,?,?,?,?,@o_id,@o_num,@o_numStr)";
    $st  = mysqli_prepare($this->cn, $sql);

    mysqli_stmt_bind_param(
      $st,
      "sssssssiisssss",
      $d['serie'],
      $d['remitenteRuc'],
      $d['remitenteRazon'],
      $d['destinatarioNombre'],
      $d['dniReceptor'],
      $d['direccionDestino'],
      $d['distritoDestino'],
      $d['idDireccionAlmacen'],   // int
      $d['idAsignacionRV'],       // int OBLIGATORIO
      $d['marca'],
      $d['placa'],
      $d['conductor'],
      $d['licencia'],
      $d['motivo']
    );

    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);

    $q   = $this->cn->query("SELECT @o_id AS idGuia, @o_num AS numero, @o_numStr AS numeroStr");
    $row = $q->fetch_assoc();

    return [
      'idGuia'    => (int)$row['idGuia'],
      'numero'    => (int)$row['numero'],
      'numeroStr' => $row['numeroStr'],
    ];
  }

  public function insertarDetalleDesdeOps(int $idGuia, array $ops): void
  {
    // asumiendo SP: sp_cus24_insertar_detalle_guia_from_ops(p_Id_Guia INT, p_ops_json JSON)
    $ops = array_values(array_unique(array_filter(array_map('intval', $ops), fn($v) => $v > 0)));
    $json = $this->cn->real_escape_string(json_encode($ops));
    $sql  = "CALL sp_cus24_insertar_detalle_guia_from_ops($idGuia, '$json')";
    $this->cn->query($sql);
  }
}
