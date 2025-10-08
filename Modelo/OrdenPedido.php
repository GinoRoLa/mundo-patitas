<?php
// /Modelo/OrdenPedido.php
//include_once '../Controlador/Conexion.php';

final class OrdenPedido {
    private $cn;
    public function __construct(){ $this->cn = (new Conexion())->conecta(); }

    public function crearOrdenConDetalle(array $payload): int {
    if (empty($payload['idCliente']) || (int)$payload['idCliente'] <= 0) {
        throw new InvalidArgumentException('idCliente es requerido en el payload');
    }
    // Prepara el JSON de items (solo los campos necesarios)
    $items = array_map(function($it){
        return [
            'IdProducto' => (int)($it['IdProducto'] ?? 0),
            'Cantidad'   => (int)($it['Cantidad']   ?? 0),
        ];
    }, $payload['items'] ?? []);
    $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

    // Llama al SP (nota: NO inicies transacción aquí)
    $sql = "CALL sp_orden_crear_con_detalle(?,?,?,?,?,?)";
    $st  = mysqli_prepare($this->cn, $sql);
    if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }

    mysqli_stmt_bind_param(
        $st, "iiddds",
        $payload['idCliente'],         // i
        $payload['metodoEntregaId'],   // i
        $payload['costoEntrega'],      // d
        $payload['descuento'],         // d
        $payload['total'],             // d
        $itemsJson                     // s (JSON)
    );

    if (!mysqli_stmt_execute($st)) {
        $err = mysqli_error($this->cn);
        mysqli_stmt_close($st);
        throw new RuntimeException($err ?: 'Fallo al ejecutar SP');
    }

    // Lee el resultset con ordenId
    $rs  = mysqli_stmt_get_result($st);
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($st);

    // Limpia resultsets sobrantes de CALL (muy importante)
    while (mysqli_more_results($this->cn)) { mysqli_next_result($this->cn); }

    if (!$row || !isset($row['ordenId'])) {
        throw new RuntimeException('El SP no devolvió ordenId');
    }
    return (int)$row['ordenId'];
}

}
