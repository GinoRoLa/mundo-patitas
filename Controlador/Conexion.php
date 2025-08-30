<?php

class Conexion {
    private $cn=null;
    
    function conecta(){
        if($this->cn==null){
            $this->cn= mysqli_connect("localhost", "root", "", "mundo_patitas","3306");
        }
        return $this->cn;
    }
}
