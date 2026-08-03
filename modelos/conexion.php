<?php 

class Conexion{
static public function conectar(){
    $link = new PDO("mysql:host=localhost; dbname=venta2026", 
    "root", 
    "");
    //cotejamiento utf8
    $link-> exec("set names utf8");
    return $link;
}
}

?>
