<?php
ControladorPedidos::ctrProcesarPedido();
$pedidos = ControladorPedidos::ctrMostrarPedidos();
$clientes = ControladorCatalogos::ctrMostrar("clientes");
$categorias = ControladorCategorias::ctrMostrarCategorias(null, null);
$materiales = ControladorMateriales::ctrMostrarMateriales();
$escaparPedido = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Pedidos</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparPedido($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Registrar pedido</div>
      <div class="card-body">
        <?php if (!$clientes || !$categorias) { ?>
          <div class="alert alert-warning">Para registrar pedidos, agrega al menos un cliente y una categoría.</div>
        <?php } else { ?>
          <form method="post">
            <input type="hidden" name="accionPedido" value="crear">
            <div class="form-group">
              <label for="pedido-cliente">Cliente</label>
              <select id="pedido-cliente" name="idcliente" class="form-control" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clientes as $cliente) { ?>
                  <option value="<?php echo (int) $cliente["idcliente"]; ?>"><?php echo $escaparPedido($cliente["nombre"]); ?></option>
                <?php } ?>
              </select>
            </div>
            <p class="text-muted">Las líneas se registran por categoría: la base actual no tiene una tabla de productos.</p>
            <div id="pedido-detalles">
              <div class="form-row pedido-detalle mb-2">
                <div class="form-group col-md-5">
                  <label>Categoría</label>
                  <select name="idcategoria[]" class="form-control" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $categoria) { ?>
                      <option value="<?php echo (int) $categoria["idcategoria"]; ?>"><?php echo $escaparPedido($categoria["nombre"]); ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group col-md-3">
                  <label>Cantidad</label>
                  <input type="number" name="cantidad[]" class="form-control" min="1" step="1" required>
                </div>
                <div class="form-group col-md-3">
                  <label>Precio unitario</label>
                  <input type="number" name="precio_unitario[]" class="form-control" min="0.01" max="99999999.99" step="0.01" required>
                </div>
                <div class="col-md-1 align-self-end mb-3">
                  <button type="button" class="btn btn-outline-danger quitar-detalle" aria-label="Quitar línea">×</button>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="agregar-detalle">Agregar línea</button>
            <?php if ($materiales) { ?>
              <p class="text-muted">Materiales utilizados (opcional; se descuentan del stock y se asocian al pedido).</p>
              <div id="pedido-materiales">
                <div class="form-row pedido-material mb-2">
                  <div class="form-group col-md-5">
                    <label>Material</label>
                    <select name="idmaterial[]" class="form-control">
                      <option value="">Sin material</option>
                      <?php foreach ($materiales as $material) { ?>
                        <option value="<?php echo (int) $material["idmaterial"]; ?>">
                          <?php echo $escaparPedido($material["nombre"] . " (" . $material["unidad_medida"] . ", stock " . $material["stock_actual"] . ")"); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                  <div class="form-group col-md-5">
                    <label>Cantidad usada</label>
                    <input type="number" name="cantidad_usada[]" class="form-control" min="0.01" max="99999999.99" step="0.01">
                  </div>
                  <div class="col-md-2 align-self-end mb-3">
                    <button type="button" class="btn btn-outline-danger quitar-material">Quitar</button>
                  </div>
                </div>
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="agregar-material">Agregar material</button>
            <?php } ?>
            <div><button type="submit" class="btn btn-primary">Crear pedido</button></div>
          </form>
        <?php } ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Pedidos registrados</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>Cliente</th><th>Detalle</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Estado</th><th>Registrado por</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($pedidos as $pedido) { ?>
              <tr>
                <td><?php echo $escaparPedido($pedido["fecha_pedido"]); ?></td>
                <td><?php echo $escaparPedido($pedido["cliente"]); ?></td>
                <td>
                  <?php foreach ($pedido["detalles"] as $detalle) { ?>
                    <div><?php echo $escaparPedido($detalle["categoria"]); ?>: <?php echo (int) $detalle["cantidad"]; ?> × $<?php echo number_format((float) $detalle["precio_unitario"], 2); ?></div>
                  <?php } ?>
                  <?php foreach ($pedido["materiales"] as $materialUsado) { ?>
                    <small><?php echo $escaparPedido($materialUsado["material"]); ?> usado: <?php echo $escaparPedido($materialUsado["cantidad_usada"] . " " . $materialUsado["unidad_medida"]); ?></small><br>
                  <?php } ?>
                </td>
                <td>$<?php echo number_format((float) $pedido["total"], 2); ?></td>
                <td>$<?php echo number_format($pedido["total_pagado"], 2); ?></td>
                <td>$<?php echo number_format(max(0, (float) $pedido["total"] - $pedido["total_pagado"]), 2); ?></td>
                <td><?php echo $escaparPedido($pedido["estado"]); ?></td>
                <td><?php echo $escaparPedido($pedido["usuario"]); ?></td>
                <td>
                  <form method="post" class="mb-1">
                    <input type="hidden" name="accionPedido" value="estado">
                    <input type="hidden" name="idpedido" value="<?php echo (int) $pedido["idpedido"]; ?>">
                    <select name="estado" class="form-control form-control-sm mb-1">
                      <?php foreach (array("PENDIENTE", "EN_PROCESO", "COMPLETADO", "CANCELADO") as $estado) { ?>
                        <option value="<?php echo $estado; ?>" <?php echo $pedido["estado"] === $estado ? "selected" : ""; ?>><?php echo $estado; ?></option>
                      <?php } ?>
                    </select>
                    <button type="submit" class="btn btn-warning btn-sm">Actualizar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('¿Eliminar el pedido? Se eliminarán los pagos asociados y se restaurará el stock de materiales consumidos.');">
                    <input type="hidden" name="accionPedido" value="eliminar">
                    <input type="hidden" name="idpedido" value="<?php echo (int) $pedido["idpedido"]; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                  </form>
                </td>
              </tr>
              <tr>
                <td colspan="9">
                  <details>
                    <summary>Editar pedido</summary>
                    <form method="post" class="mt-2">
                      <input type="hidden" name="accionPedido" value="editar">
                      <input type="hidden" name="idpedido" value="<?php echo (int) $pedido["idpedido"]; ?>">
                      <div class="form-group">
                        <label>Cliente</label>
                        <select name="idcliente" class="form-control" required>
                          <?php foreach ($clientes as $cliente) { ?>
                            <option value="<?php echo (int) $cliente["idcliente"]; ?>" <?php echo (int) $cliente["idcliente"] === (int) $pedido["idcliente"] ? "selected" : ""; ?>><?php echo $escaparPedido($cliente["nombre"]); ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <strong>Detalle por categoría</strong>
                      <div class="detalle-edicion">
                        <?php foreach ($pedido["detalles"] as $detalle) { ?>
                          <div class="form-row linea-edicion mb-2">
                            <div class="form-group col-md-5">
                              <label>Categoría</label>
                              <select name="idcategoria[]" class="form-control" required>
                                <?php foreach ($categorias as $categoria) { ?>
                                  <option value="<?php echo (int) $categoria["idcategoria"]; ?>" <?php echo (int) $categoria["idcategoria"] === (int) $detalle["idcategoria"] ? "selected" : ""; ?>><?php echo $escaparPedido($categoria["nombre"]); ?></option>
                                <?php } ?>
                              </select>
                            </div>
                            <div class="form-group col-md-3">
                              <label>Cantidad</label>
                              <input type="number" name="cantidad[]" class="form-control" value="<?php echo (int) $detalle["cantidad"]; ?>" min="1" step="1" required>
                            </div>
                            <div class="form-group col-md-3">
                              <label>Precio unitario</label>
                              <input type="number" name="precio_unitario[]" class="form-control" value="<?php echo $escaparPedido($detalle["precio_unitario"]); ?>" min="0.01" max="99999999.99" step="0.01" required>
                            </div>
                            <div class="col-md-1 align-self-end mb-3"><button type="button" class="btn btn-outline-danger quitar-edicion-detalle">×</button></div>
                          </div>
                        <?php } ?>
                      </div>
                      <button type="button" class="btn btn-outline-primary btn-sm mb-3 agregar-edicion-detalle">Agregar línea</button>
                      <?php if ($materiales) { ?>
                        <strong class="d-block">Materiales utilizados</strong>
                        <div class="materiales-edicion">
                          <?php if (!$pedido["materiales"]) { $pedido["materiales"] = array(array("idmaterial" => "", "cantidad_usada" => "")); } ?>
                          <?php foreach ($pedido["materiales"] as $materialUsado) { ?>
                            <div class="form-row material-edicion mb-2">
                              <div class="form-group col-md-5">
                                <label>Material</label>
                                <select name="idmaterial[]" class="form-control">
                                  <option value="">Sin material</option>
                                  <?php foreach ($materiales as $material) { ?>
                                    <option value="<?php echo (int) $material["idmaterial"]; ?>" <?php echo (int) $material["idmaterial"] === (int) $materialUsado["idmaterial"] ? "selected" : ""; ?>><?php echo $escaparPedido($material["nombre"] . " (" . $material["unidad_medida"] . ")"); ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                              <div class="form-group col-md-5">
                                <label>Cantidad usada</label>
                                <input type="number" name="cantidad_usada[]" class="form-control" value="<?php echo $escaparPedido($materialUsado["cantidad_usada"]); ?>" min="0.01" max="99999999.99" step="0.01">
                              </div>
                              <div class="col-md-2 align-self-end mb-3"><button type="button" class="btn btn-outline-danger quitar-edicion-material">Quitar</button></div>
                            </div>
                          <?php } ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-3 agregar-edicion-material">Agregar material</button>
                      <?php } ?>
                      <button type="submit" class="btn btn-primary">Guardar pedido</button>
                    </form>
                  </details>
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
    function agregarFila(contenedorId, selector, botonId) {
      var contenedor = document.getElementById(contenedorId);
      var boton = document.getElementById(botonId);
      if (!contenedor || !boton) return;
      boton.addEventListener("click", function () {
        var fila = contenedor.querySelector(selector).cloneNode(true);
        fila.querySelectorAll("input").forEach(function (campo) { campo.value = ""; });
        fila.querySelectorAll("select").forEach(function (campo) { campo.selectedIndex = 0; });
        contenedor.appendChild(fila);
      });
      contenedor.addEventListener("click", function (evento) {
        if (evento.target.classList.contains("quitar-detalle") || evento.target.classList.contains("quitar-material")) {
          if (contenedor.querySelectorAll(selector).length > 1) evento.target.closest(selector).remove();
        }
      });
    }
    agregarFila("pedido-detalles", ".pedido-detalle", "agregar-detalle");
    agregarFila("pedido-materiales", ".pedido-material", "agregar-material");
    document.querySelectorAll(".agregar-edicion-detalle, .agregar-edicion-material").forEach(function (boton) {
      boton.addEventListener("click", function () {
        var materiales = boton.classList.contains("agregar-edicion-material");
        var contenedor = boton.previousElementSibling;
        var selector = materiales ? ".material-edicion" : ".linea-edicion";
        var fila = contenedor.querySelector(selector).cloneNode(true);
        fila.querySelectorAll("input").forEach(function (campo) { campo.value = ""; });
        fila.querySelectorAll("select").forEach(function (campo) { campo.selectedIndex = 0; });
        contenedor.appendChild(fila);
      });
    });
    document.addEventListener("click", function (evento) {
      if (evento.target.classList.contains("quitar-edicion-detalle") || evento.target.classList.contains("quitar-edicion-material")) {
        var fila = evento.target.closest(".linea-edicion, .material-edicion");
        var contenedor = fila.parentElement;
        if (contenedor.children.length > 1) fila.remove();
      }
    });
  })();
</script>
