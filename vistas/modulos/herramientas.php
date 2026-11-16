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
          <input type="hidden" name="csrf_token" value="<?php echo $escaparHerramienta($_SESSION["csrf_token"] ?? ""); ?>">
          <div class="form-group col-md-3">
            <label for="herramienta-codigo">Código</label>
            <input id="herramienta-codigo" class="form-control" name="codigo" maxlength="50" required>
          </div>
          <div class="form-group col-md-4">
            <label for="herramienta-nombre">Nombre</label>
            <input id="herramienta-nombre" class="form-control" name="nombre" maxlength="150" required>
          </div>
          <div class="form-group col-md-3">
            <label for="herramienta-estado">Estado</label>
            <input id="herramienta-estado" class="form-control" name="estado" value="Disponible" maxlength="50" required>
          </div>
          <div class="form-group col-md-2">
            <label>Stock inicial</label>
            <p class="form-control-static">0. Registra una entrada desde Movimientos para añadir existencias.</p>
          </div>
          <div class="col-12"><button class="btn btn-primary" type="submit">Guardar herramienta</button></div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Código</th><th>Nombre</th><th>Estado</th><th>Stock (solo lectura)</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if (!$herramientas) { ?>
              <tr><td colspan="5" class="text-center">No hay herramientas registradas.</td></tr>
            <?php } ?>
            <?php foreach ($herramientas as $herramienta) {
              $formId = "editar-herramienta-" . (int) $herramienta["idherramienta"];
            ?>
              <tr>
                <td><input class="form-control" name="codigo" form="<?php echo $formId; ?>" value="<?php echo $escaparHerramienta($herramienta["codigo"] ?? ""); ?>" placeholder="Asignar código" maxlength="50" required></td>
                <td><input class="form-control" name="nombre" form="<?php echo $formId; ?>" value="<?php echo $escaparHerramienta($herramienta["nombre"]); ?>" maxlength="150" required></td>
                <td><input class="form-control" name="estado" form="<?php echo $formId; ?>" value="<?php echo $escaparHerramienta($herramienta["estado"]); ?>" maxlength="50" required></td>
                <td><?php echo (int) $herramienta["stock"]; ?></td>
                <td>
                  <form id="<?php echo $formId; ?>" method="post">
                    <input type="hidden" name="accionHerramienta" value="editar">
                    <input type="hidden" name="idherramienta" value="<?php echo (int) $herramienta["idherramienta"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $escaparHerramienta($_SESSION["csrf_token"] ?? ""); ?>">
                    <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('¿Eliminar esta herramienta?');">
                    <input type="hidden" name="accionHerramienta" value="eliminar">
                    <input type="hidden" name="idherramienta" value="<?php echo (int) $herramienta["idherramienta"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $escaparHerramienta($_SESSION["csrf_token"] ?? ""); ?>">
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
