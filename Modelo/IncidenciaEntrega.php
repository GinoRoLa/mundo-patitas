<?php
include_once __DIR__ . '/../Controlador/Conexion.php';

class IncidenciaEntrega {
    private $cn;

    public function __construct() {
        $this->cn = (new Conexion())->conecta();
    }

    // Buscar pedidos por código de distrito
    public function listarPedidosPorDistrito($idDistrito) {
        $sql = "SELECT o.Id_OrdenPedido AS IDPedido,
                       CONCAT(c.des_nombreCliente, ' ', c.des_apepatCliente, ' ', c.des_apematCliente) AS Cliente,
                       d.DireccionSnap AS Direccion,
                       d.TelefonoSnap AS Telefono,
                       o.Estado,
                       DATE(o.Fecha) AS Fecha
                FROM t02OrdenPedido o
                JOIN t71OrdenDirecEnvio d ON o.Id_OrdenPedido = d.Id_OrdenPedido
                JOIN t20Cliente c ON o.Id_Cliente = c.Id_Cliente
                WHERE d.Id_Distrito = ?
                AND (o.Estado IN ('En reparto', 'No entregado'))
                AND o.Id_OrdenPedido NOT IN (SELECT IDPedido FROM t405IncidenciaEntrega)";

        $st = mysqli_prepare($this->cn, $sql);
        if (!$st) throw new Exception("Error preparando SQL listarPedidosPorDistrito: " . mysqli_error($this->cn));

        mysqli_stmt_bind_param($st, "i", $idDistrito);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $data = mysqli_fetch_all($rs, MYSQLI_ASSOC);
        mysqli_stmt_close($st);
        return $data;
    }

    // Registrar incidencia
    public function registrarIncidencia(array $data): bool {
        $sql = "INSERT INTO t405IncidenciaEntrega
                (IDPedido, Cliente, Direccion, Motivo, Observaciones, Estado, FechaIncidencia)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $st = mysqli_prepare($this->cn, $sql);
        if (!$st) throw new Exception("Error preparando INSERT: " . mysqli_error($this->cn));

        mysqli_stmt_bind_param($st, "isssss",
            $data['IDPedido'], $data['Cliente'], $data['Direccion'],
            $data['Motivo'], $data['Observaciones'], $data['Estado']
        );

        $ok = mysqli_stmt_execute($st);
        if (!$ok) throw new Exception("Error ejecutando INSERT: " . mysqli_error($this->cn));

        // Actualizar estado del pedido a 'Incidencia registrada'
        $upd = mysqli_prepare($this->cn, "UPDATE t02OrdenPedido SET Estado='Incidencia registrada' WHERE Id_OrdenPedido=?");
        mysqli_stmt_bind_param($upd, "i", $data['IDPedido']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);

        return $ok;
    }

    public function listarIncidencias(): array {
        $sql = "SELECT * FROM t405IncidenciaEntrega ORDER BY FechaIncidencia DESC";
        $rs = mysqli_query($this->cn, $sql);
        return $rs ? mysqli_fetch_all($rs, MYSQLI_ASSOC) : [];
    }
}
?>
