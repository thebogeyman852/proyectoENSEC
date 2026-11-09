<?php
ControladorMateriales::ctrProcesarMaterial();
$materiales = ControladorMateriales::ctrMostrarMateriales();
$tiposMaterial = ControladorCatalogos::ctrMostrar("tipos-material");
$proveedores = ControladorCatalogos::ctrMostrar("proveedores");
$escaparMaterial = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Administrar materiales</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparMaterial($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Agregar material</div>
      <div class="card-body">
        <?php if (!$tiposMaterial || !$proveedores) { ?>
          <div class="alert alert-warning">
            Registra primero un tipo de material y un proveedor en
            <a href="index.php?ruta=tipos-material">Tipos de material</a> y
            <a href="index.php?ruta=proveedores">Proveedores</a>.
          </div>
        <?php } else { ?>
        <form method="post" class="form-row">
          <input type="hidden" name="accionMaterial" value="crear">
          <div class="form-group col-md-3">
            <label for="material-nombre">Nombre</label>
            <input id="material-nombre" class="form-control" name="nombre" maxlength="150" required>
          </div>
          <div class="form-group col-md-3">
            <label for="material-tipo">Tipo</label>
            <select id="material-tipo" class="form-control" name="idtipo_material" required>
              <option value="">Seleccionar tipo</option>
              <?php foreach ($tiposMaterial as $tipo) { ?>
                <option value="<?php echo (int) $tipo["idtipo_material"]; ?>"><?php echo $escaparMaterial($tipo["nombre"]); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label for="material-proveedor">Proveedor</label>
            <select id="material-proveedor" class="form-control" name="idproveedor" required>
              <option value="">Seleccionar proveedor</option>
              <?php foreach ($proveedores as $proveedor) { ?>
                <option value="<?php echo (int) $proveedor["idproveedor"]; ?>"><?php echo $escaparMaterial($proveedor["nombre_proveedor"]); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-1">
            <label for="material-unidad">Unidad</label>
            <input id="material-unidad" class="form-control" name="unidad_medida" maxlength="50" required>
          </div>
          <div class="form-group col-md-2">
            <label for="material-stock">Stock inicial</label>
            <input id="material-stock" type="number" class="form-control" name="stock_actual" min="0" max="99999999.99" step="0.01" value="0" required>
          </div>
          <div class="col-12"><button class="btn btn-primary" type="submit">Guardar material</button></div>
        </form>
        <?php } ?>
      </div>
    </div>
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Nombre</th><th>Tipo</th><th>Proveedor</th><th>Unidad</th><th>Stock actual</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($materiales as $material) {
              $formId = "editar-material-" . (int) $material["idmaterial"];
            ?>
              <tr>
                <td><input class="form-control" name="nombre" form="<?php echo $formId; ?>" value="<?php echo $escaparMaterial($material["nombre"]); ?>" maxlength="150" required></td>
                <td>
                  <select class="form-control" name="idtipo_material" form="<?php echo $formId; ?>" required>
                    <?php foreach ($tiposMaterial as $tipo) { ?>
                      <option value="<?php echo (int) $tipo["idtipo_material"]; ?>" <?php echo (int) $tipo["idtipo_material"] === (int) $material["idtipo_material"] ? "selected" : ""; ?>><?php echo $escaparMaterial($tipo["nombre"]); ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <select class="form-control" name="idproveedor" form="<?php echo $formId; ?>" required>
                    <?php foreach ($proveedores as $proveedor) { ?>
                      <option value="<?php echo (int) $proveedor["idproveedor"]; ?>" <?php echo (int) $proveedor["idproveedor"] === (int) $material["idproveedor"] ? "selected" : ""; ?>><?php echo $escaparMaterial($proveedor["nombre_proveedor"]); ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td><input class="form-control" name="unidad_medida" form="<?php echo $formId; ?>" value="<?php echo $escaparMaterial($material["unidad_medida"]); ?>" maxlength="50" required></td>
                <td><input type="number" class="form-control" name="stock_actual" form="<?php echo $formId; ?>" value="<?php echo $escaparMaterial($material["stock_actual"]); ?>" min="0" max="99999999.99" step="0.01" required></td>
                <td>
                  <form id="<?php echo $formId; ?>" method="post">
                    <input type="hidden" name="accionMaterial" value="editar">
                    <input type="hidden" name="idMaterial" value="<?php echo (int) $material["idmaterial"]; ?>">
                    <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('¿Eliminar este material?');">
                    <input type="hidden" name="accionMaterial" value="eliminar">
                    <input type="hidden" name="idMaterial" value="<?php echo (int) $material["idmaterial"]; ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
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
