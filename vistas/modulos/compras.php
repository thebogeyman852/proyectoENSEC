<?php
$compras = ControladorCompras::ctrListarCompras();
$proveedoresCompra = ModeloCatalogos::mdlMostrar("proveedores");
$materialesCompra = ModeloInventario::mdlListarArticulos("material");
$herramientasCompra = ModeloInventario::mdlListarArticulos("herramienta");
$detalleCompraSeleccionada = array();
$idCompraVista = filter_var($_GET["detalle"] ?? null, FILTER_VALIDATE_INT);
if ($idCompraVista !== false && $idCompraVista > 0) {
  $detalleCompraSeleccionada = ControladorCompras::ctrDetalle($idCompraVista);
}
$escaparCompra = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Compras</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensajeCompra"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensajeCompra"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparCompra($_SESSION["mensajeCompra"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensajeCompra"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Registrar compra</div>
      <div class="card-body">
        <?php if (!$proveedoresCompra || (!$materialesCompra && !$herramientasCompra)) { ?>
          <div class="alert alert-warning">Registra al menos un proveedor y un material o herramienta antes de ingresar compras.</div>
        <?php } else { ?>
          <form method="post" id="form-compra">
            <input type="hidden" name="accionCompra" value="crear">
            <input type="hidden" name="csrf_token" value="<?php echo $escaparCompra($_SESSION["csrf_token"] ?? ""); ?>">
            <input type="hidden" name="id_solicitud" value="<?php echo bin2hex(random_bytes(16)); ?>">
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="proveedor-compra">Proveedor</label>
                <select id="proveedor-compra" name="id_proveedor" class="form-control" required>
                  <option value="">Seleccionar</option>
                  <?php foreach ($proveedoresCompra as $proveedor) { ?>
                    <option value="<?php echo (int) $proveedor["idproveedor"]; ?>"><?php echo $escaparCompra($proveedor["nombre_proveedor"]); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group col-md-3"><label for="fecha-compra">Fecha</label><input id="fecha-compra" type="date" name="fecha" class="form-control" value="<?php echo date("Y-m-d"); ?>" required></div>
              <div class="form-group col-md-5"><label for="documento-compra">N.º de documento</label><input id="documento-compra" name="nro_documento" class="form-control" maxlength="50"></div>
            </div>
            <div class="form-group">
              <label>Artículos de la compra</label>
              <div id="renglones-compra">
                <div class="form-row renglon-compra mb-2">
                  <div class="form-group col-md-2">
                    <select name="tipo_item[]" class="form-control tipo-articulo-compra" required>
                      <option value="material">Material</option><option value="herramienta">Herramienta</option>
                    </select>
                  </div>
                  <div class="form-group col-md-4">
                    <select name="id_item[]" class="form-control articulo-compra" required>
                      <?php foreach ($materialesCompra as $articulo) { ?>
                        <option data-tipo="material" data-proveedor="<?php echo (int) $articulo["id_proveedor"]; ?>" value="<?php echo (int) $articulo["id"]; ?>"><?php echo $escaparCompra($articulo["nombre"] . " (" . $articulo["unidad"] . ")"); ?></option>
                      <?php } ?>
                      <?php foreach ($herramientasCompra as $articulo) { ?>
                        <option data-tipo="herramienta" value="<?php echo (int) $articulo["id"]; ?>"><?php echo $escaparCompra($articulo["nombre"]); ?></option>
                      <?php } ?>
                    </select>
                  </div>
                  <div class="form-group col-md-2"><input name="cantidad[]" type="number" class="form-control" min="0.01" step="0.01" placeholder="Cantidad" required></div>
                  <div class="form-group col-md-2"><input name="costo_unitario[]" type="number" class="form-control" min="0" step="0.01" placeholder="Costo unitario" required></div>
                  <div class="form-group col-md-2"><button type="button" class="btn btn-outline-danger quitar-renglon">Quitar</button></div>
                </div>
              </div>
              <button type="button" id="agregar-renglon-compra" class="btn btn-secondary">Agregar otro artículo</button>
            </div>
            <div class="form-group"><label for="observacion-compra">Observación</label><input id="observacion-compra" name="observacion" class="form-control" maxlength="255"></div>
            <p class="text-muted">Cada material debe pertenecer al proveedor seleccionado. El total se calcula en el servidor; las compras registran existencias solo para materiales y herramientas.</p>
            <button type="submit" class="btn btn-primary">Confirmar compra y registrar entradas</button>
          </form>
        <?php } ?>
      </div>
    </div>
    <?php if ($idCompraVista !== false && $idCompraVista > 0) { ?>
      <div class="card mb-3">
        <div class="card-header">Detalle de compra #<?php echo (int) $idCompraVista; ?></div>
        <div class="card-body table-responsive">
          <table class="table table-bordered">
            <thead><tr><th>Tipo</th><th>Artículo</th><th>Cantidad</th><th>Costo unitario</th><th>Subtotal</th></tr></thead>
            <tbody>
              <?php if (!$detalleCompraSeleccionada) { ?><tr><td colspan="5">No hay detalle o la compra no existe.</td></tr><?php } ?>
              <?php foreach ($detalleCompraSeleccionada as $detalle) { ?>
                <tr>
                  <td><?php echo $escaparCompra($detalle["tipo_item"]); ?></td>
                  <td><?php echo $escaparCompra($detalle["articulo"] ?? "Artículo no disponible"); ?></td>
                  <td><?php echo $escaparCompra($detalle["cantidad"]); ?></td>
                  <td><?php echo number_format((float) $detalle["costo_unitario"], 2); ?></td>
                  <td><?php echo number_format((float) $detalle["cantidad"] * (float) $detalle["costo_unitario"], 2); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
          <a class="btn btn-light" href="index.php?ruta=compras">Cerrar detalle</a>
        </div>
      </div>
    <?php } ?>
    <div class="card">
      <div class="card-header">Historial de compras</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>N.º</th><th>Proveedor</th><th>Documento</th><th>Total</th><th>Estado</th><th>Usuario</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if (!$compras) { ?><tr><td colspan="8" class="text-center">No hay compras registradas.</td></tr><?php } ?>
            <?php foreach ($compras as $compra) { ?>
              <tr>
                <td><?php echo $escaparCompra($compra["fecha"]); ?></td>
                <td>#<?php echo (int) $compra["id_compra"]; ?></td>
                <td><?php echo $escaparCompra($compra["nombre_proveedor"]); ?></td>
                <td><?php echo $escaparCompra($compra["nro_documento"]); ?></td>
                <td>$<?php echo number_format((float) $compra["total"], 2); ?></td>
                <td><?php echo $escaparCompra($compra["estado"]); ?></td>
                <td><?php echo $escaparCompra($compra["usuario"] ?? "Usuario no disponible"); ?></td>
                <td>
                  <a class="btn btn-info btn-sm" href="index.php?ruta=compras&amp;detalle=<?php echo (int) $compra["id_compra"]; ?>">Ver detalle</a>
                  <?php if ($compra["estado"] === "registrada") { ?>
                    <form method="post" class="mt-1" onsubmit="return confirm('La anulación intentará descontar las cantidades de esta compra y conservará sus movimientos. ¿Continuar?');">
                      <input type="hidden" name="accionCompra" value="anular">
                      <input type="hidden" name="csrf_token" value="<?php echo $escaparCompra($_SESSION["csrf_token"] ?? ""); ?>">
                      <input type="hidden" name="id_compra" value="<?php echo (int) $compra["id_compra"]; ?>">
                      <input type="text" name="motivo_anulacion" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motivo de anulación" required>
                      <button class="btn btn-danger btn-sm" type="submit">Anular</button>
                    </form>
                  <?php } ?>
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
  (function () {
    var contenedor = document.getElementById("renglones-compra");
    if (!contenedor) return;
    var proveedor = document.getElementById("proveedor-compra");
    function filtrar(renglon, seleccionarPrimero) {
      var tipo = renglon.querySelector(".tipo-articulo-compra").value;
      var articulo = renglon.querySelector(".articulo-compra");
      var idProveedor = proveedor.value;
      Array.prototype.forEach.call(articulo.options, function (opcion) {
        var tipoValido = opcion.dataset.tipo === tipo;
        var proveedorValido = tipo !== "material" || !idProveedor || opcion.dataset.proveedor === idProveedor;
        opcion.hidden = !tipoValido || !proveedorValido;
        opcion.disabled = opcion.hidden;
      });
      var seleccionada = articulo.options[articulo.selectedIndex];
      if (!seleccionada || seleccionada.disabled) {
        if (seleccionarPrimero) {
          var primera = Array.prototype.find.call(articulo.options, function (opcion) {
            return !opcion.disabled;
          });
          articulo.value = primera ? primera.value : "";
        } else {
          articulo.value = "";
        }
      }
    }
    contenedor.addEventListener("change", function (evento) {
      if (evento.target.classList.contains("tipo-articulo-compra")) {
        filtrar(evento.target.closest(".renglon-compra"), true);
      }
    });
    proveedor.addEventListener("change", function () {
      contenedor.querySelectorAll(".renglon-compra").forEach(function (renglon) {
        filtrar(renglon, false);
      });
    });
    contenedor.addEventListener("click", function (evento) {
      if (evento.target.classList.contains("quitar-renglon")) {
        var renglones = contenedor.querySelectorAll(".renglon-compra");
        if (renglones.length > 1) evento.target.closest(".renglon-compra").remove();
      }
    });
    document.getElementById("agregar-renglon-compra").addEventListener("click", function () {
      var copia = contenedor.querySelector(".renglon-compra").cloneNode(true);
      copia.querySelectorAll("input").forEach(function (campo) { campo.value = ""; });
      contenedor.appendChild(copia);
      filtrar(copia, true);
    });
    contenedor.querySelectorAll(".renglon-compra").forEach(function (renglon) {
      filtrar(renglon, true);
    });
  })();
</script>
