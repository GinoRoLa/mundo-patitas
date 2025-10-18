<?php
require_once __DIR__ . '/../Modelo/GestionNoEntregado.php';

class CUS26Negocio {
    private $modelo;
    private $cn;

    public function __construct() {
        $this->modelo = new GestionNoEntregados();
        $this->cn = $this->modelo->getConexion();
    }

    public function listarNoEntregadas() {
        return $this->modelo->listarNoEntregadas();
    }

    public function registrarReprogramacion($data) {
        $idPedido = $data['idPedido'];
        $idCliente = $data['idCliente'];
        $fechaConsol = $data['fechaConsolRep']; // fecha de consolidación seleccionada
        $estado = 'Reprogramación'; // estado que se guardará en t172
        $fechaHoy = date('Y-m-d');

        // 1) Insertar en t172GestionNoEntregados
        $sqlInsert = "INSERT INTO t172GestionNoEntregados
                    (Id_Consolidacion, Id_OrdenPedido, Id_Cliente, Fecha_Reprogramacion, Estado)
                    SELECT ID_Consolidacion, Id_OrdenPedido, Id_Cliente, '$fechaHoy', '$estado'
                    FROM t171Consolidacion_Entrega
                    WHERE Id_OrdenPedido = $idPedido";

        $resInsert = mysqli_query($this->cn, $sqlInsert);
        if(!$resInsert) return false;

        // 2) Actualizar t02OrdenPedido
        $sqlPedido = "UPDATE t02OrdenPedido
                    SET Fecha = '$fechaHoy',
                        Estado = 'Pagado'
                    WHERE Id_OrdenPedido = $idPedido";
        $resPedido = mysqli_query($this->cn, $sqlPedido);
        if(!$resPedido) return false;

        // 3) Actualizar t59OrdenServicioEntrega
        $sqlOSE = "UPDATE t59OrdenServicioEntrega
                SET Estado = 'Emitido'
                WHERE Id_OrdenPedido = $idPedido";
        $resOSE = mysqli_query($this->cn, $sqlOSE);
        if(!$resOSE) return false;

        return true;
    }


    public function listarGestionNoEntregados(){
        $sql = "SELECT g.Id_Gestion, g.Id_Consolidacion, g.Id_OrdenPedido, g.Id_Cliente,
                    c.NombreContactoSnap AS NombreCliente, g.FechaGestion, g.Estado
                FROM t172GestionNoEntregados g
                LEFT JOIN t71OrdenDirecEnvio c ON g.Id_OrdenPedido = c.Id_OrdenPedido
                ORDER BY g.FechaGestion DESC";
        $res = mysqli_query($this->cn, $sql);
        $datos = [];
        while($fila = mysqli_fetch_assoc($res)) $datos[] = $fila;
        return $datos;
    }

    public function listarPedidosPagados(){
        $sql = "SELECT Id_OrdenPedido, Id_Cliente, Fecha, Estado
                FROM t02OrdenPedido
                WHERE Estado = 'Pagado'
                ORDER BY Fecha DESC";
        $res = mysqli_query($this->cn, $sql);
        $datos = [];
        while($fila = mysqli_fetch_assoc($res)) $datos[] = $fila;
        return $datos;
    }

    public function listarOSEEmitidas(){
        $sql = "SELECT Id_OSE, Id_OrdenPedido, Estado
                FROM t59OrdenServicioEntrega
                WHERE Estado = 'Emitido'
                ORDER BY Id_OSE DESC";
        $res = mysqli_query($this->cn, $sql);
        $datos = [];
        while($fila = mysqli_fetch_assoc($res)) $datos[] = $fila;
        return $datos;
    }

    
}


