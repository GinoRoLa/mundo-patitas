<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$cn = new PDO('mysql:host=localhost;dbname=bd_mundopatitas', 'root', 'jasas');
//Hagan los cambios que deseen, pero haganlo en su servidor xamp, no aca, no les va a correr
// C:\xampp\htdocs\ServicioWebTaller\dbConexion.php   - ruta en su pc para hacer los cambios

?>