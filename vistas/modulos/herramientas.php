<?php
ControladorHerramientas::ctrProcesarHerramienta();
$herramientas = ControladorHerramientas::ctrMostrarHerramientas();
$escaparHerramienta = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Administrar herramientas</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparHerramienta($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Agregar herramienta</div>
      <div class="card-body">
        <form method="post" class="form-row">
          <input type="hidden" name="accionHerramienta" value="crear">
          <div class="form-group col-md-5">
            <label for="herramienta-nombre">Nombre</label>
            <input id="herramienta-nombre" class="form-control" name="nombre" maxlength="150" required>
          </div>
          <div class="form-group col-md-4">
            <label for="herramienta-estado">Estado</label>
            <input id="herramienta-estado" class="form-control" name="estado" value="Disponible" maxlength="50" required>
          </div>
          <div class="form-group col-md-3">
            <label for="herramienta-stock">Stock inicial</label>
            <input id="herramienta-stock" type="number" class="form-control" name="stock" min="0" value="1" required>
          </div>
          <div class="col-12"><button class="btn btn-primary" type="submit">Guardar herramienta</button></div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Nombre</th><th>Estado</th><th>Stock</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($herramientas as $herramienta) {
              $formId = "editar-herramienta-" . (int) $herramienta["idherramienta"];
            ?>
              <tr>
                <td><input class="form-control" name="nombre" form="<?php echo $formId; ?>" value="<?php echo $escaparHerramienta($herramienta["nombre"]); ?>" maxlength="150" required></td>
                <td><input class="form-control" name="estado" form="<?php echo $formId; ?>" value="<?php echo $escaparHerramienta($herramienta["estado"]); ?>" maxlength="50" required></td>
                <td><input type="number" class="form-control" name="stock" form="<?php echo $formId; ?>" value="<?php echo (int) $herramienta["stock"]; ?>" min="0" required></td>
                <td>
                  <form id="<?php echo $formId; ?>" method="post">
                    <input type="hidden" name="accionHerramienta" value="editar">
                    <input type="hidden" name="idherramienta" value="<?php echo (int) $herramienta["idherramienta"]; ?>">
                    <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('¿Eliminar esta herramienta?');">
                    <input type="hidden" name="accionHerramienta" value="eliminar">
                    <input type="hidden" name="idherramienta" value="<?php echo (int) $herramienta["idherramienta"]; ?>">
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
