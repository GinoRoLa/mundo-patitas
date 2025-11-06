<?php
require_once __DIR__ . '/../Modelo/RequerimientoModel.php';

class CUS13Negocio {
    private $model;
    public function __construct(){
        $this->model = new RequerimientoModel();
    }

    // Obtener financiamiento del periodo actual
    public function obtenerFinanciamientoPeriodo($mes = null){
        return $this->model->obtenerFinanciamientoPeriodo($mes);
    }

    public function listarPartidasActivas(){
        return $this->model->listarPartidasActivas();
    }

    public function obtenerSaldoPartida($idPartida){
        return $this->model->obtenerSaldoDisponible($idPartida);
    }
//
    public function listarSolicitudesPendientes(){
        return $this->model->listarSolicitudesPendientes();
    }
//
    public function obtenerDetalleSolicitud($idReq){
        return $this->model->obtenerDetalleSolicitud($idReq);
    }
//
    public function evaluarYRegistrar($idReq, $idPartida, $criterioLabel = 'Precio+Rotacion+Proporcionalidad') {
        require_once __DIR__ . '/../Modelo/RequerimientoModel.php';
        $model = new RequerimientoModel();
        return $model->evaluarYRegistrar($idReq, $idPartida, $criterioLabel);
    }


}
?>
