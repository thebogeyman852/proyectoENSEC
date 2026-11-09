<?php
ControladorMovimientos::ctrProcesarMovimiento();
$movimientos = ControladorMovimientos::ctrMostrarMovimientos();
$herramientas = ControladorHerramientas::ctrMostrarHerramientas();
$escaparMovimiento = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Movimientos de inventario</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparMovimiento($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Registrar movimiento de herramienta</div>
      <div class="card-body">
        <?php if (!$herramientas) { ?>
          <p>Registra una herramienta antes de ingresar movimientos de inventario.</p>
        <?php } else { ?>
          <form method="post" class="form-row">
            <div class="form-group col-md-5">
              <label for="movimiento-herramienta">Herramienta</label>
              <select id="movimiento-herramienta" name="idHerramienta" class="form-control" required>
                <option value="">Seleccionar herramienta</option>
                <?php foreach ($herramientas as $herramienta) { ?>
                  <option value="<?php echo (int) $herramienta["idherramienta"]; ?>">
                    <?php echo $escaparMovimiento($herramienta["nombre"]); ?> (stock: <?php echo (int) $herramienta["stock"]; ?>)
                  </option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="movimiento-tipo">Tipo</label>
              <select id="movimiento-tipo" name="tipoMovimiento" class="form-control" required>
                <option value="ENTRADA">Entrada</option>
                <option value="SALIDA">Salida</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="movimiento-cantidad">Cantidad</label>
              <input id="movimiento-cantidad" type="number" name="cantidadMovimiento" class="form-control" min="1" step="1" required>
            </div>
            <div class="col-md-2 align-self-end mb-3">
              <button type="submit" class="btn btn-primary">Registrar</button>
            </div>
          </form>
        <?php } ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Historial (los movimientos son registros permanentes)</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>Herramienta</th><th>Movimiento</th><th>Cantidad</th><th>Usuario</th></tr></thead>
          <tbody>
            <?php foreach ($movimientos as $movimiento) { ?>
              <tr>
                <td><?php echo $escaparMovimiento($movimiento["fecha"]); ?></td>
                <td><?php echo $escaparMovimiento($movimiento["nombre_herramienta"] ?: "Herramienta eliminada"); ?></td>
                <td><?php echo $escaparMovimiento($movimiento["tipo_movimiento"]); ?></td>
                <td><?php echo (int) $movimiento["cantidad"]; ?></td>
                <td><?php echo $escaparMovimiento($movimiento["nombre_usuario"]); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
