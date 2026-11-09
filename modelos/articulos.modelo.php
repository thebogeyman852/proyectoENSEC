<?php

require_once "conexion.php";

class ModeloArticulos {

    // ===============================================
    // MOSTRAR ARTÍCULOS
    // ===============================================
    static public function mdlMostrarArticulos($tabla, $item, $valor) {
        if ($item != null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");
            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } else {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $stmt->close();
        $stmt = null;
    }

    // ===============================================
    // INGRESAR ARTÍCULO (CON IMAGEN / MODELO)
    // ===============================================
    static public function mdlIngresarArticulo($tabla, $datos){

    $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(idcategoria, codigo, nombre, stock, precio_venta, material, tamano, proporcion, profundidad, piezas, acabado, descripcion, imagen) VALUES (:idcategoria, :codigo, :nombre, :stock, :precio_venta, :material, :tamano, :proporcion, :profundidad, :piezas, :acabado, :descripcion, :imagen)");

    $stmt->bindParam(":idcategoria", $datos["idcategoria"], PDO::PARAM_INT);
    $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
    $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
    $stmt->bindParam(":stock", $datos["stock"], PDO::PARAM_INT);
    $stmt->bindParam(":precio_venta", $datos["precio_venta"], PDO::PARAM_STR);
    $stmt->bindParam(":material", $datos["material"], PDO::PARAM_STR);
    $stmt->bindParam(":tamano", $datos["tamano"], PDO::PARAM_STR);
    $stmt->bindParam(":proporcion", $datos["proporcion"], PDO::PARAM_STR);
    $stmt->bindParam(":profundidad", $datos["profundidad"], PDO::PARAM_STR);
    $stmt->bindParam(":piezas", $datos["piezas"], PDO::PARAM_INT);
    $stmt->bindParam(":acabado", $datos["acabado"], PDO::PARAM_STR);
    $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
    $stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);

    if($stmt->execute()){
        return "ok";
    }else{
        return "error";
    }

    $stmt->close();
    $stmt = null;
}

   static public function mdlActualizarArticulo($tabla, $datos){

        $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET idcategoria = :idcategoria, codigo = :codigo, nombre = :nombre, stock = :stock, precio_venta = :precio_venta, material = :material, tamano = :tamano, proporcion = :proporcion, profundidad = :profundidad, piezas = :piezas, acabado = :acabado, descripcion = :descripcion, imagen = :imagen WHERE idarticulo = :idarticulo");

        $stmt->bindParam(":idarticulo", $datos["idarticulo"], PDO::PARAM_INT);
        $stmt->bindParam(":idcategoria", $datos["idcategoria"], PDO::PARAM_INT);
        $stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindParam(":stock", $datos["stock"], PDO::PARAM_INT);
        $stmt->bindParam(":precio_venta", $datos["precio_venta"], PDO::PARAM_STR);
        $stmt->bindParam(":material", $datos["material"], PDO::PARAM_STR);
        $stmt->bindParam(":tamano", $datos["tamano"], PDO::PARAM_STR);
        $stmt->bindParam(":proporcion", $datos["proporcion"], PDO::PARAM_STR);
        $stmt->bindParam(":profundidad", $datos["profundidad"], PDO::PARAM_STR);
        $stmt->bindParam(":piezas", $datos["piezas"], PDO::PARAM_INT);
        $stmt->bindParam(":acabado", $datos["acabado"], PDO::PARAM_STR);
        $stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
        $stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);

        if($stmt->execute()){
            return "ok";
        }else{
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }

    /* ===============================================
    BORRAR ARTÍCULO
    =============================================== */
    static public function mdlBorrarArticulo($tabla, $datos){

        $stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE idarticulo = :idarticulo");

        $stmt->bindParam(":idarticulo", $datos, PDO::PARAM_INT);

        if($stmt->execute()){
            return "ok";
        }else{
            return "error";
        }

        $stmt->close();
        $stmt = null;
    }
}