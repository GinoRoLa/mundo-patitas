<?php
include_once __DIR__ . '/../Modelo/IncidenciaEntrega.php';

class CUS26Negocio {
    private $dao;

    public function __construct() {
        $this->dao = new IncidenciaEntrega();
    }

    public function listarDistritos() {
        return $this->dao->listarDistritos();
    }

    public function listarPedidosPorDistrito($idDistrito) {
        return $this->dao->listarPedidosPorDistrito($idDistrito);
    }

    public function registrarIncidencia($data) {
        return $this->dao->registrarIncidencia($data);
    }

    public function listarIncidencias() {
        return $this->dao->listarIncidencias();
    }
}
?>
