<?php
include_once __DIR__ . '/../Controlador/Conexion.php';

class IncidenciaEntrega {
    private $cn;
    public function __construct() {
        $this->cn = (new Conexion())->conecta();
    }

    public function listarDistritos(): array {
        $sql = "SELECT Id_Distrito, DescNombre AS Distrito 
                FROM t77DistritoEnvio 
                WHERE Estado = 'Activo'";
        $rs = mysqli_query($this->cn, $sql);
        return $rs ? mysqli_fetch_all($rs, MYSQLI_ASSOC) : [];
    }

    public function listarPedidosPorDistrito($idDistrito): array {
        $sql = "SELECT o.Id_OrdenPedido AS IDPedido,
                       CONCAT(c.des_nombreCliente, ' ', c.des_apepatCliente, ' ', c.des_apematCliente) AS Cliente,
                       d.DireccionSnap AS Direccion,
                       d.TelefonoSnap AS Telefono,
                       o.Estado
                FROM t02OrdenPedido o
                JOIN t71OrdenDirecEnvio d ON o.Id_OrdenPedido = d.Id_OrdenPedido
                JOIN t20Cliente c ON o.Id_Cliente = c.Id_Cliente
                WHERE d.Id_Distrito = ?
                AND (o.Estado = 'En reparto' OR o.Estado = 'No entregado')
                AND o.Id_OrdenPedido NOT IN (SELECT IDPedido FROM t405IncidenciaEntrega)";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param($st, "i", $idDistrito);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $data = mysqli_fetch_all($rs, MYSQLI_ASSOC);
        mysqli_stmt_close($st);
        return $data;
    }

    public function registrarIncidencia(array $data): bool {
        $sql = "INSERT INTO t405IncidenciaEntrega
                (IDPedido, Cliente, Direccion, Motivo, Observaciones, Estado, FechaIncidencia, Foto)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
        $st = mysqli_prepare($this->cn, $sql);
        mysqli_stmt_bind_param(
            $st, "issssss",
            $data['IDPedido'], $data['Cliente'], $data['Direccion'],
            $data['Motivo'], $data['Observaciones'], $data['Estado'], $data['Foto']
        );
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        if ($ok) {
            // Cambia estado del pedido a "No entregado"
            $update = mysqli_prepare($this->cn, "UPDATE t02OrdenPedido SET Estado='No entregado' WHERE Id_OrdenPedido=?");
            mysqli_stmt_bind_param($update, "i", $data['IDPedido']);
            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);
        }

        return $ok;
    }

    public function listarIncidencias(): array {
        $sql = "SELECT IDIncidenciaEntrega, IDPedido, Cliente, Motivo, Estado, FechaIncidencia
                FROM t405IncidenciaEntrega
                ORDER BY FechaIncidencia DESC";
        $rs = mysqli_query($this->cn, $sql);
        return $rs ? mysqli_fetch_all($rs, MYSQLI_ASSOC) : [];
    }
}
?>
