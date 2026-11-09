<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Administrar Productos y Herramientas</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="inicio">Inicio</a></li>
            <li class="breadcrumb-item active">Productos</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarArticulo">
            <i class="mdi mdi-plus"></i> Agregar Artículo
          </button>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped dt-responsive tablas" width="100%">
            <thead>
              <tr>
               <th style="width:10px">#</th>
               <th>Imagen</th>
               <th>Código</th>
               <th>Nombre</th>
               <th>Categoría</th>
               <th>Stock</th>
               <th>Precio</th>
               <th>Detalles Técnicos</th>
               <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php
$item = null;
$valor = null;
$articulos = ControladorArticulos::ctrMostrarArticulos($item, $valor);

foreach ($articulos as $key => $value) {
    // Obtener nombre de la categoría relacionada
    $itemCat = "idcategoria";
    $valorCat = $value["idcategoria"];
    $categoria = ControladorCategorias::ctrMostrarCategorias($itemCat, $valorCat);

    echo '<tr>
            <td>'.($key+1).'</td>
            <td>';
            if($value["imagen"] != ""){
                echo '<img src="'.$value["imagen"].'" class="img-thumbnail" width="40px">';
            } else {
                echo '<img src="vistas/img/productos/default.png" class="img-thumbnail" width="40px">';
            }
    echo '</td>
            <td>'.$value["codigo"].'</td>
            <td>'.$value["nombre"].'</td>
            <td>'.$categoria["nombre"].'</td>
            <td>'.$value["stock"].'</td>
            <td>Bs. '.$value["precio_venta"].'</td>
            <td>
              <small>
                <b>Material:</b> '.$value["material"].'<br>
                <b>Tamaño:</b> '.$value["tamaño"].'<br>
                <b>Prop.:</b> '.$value["proporcion"].'<br>
                <b>Prof.:</b> '.$value["profundidad"].'<br>
                <b>Piezas:</b> '.$value["piezas"].'<br>
                <b>Acabado:</b> '.$value["acabado"].'
              </small>
            </td>
            <td>
              <div class="btn-group">
                <button class="btn btn-warning btnEditarArticulo" idArticulo="'.$value["idarticulo"].'" data-toggle="modal" data-target="#modalEditarArticulo"><i class="mdi mdi-pencil"></i></button>
                <button class="btn btn-danger btnEliminarArticulo" idArticulo="'.$value["idarticulo"].'"><i class="mdi mdi-delete"></i></button>
              </div>
            </td>
          </tr>';
}
?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- =======================================
MODAL AGREGAR ARTÍCULO
======================================= -->
<div id="modalAgregarArticulo" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Importante: enctype para permitir subida de archivos/imágenes/modelos 3D -->
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header" style="background:#3f51b5; color:white">
          <h4 class="modal-title">Agregar Artículo / Herramienta</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="box-body">
            
            <!-- Seleccionar Categoría -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-folder-outline"></i></span>
                </div>
                <select class="form-control input-lg" name="nuevaCategoria" required>
                  <option value="">Selecionar categoría</option>
                  <?php
                  $categorias = ControladorCategorias::ctrMostrarCategorias(null, null);
                  foreach ($categorias as $key => $value) {
                    echo '<option value="'.$value["idcategoria"].'">'.$value["nombre"].'</option>';
                  }
                  ?>
                </select>
              </div>
            </div>

            <!-- Código del Artículo -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-barcode"></i></span>
                </div>
                <input type="text" class="form-control input-lg" name="nuevoCodigo" placeholder="Ingresar código" required>
              </div>
            </div>

            <!-- Nombre -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-format-title"></i></span>
                </div>
                <input type="text" class="form-control input-lg" name="nuevoNombre" placeholder="Ingresar nombre" required>
              </div>
            </div>

            <!-- Stock -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-dropbox"></i></span>
                </div>
                <input type="number" class="form-control input-lg" name="nuevoStock" min="0" placeholder="Stock inicial" required>
              </div>
            </div>

            <!-- Precio Venta -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-currency-usd"></i></span>
                </div>
                <input type="number" step="any" class="form-control input-lg" name="nuevoPrecioVenta" placeholder="Precio de venta" required>
              </div>
            </div>

            <!-- Descripción -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-comment-text-outline"></i></span>
                </div>
                <textarea class="form-control" name="nuevaDescripcion" rows="2" placeholder="Ingresar descripción o detalles técnicos"></textarea>
              </div>
            </div>

            <!-- Subir Imagen o Modelo 3D -->
            <div class="form-group">
              <panel class="panel">SUBIR IMAGEN / MODELO 3D</panel>
              <input type="file" class="form-control" name="nuevaImagen">
              <p class="help-block">Peso máximo del archivo 2MB (Formatos admitidos: JPG, PNG o archivos 3D compatibles)</p>
            </div>

          </div>
          <!-- ENTRADA PARA EL MATERIAL -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-cube"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoMaterial" placeholder="Ingresar material (ej: Madera Roble)" required>
              </div>
            </div>

            <!-- ENTRADA PARA EL TAMAÑO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-ruler"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoTamano" placeholder="Ingresar tamaño (ej: 120x80 cm)" required>
              </div>
            </div>

            <!-- ENTRADA PARA LA PROPORCIÓN -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-scale-balance"></i></span>
                <input type="text" class="form-control input-lg" name="nuevaProporcion" placeholder="Ingresar proporción (ej: Rectangular)" required>
              </div>
            </div>

            <!-- ENTRADA PARA LA PROFUNDIDAD -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-arrow-expand-down"></i></span>
                <input type="text" class="form-control input-lg" name="nuevaProfundidad" placeholder="Ingresar profundidad (ej: 45 cm)" required>
              </div>
            </div>

            <!-- ENTRADA PARA EL NÚMERO DE PIEZAS -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-puzzle"></i></span>
                <input type="number" class="form-control input-lg" name="nuevasPiezas" placeholder="Número de piezas (ej: 1 o 7)" min="1" required>
              </div>
            </div>

            <!-- ENTRADA PARA EL ACABADO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="mdi mdi-format-paint"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoAcabado" placeholder="Ingresar acabado (ej: Barniz mate / Nogal)" required>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar artículo</button>
        </div>
        <?php
          $crearArticulo = new ControladorArticulos();
          $crearArticulo->ctrCrearArticulo();
        ?>
      </form>
    </div>
  </div>
</div>