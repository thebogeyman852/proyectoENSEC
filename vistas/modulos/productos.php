<?php
$productos = ControladorProductos::ctrMostrarProductos();
$categorias = ControladorCategorias::ctrMostrarCategorias(null, null);
$escaparProducto = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
$obtenerImagenVisible = static function ($ruta) {
    if (
        !is_string($ruta) ||
        !preg_match('/^vistas\/img\/productos\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|webp)$/i', $ruta)
    ) {
        return null;
    }
    $archivo = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
        . str_replace("/", DIRECTORY_SEPARATOR, $ruta);
    return is_file($archivo) ? $ruta : null;
};
$camposDimension = array(
    "ancho" => "Ancho (cm)",
    "alto" => "Alto (cm)",
    "profundidad" => "Profundidad (cm)",
    "largo" => "Largo (cm)",
    "espesor" => "Espesor (mm o cm)",
    "peso" => "Peso (kg)"
);
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Productos</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensajeProducto"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensajeProducto"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparProducto($_SESSION["mensajeProducto"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensajeProducto"]); ?>
    <?php } ?>

    <div class="card mb-3">
      <div class="card-header">Agregar producto</div>
      <div class="card-body">
        <?php if (!$categorias) { ?>
          <div class="alert alert-warning">Agrega primero una categoría para poder registrar productos.</div>
        <?php } else { ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="accionProducto" value="crear">
            <input type="hidden" name="csrf_token" value="<?php echo $escaparProducto($_SESSION["csrf_token"] ?? ""); ?>">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Categoría</label>
                <select name="idcategoria" class="form-control" required>
                  <option value="">Seleccionar</option>
                  <?php foreach ($categorias as $categoria) { ?>
                    <option value="<?php echo (int) $categoria["idcategoria"]; ?>"><?php echo $escaparProducto($categoria["nombre"]); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group col-md-3">
                <label>Código</label>
                <input type="text" name="codigo" class="form-control" maxlength="50" required>
              </div>
              <div class="form-group col-md-6">
                <label>Descripción / nombre</label>
                <input type="text" name="descripcion" class="form-control" maxlength="255" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-3">
                <label>Stock de producto terminado (solo lectura)</label>
                <p class="form-control-static">Los productos nuevos comienzan con stock cero. Este dato es independiente del inventario de materiales.</p>
              </div>
              <div class="form-group col-md-3">
                <label>Precio de venta</label>
                <input type="number" name="precio_venta" class="form-control" min="0.01" max="99999999.99" step="0.01" required>
              </div>
              <div class="form-group col-md-6">
                <label for="imagen-producto-nuevo">Imagen del producto (opcional)</label>
                <input type="file" id="imagen-producto-nuevo" name="imagenProducto" class="form-control-file" accept="image/png,image/jpeg,image/gif,image/webp">
                <small class="form-text text-muted">PNG, JPG, GIF o WEBP. Máximo 2 MB.</small>
                <img id="vista-previa-imagen-nueva" alt="Vista previa de la imagen" class="img-thumbnail mt-2" style="display:none; max-width:140px; max-height:110px">
              </div>
            </div>
            <div class="form-row">
              <?php foreach ($camposDimension as $campo => $etiqueta) { ?>
                <div class="form-group col-md-2">
                  <label><?php echo $escaparProducto($etiqueta); ?></label>
                  <input type="number" name="<?php echo $campo; ?>" class="form-control" min="0" max="99999999.99" step="0.01">
                </div>
              <?php } ?>
            </div>
            <button type="submit" class="btn btn-primary">Guardar producto</button>
          </form>
        <?php } ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Productos registrados</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Stock de producto terminado</th><th>Precio</th><th>Dimensiones y datos</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php foreach ($productos as $producto) { ?>
              <tr>
                <td><?php echo $escaparProducto($producto["codigo"]); ?></td>
                <td>
                  <?php $imagenVisible = $obtenerImagenVisible($producto["imagen"]); ?>
                  <?php if ($imagenVisible) { ?>
                    <img src="<?php echo $escaparProducto($imagenVisible); ?>" alt="<?php echo $escaparProducto($producto["descripcion"]); ?>" class="img-thumbnail thumb-image mb-2" style="width:60px; height:60px; object-fit:contain">
                    <br>
                  <?php } ?>
                  <?php echo $escaparProducto($producto["descripcion"]); ?>
                </td>
                <td><?php echo $escaparProducto($producto["categoria"]); ?></td>
                <td><?php echo (int) $producto["stock"]; ?></td>
                <td>$<?php echo number_format((float) $producto["precio_venta"], 2); ?></td>
                <td>
                  <?php foreach ($camposDimension as $campo => $etiqueta) { ?>
                    <?php if ($producto[$campo] !== null) { ?>
                      <small><?php echo $escaparProducto($etiqueta); ?>: <?php echo $escaparProducto($producto[$campo]); ?></small><br>
                    <?php } ?>
                  <?php } ?>
                </td>
                <td>
                  <details>
                    <summary>Editar</summary>
                    <?php if ($categorias) { ?>
                      <form method="post" enctype="multipart/form-data" class="mt-2">
                        <input type="hidden" name="accionProducto" value="editar">
                        <input type="hidden" name="idproducto" value="<?php echo (int) $producto["idproducto"]; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $escaparProducto($_SESSION["csrf_token"] ?? ""); ?>">
                        <div class="form-group">
                          <label>Categoría</label>
                          <select name="idcategoria" class="form-control" required>
                            <?php foreach ($categorias as $categoria) { ?>
                              <option value="<?php echo (int) $categoria["idcategoria"]; ?>" <?php echo (int) $categoria["idcategoria"] === (int) $producto["idcategoria"] ? "selected" : ""; ?>>
                                <?php echo $escaparProducto($categoria["nombre"]); ?>
                              </option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label>Código</label>
                          <input type="text" name="codigo" class="form-control" maxlength="50" value="<?php echo $escaparProducto($producto["codigo"]); ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Descripción</label>
                          <input type="text" name="descripcion" class="form-control" maxlength="255" value="<?php echo $escaparProducto($producto["descripcion"]); ?>" required>
                        </div>
                        <div class="form-group">
                          <label>Stock de producto terminado (solo lectura)</label>
                          <p class="form-control-static"><?php echo (int) $producto["stock"]; ?> (solo lectura)</p>
                          <small class="form-text text-muted">El CRUD de productos no modifica existencias; el inventario de materiales se administra por separado.</small>
                        </div>
                        <div class="form-group">
                          <label>Precio de venta</label>
                          <input type="number" name="precio_venta" class="form-control" min="0.01" max="99999999.99" step="0.01" value="<?php echo $escaparProducto($producto["precio_venta"]); ?>" required>
                        </div>
                        <div class="form-group">
                          <label for="imagen-producto-<?php echo (int) $producto["idproducto"]; ?>">Cambiar imagen</label>
                          <input type="file" id="imagen-producto-<?php echo (int) $producto["idproducto"]; ?>" name="imagenProducto" class="form-control-file" accept="image/png,image/jpeg,image/gif,image/webp">
                          <small class="form-text text-muted">PNG, JPG, GIF o WEBP. Máximo 2 MB. Si no seleccionas otra, se conserva la imagen actual.</small>
                          <?php $imagenActualVisible = $obtenerImagenVisible($producto["imagen"]); ?>
                          <img id="vista-previa-imagen-<?php echo (int) $producto["idproducto"]; ?>" src="<?php echo $escaparProducto($imagenActualVisible ?? ""); ?>" alt="Vista previa de <?php echo $escaparProducto($producto["descripcion"]); ?>" class="img-thumbnail mt-2" style="<?php echo $imagenActualVisible ? "" : "display:none;"; ?> max-width:120px; max-height:100px">
                          <?php if (!empty($producto["imagen"])) { ?>
                            <div class="form-check mt-2">
                              <input type="checkbox" id="eliminar-imagen-<?php echo (int) $producto["idproducto"]; ?>" name="eliminarImagen" value="1" class="form-check-input">
                              <label class="form-check-label" for="eliminar-imagen-<?php echo (int) $producto["idproducto"]; ?>">Quitar imagen actual</label>
                            </div>
                          <?php } ?>
                        </div>
                        <div class="form-row">
                          <?php foreach ($camposDimension as $campo => $etiqueta) { ?>
                            <div class="form-group col-md-6">
                              <label><?php echo $escaparProducto($etiqueta); ?></label>
                              <input type="number" name="<?php echo $campo; ?>" class="form-control" min="0" max="99999999.99" step="0.01" value="<?php echo $escaparProducto($producto[$campo]); ?>">
                            </div>
                          <?php } ?>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm">Guardar cambios</button>
                      </form>
                    <?php } ?>
                  </details>
                  <form method="post" class="mt-2" onsubmit="return confirm('¿Eliminar este producto? Los productos asociados a pedidos no se pueden eliminar.');">
                    <input type="hidden" name="accionProducto" value="eliminar">
                    <input type="hidden" name="idproducto" value="<?php echo (int) $producto["idproducto"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $escaparProducto($_SESSION["csrf_token"] ?? ""); ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<script>
  document.querySelectorAll('input[name="imagenProducto"]').forEach(function (campo) {
    campo.addEventListener("change", function () {
      var archivo = campo.files && campo.files[0];
      if (!archivo) return;
      var vistaPreviaId = campo.id === "imagen-producto-nuevo"
        ? "vista-previa-imagen-nueva"
        : "vista-previa-imagen-" + campo.id.replace("imagen-producto-", "");
      var vistaPrevia = document.getElementById(vistaPreviaId);
      if (!vistaPrevia || !archivo.type.match(/^image\/(png|jpeg|gif|webp)$/)) return;
      if (vistaPrevia.dataset.urlPrevia) URL.revokeObjectURL(vistaPrevia.dataset.urlPrevia);
      vistaPrevia.dataset.urlPrevia = URL.createObjectURL(archivo);
      vistaPrevia.src = vistaPrevia.dataset.urlPrevia;
      vistaPrevia.style.display = "inline-block";
      var quitarImagen = campo.form.querySelector('[name="eliminarImagen"]');
      if (quitarImagen) quitarImagen.checked = false;
    });
  });
</script>
