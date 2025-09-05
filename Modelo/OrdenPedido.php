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
        $idCliente = (int)$payload['idCliente'];

        mysqli_begin_transaction($this->cn);
        try {
            // Cabecera
            $sqlCab = "INSERT INTO t02OrdenPedido
                       (Fecha, Id_Cliente, Id_MetodoEntrega, CostoEntrega, Descuento, Total, Estado)
                       VALUES (NOW(), ?, ?, ?, ?, ?, 'Generada')";
            $st = mysqli_prepare($this->cn, $sqlCab);
            if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
            mysqli_stmt_bind_param(
                $st, "iiddd",
                $idCliente,
                $payload['metodoEntregaId'],
                $payload['costoEntrega'],
                $payload['descuento'],
                $payload['total']
            );
            mysqli_stmt_execute($st);
            $ordenId = mysqli_insert_id($this->cn);
            mysqli_stmt_close($st);

            // Detalle
            if (empty($payload['items']) || !is_array($payload['items'])) {
                throw new RuntimeException('Items vacíos o inválidos');
            }
            $sqlDet = "INSERT INTO t60DetOrdenPedido(
                         t18CatalogoProducto_Id_Producto,
                         t02OrdenPedido_Id_OrdenPedido,
                         Id_Cliente,
                         Cantidad
                       ) VALUES (?,?,?,?)";
            $st = mysqli_prepare($this->cn, $sqlDet);
            if (!$st) { throw new RuntimeException(mysqli_error($this->cn)); }
            foreach ($payload['items'] as $it) {
                $idProd = (int)($it['IdProducto'] ?? 0);
                $cant   = (int)($it['Cantidad'] ?? 0);
                if ($idProd <= 0 || $cant <= 0) {
                    throw new RuntimeException('Item inválido (IdProducto/Cantidad)');
                }
                mysqli_stmt_bind_param($st, "iiii", $idProd, $ordenId, $idCliente, $cant);
                mysqli_stmt_execute($st);
            }
            mysqli_stmt_close($st);

            mysqli_commit($this->cn);
            return $ordenId;
        } catch (\Throwable $e) {
            mysqli_rollback($this->cn);
            throw $e;
        }
    }
}
