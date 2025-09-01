<?php

class Conexion {
    private $cn=null;
    
    function conecta(){
        if($this->cn==null){
            $this->cn= mysqli_connect("localhost", "root", "12345", "mundo_patitas2","3306");
        }
        return $this->cn;
    }
}
