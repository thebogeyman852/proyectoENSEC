<?php
$categorias = ControladorCategorias::ctrMostrarCategorias(null, null);
$escaparCategoria = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Administrar Categorías</h1>
    <ol class="breadcrumb">
      <li><a href="index.php?ruta=inicio">Inicio</a></li>
      <li class="active">Categorías</li>
    </ol>
  </section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparCategoria($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card">
      <div class="card-header">Agregar categoría</div>
      <div class="card-body">
        <form method="post" class="form-row">
          <input type="hidden" name="accionCategoria" value="crear">
          <input type="hidden" name="csrf_token" value="<?php echo $escaparCategoria($_SESSION["csrf_token"] ?? ""); ?>">
          <div class="form-group col-md-4">
            <label for="nueva-categoria">Nombre</label>
            <input id="nueva-categoria" type="text" name="nombre" class="form-control" maxlength="100" required>
          </div>
          <div class="form-group col-md-6">
            <label for="nueva-descripcion">Descripción</label>
            <input id="nueva-descripcion" type="text" name="descripcion" class="form-control">
          </div>
          <div class="col-md-2 align-self-end mb-3">
            <button type="submit" class="btn btn-primary">Agregar</button>
          </div>
        </form>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if (!$categorias) { ?>
              <tr><td colspan="4" class="text-center">No hay categorías registradas.</td></tr>
            <?php } else { ?>
              <?php foreach ($categorias as $indice => $categoria) {
                $id = (int) $categoria["idcategoria"];
              ?>
                <tr>
                  <td><?php echo $indice + 1; ?></td>
                  <td colspan="2">
                    <form id="editar-categoria-<?php echo $id; ?>" method="post">
                      <input type="hidden" name="accionCategoria" value="editar">
                      <input type="hidden" name="idcategoria" value="<?php echo $id; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo $escaparCategoria($_SESSION["csrf_token"] ?? ""); ?>">
                      <label class="sr-only" for="nombre-categoria-<?php echo $id; ?>">Nombre</label>
                      <input id="nombre-categoria-<?php echo $id; ?>" type="text" name="nombre" class="form-control mb-2" value="<?php echo $escaparCategoria($categoria["nombre"]); ?>" maxlength="100" required>
                      <label class="sr-only" for="descripcion-categoria-<?php echo $id; ?>">Descripción</label>
                      <textarea id="descripcion-categoria-<?php echo $id; ?>" name="descripcion" class="form-control" rows="2"><?php echo $escaparCategoria($categoria["descripcion"]); ?></textarea>
                    </form>
                  </td>
                  <td>
                    <button type="submit" form="editar-categoria-<?php echo $id; ?>" class="btn btn-warning btn-sm">Guardar</button>
                    <form method="post" class="mt-1" onsubmit="return confirm('¿Eliminar esta categoría? Los productos relacionados impedirán la eliminación.');">
                      <input type="hidden" name="accionCategoria" value="eliminar">
                      <input type="hidden" name="idcategoria" value="<?php echo $id; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo $escaparCategoria($_SESSION["csrf_token"] ?? ""); ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
