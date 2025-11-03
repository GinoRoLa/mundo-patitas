<?php

class Conexion {
    private $cn=null;
    
    function conecta(){
        if($this->cn==null){
            $this->cn= mysqli_connect("localhost", "root", "12345", "mundo_patitas3","3306");
        }
        return $this->cn;
    }
    /*
        function conecta(){
        if($this->cn==null){
            $this->cn= mysqli_connect("localhost", "root", "jasas", "bd_mundopatitas","3306");
        }
        return $this->cn;
    */
} 
