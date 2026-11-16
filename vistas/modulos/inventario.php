<?php
$filtros = array(
    "tipo_item" => $_GET["tipo_item"] ?? "",
    "tipo_movimiento" => $_GET["tipo_movimiento"] ?? "",
    "id_usuario" => $_GET["id_usuario"] ?? "",
    "id_item" => $_GET["id_item"] ?? "",
    "desde" => $_GET["desde"] ?? "",
    "hasta" => $_GET["hasta"] ?? ""
);
$movimientosKardex = ControladorInventario::ctrListarMovimientos($filtros);
$materialesKardex = ControladorInventario::ctrListarArticulos("material");
$herramientasKardex = ControladorInventario::ctrListarArticulos("herramienta");
$usuariosKardex = ControladorInventario::ctrUsuarios();
$escaparKardex = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
$tokenKardex = $escaparKardex($_SESSION["csrf_token"] ?? "");
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Kardex e inventario</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensajeInventario"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensajeInventario"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparKardex($_SESSION["mensajeInventario"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensajeInventario"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Registrar entrada o salida manual</div>
      <div class="card-body">
        <form method="post" class="form-row">
          <input type="hidden" name="accionInventario" value="movimiento">
          <input type="hidden" name="csrf_token" value="<?php echo $tokenKardex; ?>">
          <input type="hidden" name="id_solicitud" value="<?php echo bin2hex(random_bytes(16)); ?>">
          <div class="form-group col-md-3">
            <label for="tipo-item-movimiento">Tipo de artículo</label>
            <select id="tipo-item-movimiento" name="tipo_item" class="form-control" required>
              <option value="material">Material</option><option value="herramienta">Herramienta</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label for="id-item-movimiento">Artículo</label>
            <select id="id-item-movimiento" name="id_item" class="form-control" required>
              <?php foreach ($materialesKardex as $articulo) { ?>
                <option data-tipo="material" value="<?php echo (int) $articulo["id"]; ?>">
                  <?php echo $escaparKardex($articulo["nombre"] . " — stock " . $articulo["stock"] . " " . $articulo["unidad"]); ?>
                </option>
              <?php } ?>
              <?php foreach ($herramientasKardex as $articulo) { ?>
                <option data-tipo="herramienta" value="<?php echo (int) $articulo["id"]; ?>">
                  <?php echo $escaparKardex($articulo["nombre"] . " — stock " . $articulo["stock"]); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="tipo-movimiento">Movimiento</label>
            <select id="tipo-movimiento" name="tipo_movimiento" class="form-control" required>
              <option value="entrada">Entrada</option><option value="salida">Salida</option>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label for="cantidad-movimiento">Cantidad</label>
            <input id="cantidad-movimiento" type="number" name="cantidad" class="form-control" min="0.01" step="0.01" required>
          </div>
          <div class="form-group col-md-10">
            <label for="motivo-movimiento">Motivo / documento relacionado</label>
            <input id="motivo-movimiento" name="motivo" class="form-control" maxlength="255" required>
          </div>
          <div class="col-md-2 align-self-end mb-3"><button class="btn btn-primary" type="submit">Registrar</button></div>
        </form>
      </div>
    </div>
    <?php if (($_SESSION["perfil"] ?? "") === "Administrador") { ?>
      <div class="card mb-3">
        <div class="card-header">Ajuste por conteo físico (solo administrador)</div>
        <div class="card-body">
          <form method="post" class="form-row">
            <input type="hidden" name="accionInventario" value="ajuste">
            <input type="hidden" name="csrf_token" value="<?php echo $tokenKardex; ?>">
            <input type="hidden" name="id_solicitud" value="<?php echo bin2hex(random_bytes(16)); ?>">
            <div class="form-group col-md-3">
              <label for="tipo-item-ajuste">Tipo de artículo</label>
              <select id="tipo-item-ajuste" name="tipo_item" class="form-control" required>
                <option value="material">Material</option><option value="herramienta">Herramienta</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label for="id-item-ajuste">Artículo</label>
              <select id="id-item-ajuste" name="id_item" class="form-control" required>
                <?php foreach ($materialesKardex as $articulo) { ?>
                  <option data-tipo="material" value="<?php echo (int) $articulo["id"]; ?>">
                    <?php echo $escaparKardex($articulo["nombre"] . " — sistema " . $articulo["stock"]); ?>
                  </option>
                <?php } ?>
                <?php foreach ($herramientasKardex as $articulo) { ?>
                  <option data-tipo="herramienta" value="<?php echo (int) $articulo["id"]; ?>">
                    <?php echo $escaparKardex($articulo["nombre"] . " — sistema " . $articulo["stock"]); ?>
                  </option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="stock-fisico">Stock contado</label>
              <input id="stock-fisico" type="number" name="stock_fisico" class="form-control" min="0" step="0.01" required>
            </div>
            <div class="form-group col-md-3">
              <label for="motivo-ajuste">Motivo</label>
              <input id="motivo-ajuste" name="motivo" class="form-control" maxlength="255" required>
            </div>
            <div class="col-12"><button class="btn btn-warning" type="submit">Guardar ajuste</button></div>
          </form>
        </div>
      </div>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Filtrar historial</div>
      <div class="card-body">
        <form method="get" class="form-row">
          <input type="hidden" name="ruta" value="inventario">
          <div class="form-group col-md-2">
            <label>Tipo de artículo</label>
            <select name="tipo_item" class="form-control">
              <option value="">Todos</option>
              <?php foreach (array("material" => "Material", "herramienta" => "Herramienta", "producto" => "Producto") as $valor => $etiqueta) { ?>
                <option value="<?php echo $valor; ?>" <?php echo $filtros["tipo_item"] === $valor ? "selected" : ""; ?>><?php echo $etiqueta; ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label>Movimiento</label>
            <select name="tipo_movimiento" class="form-control">
              <option value="">Todos</option>
              <?php foreach (array("entrada" => "Entrada", "salida" => "Salida") as $valor => $etiqueta) { ?>
                <option value="<?php echo $valor; ?>" <?php echo $filtros["tipo_movimiento"] === $valor ? "selected" : ""; ?>><?php echo $etiqueta; ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-2"><label>Desde</label><input type="date" name="desde" class="form-control" value="<?php echo $escaparKardex($filtros["desde"]); ?>"></div>
          <div class="form-group col-md-2"><label>Hasta</label><input type="date" name="hasta" class="form-control" value="<?php echo $escaparKardex($filtros["hasta"]); ?>"></div>
          <div class="form-group col-md-3">
            <label>Usuario</label>
            <select name="id_usuario" class="form-control">
              <option value="">Todos</option>
              <?php foreach ($usuariosKardex as $usuario) { ?>
                <option value="<?php echo (int) $usuario["idusuario"]; ?>" <?php echo (string) $filtros["id_usuario"] === (string) $usuario["idusuario"] ? "selected" : ""; ?>><?php echo $escaparKardex($usuario["nombre"]); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label>ID artículo</label>
            <input type="number" name="id_item" class="form-control" min="1" value="<?php echo $escaparKardex($filtros["id_item"]); ?>" placeholder="Opcional">
          </div>
          <div class="col-12"><button class="btn btn-secondary" type="submit">Filtrar</button> <a class="btn btn-light" href="index.php?ruta=inventario">Limpiar</a></div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Movimientos recientes (máximo 1.000 filas)</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>Tipo</th><th>Artículo</th><th>Movimiento</th><th>Cantidad</th><th>Stock anterior</th><th>Stock resultante</th><th>Usuario</th><th>Referencia</th><th>Motivo</th></tr></thead>
          <tbody>
            <?php if (!$movimientosKardex) { ?><tr><td colspan="10" class="text-center">No hay movimientos para mostrar.</td></tr><?php } ?>
            <?php foreach ($movimientosKardex as $movimiento) { ?>
              <tr>
                <td><?php echo $escaparKardex($movimiento["fecha"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["tipo_item"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["articulo"] ?? "Artículo no disponible"); ?></td>
                <td><?php echo $escaparKardex($movimiento["tipo_movimiento"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["cantidad"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["stock_anterior"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["stock_resultante"]); ?></td>
                <td><?php echo $escaparKardex($movimiento["usuario"] ?? "Usuario no disponible"); ?></td>
                <td><?php echo $escaparKardex($movimiento["referencia_tipo"] . ($movimiento["referencia_id"] ? " #" . $movimiento["referencia_id"] : "")); ?></td>
                <td><?php echo $escaparKardex($movimiento["motivo"]); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
<script>
  (function () {
    function filtrarArticulos(tipoId, articuloId) {
      var tipo = document.getElementById(tipoId);
      var articulos = document.getElementById(articuloId);
      if (!tipo || !articulos) return;
      Array.prototype.forEach.call(articulos.options, function (opcion) {
        opcion.hidden = opcion.dataset.tipo !== tipo.value;
        opcion.disabled = opcion.hidden;
      });
      var disponibles = Array.prototype.filter.call(articulos.options, function (opcion) {
        return !opcion.disabled;
      });
      articulos.value = disponibles.length ? disponibles[0].value : "";
    }
    [["tipo-item-movimiento", "id-item-movimiento"], ["tipo-item-ajuste", "id-item-ajuste"]]
      .forEach(function (ids) {
        var tipo = document.getElementById(ids[0]);
        if (!tipo) return;
        tipo.addEventListener("change", function () { filtrarArticulos(ids[0], ids[1]); });
        filtrarArticulos(ids[0], ids[1]);
      });
  })();
</script>
