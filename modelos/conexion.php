<?php 

class Conexion{
static public function conectar(){
    $link = new PDO("mysql:host=localhost;dbname=sistemainventario_v2;charset=utf8mb4",
    "root", 
    "");
    $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $link;
}
}

?>
