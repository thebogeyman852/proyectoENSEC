<?php
$escaparCategoria = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Administrar Categorías</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="inicio">Inicio</a></li>
            <li class="breadcrumb-item active">Categorías</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCategoria">
            <i class="mdi mdi-plus"></i> Agregar Categoría
          </button>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped dt-responsive tablas" width="100%">
            <thead>
              <tr>
                <th style="width:10px">#</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th style="width:90px">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $item = null;
              $valor = null;
              $categorias = ControladorCategorias::ctrMostrarCategorias($item, $valor);

              foreach ($categorias as $key => $value) {
                echo '<tr>
                        <td>'.($key+1).'</td>
                        <td>'.$escaparCategoria($value["nombre"]).'</td>
                        <td>'.$escaparCategoria($value["descripcion"]).'</td>
                        <td>
                          <div class="btn-group">
                            <button class="btn btn-warning btnEditarCategoria" idCategoria="'.$value["idcategoria"].'" data-toggle="modal" data-target="#modalEditarCategoria"><i class="mdi mdi-pencil"></i></button>
                            <button class="btn btn-danger btnEliminarCategoria" idCategoria="'.$value["idcategoria"].'"><i class="mdi mdi-delete"></i></button>
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
MODAL AGREGAR CATEGORÍA
======================================= -->
<div id="modalAgregarCategoria" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header" style="background:#3f51b5; color:white">
          <h4 class="modal-title">Agregar Categoría</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="box-body">
            
            <!-- Entrada del Nombre -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-folder-outline"></i></span>
                </div>
                <input type="text" class="form-control input-lg" name="nuevaCategoria" placeholder="Ingresar categoría" required>
              </div>
            </div>

            <!-- Entrada de la Descripción -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-comment-text-outline"></i></span>
                </div>
                <textarea class="form-control" name="nuevaDescripcion" rows="3" placeholder="Ingresar descripción"></textarea>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar categoría</button>
        </div>
        <?php
          $crearCategoria = new ControladorCategorias();
          $crearCategoria->ctrCrearCategoria();
        ?>
      </form>
    </div>
  </div>
</div>

<!-- =======================================
MODAL EDITAR CATEGORÍA
======================================= -->
<div id="modalEditarCategoria" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header" style="background:#ffc107; color:white">
          <h4 class="modal-title">Editar Categoría</h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="box-body">
            
            <!-- Entrada del Nombre -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-folder-outline"></i></span>
                </div>
                <input type="text" class="form-control input-lg" id="editarCategoria" name="editarCategoria" required>
                <input type="hidden" id="idCategoria" name="idCategoria">
              </div>
            </div>

            <!-- Entrada de la Descripción -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="mdi mdi-comment-text-outline"></i></span>
                </div>
                <textarea class="form-control" id="editarDescripcion" name="editarDescripcion" rows="3"></textarea>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
        <?php
          $editarCategoria = new ControladorCategorias();
          $editarCategoria->ctrEditarCategoria();
        ?>
      </form>
    </div>
  </div>
</div>

<?php
  $borrarCategoria = new ControladorCategorias();
  $borrarCategoria->ctrBorrarCategoria();
?>