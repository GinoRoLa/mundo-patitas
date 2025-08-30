<?php
include_once 'Conexion.php';
class Negocio {
    
    //LISTA DE RUTAS
    function lisRutas(){
        $obj= new Conexion();
        $sql= "select rutcod,rutnom,pago_cho, rutdes from ruta";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
    
    //LISTA DE VIAJES POR RUTA
    function lisViajes($codruta){
        $obj= new Conexion();
        $sql= "select vianro,busnro,RUTCOD,idcod,viahrs,viafch,cosvia from viaje where RUTCOD='$codruta';";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
    
    //LISTA DE PASAJEROS POR VIAJE
    function lisPasajero($codviaje){
        $obj= new Conexion();
        $sql= "select bolnro,VIANRO, nom_pas, nro_asi, tipo,pago from pasajeros where VIANRO=$codviaje;";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
    
    //GRABAR NUEVOS PASAJEROS
    function adicion($codviaje,$nomp,$asiento,$tp,$pg){
        $obj= new Conexion();
        $sql= "call SPAGPASAJERO('$codviaje','$nomp','$asiento','$tp',$pg)";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
    }
    
    //ANULAR PASAJEROS
    function anula($cod){
        $obj= new Conexion();
        $sql= "call spanula('$cod')";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
    }
    
    //BUSCAR VIAJES POR CHOFER
    function viajesChofer($cod){
        $obj= new Conexion();
        $sql= "CALL SPVIACHOFER('$cod');";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;                                                                                                                        
    }
    
    //LISTA DE CHOFERES
    function lisChofer(){
        $obj= new Conexion();
        $sql= "select idcod, chonom, chofin, chocat, chosba, chopais, choreg, chodes from chofer";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
    
    //LISTA DE VIAJES POR RUTA
    function lisViaRuta(){
        $obj= new Conexion();
        $sql= "select rutnom, count(*) cantidad from ruta r join viaje v on r.RUTCOD=v.RUTCOD group by rutnom";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
    
    //LISTA DE VIAJES POR RUTA
    function lis5rutasMasVisitadas(){
        $obj= new Conexion();
        $sql= "select r.RUTCOD,RUTNOM,RUTDES, count(*) cantidad from ruta r join viaje  v where r.RUTCOD=v.RUTCOD group by r.RUTCOD order by 4 DESC LIMIT 5;";
        $res= mysqli_query($obj->conecta(), $sql) or die(mysqli_error($obj->conecta()));
        $vec=array();
        while($f= mysqli_fetch_array($res)){
            $vec[]=$f;
        }
        return $vec;
    }
}
