<?php
ControladorPagos::ctrProcesarPago();
$pagos = ControladorPagos::ctrMostrarPagos();
$pedidosConSaldo = ControladorPagos::ctrMostrarPedidosConSaldo();
$escaparPago = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Pagos</h1></section>
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparPago($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="card mb-3">
      <div class="card-header">Registrar pago</div>
      <div class="card-body">
        <?php if (!$pedidosConSaldo) { ?>
          <p>No hay pedidos con saldo pendiente.</p>
        <?php } else { ?>
          <form method="post" class="form-row">
            <input type="hidden" name="accionPago" value="crear">
            <input type="hidden" name="csrf_token" value="<?php echo $escaparPago($_SESSION["csrf_token"] ?? ""); ?>">
            <input type="hidden" name="id_solicitud" value="<?php echo bin2hex(random_bytes(16)); ?>">
            <div class="form-group col-md-5">
              <label for="pago-pedido">Pedido</label>
              <select id="pago-pedido" name="idpedido" class="form-control" required>
                <option value="">Seleccionar pedido</option>
                <?php foreach ($pedidosConSaldo as $pedido) { ?>
                  <option value="<?php echo (int) $pedido["idpedido"]; ?>" data-total="<?php echo $escaparPago($pedido["total"]); ?>" data-pagado="<?php echo $escaparPago($pedido["total_pagado"]); ?>" data-saldo="<?php echo $escaparPago($pedido["saldo"]); ?>">
                    #<?php echo (int) $pedido["idpedido"]; ?> — <?php echo $escaparPago($pedido["cliente"]); ?> — total $<?php echo number_format((float) $pedido["total"], 2); ?> — pagado $<?php echo number_format((float) $pedido["total_pagado"], 2); ?> — saldo $<?php echo number_format((float) $pedido["saldo"], 2); ?>
                  </option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label for="pago-monto">Monto</label>
              <input id="pago-monto" type="number" name="monto" class="form-control" min="0.01" max="999999999.99" step="0.01" required>
            </div>
            <div class="form-group col-md-2">
              <label for="pago-metodo">Método de pago</label>
              <input id="pago-metodo" name="metodo_pago" class="form-control" maxlength="50" required>
            </div>
            <div class="form-group col-md-2 align-self-end">
              <p>Total: $<span id="pago-total">0.00</span><br>Pagado: $<span id="pago-pagado">0.00</span><br>Saldo: $<span id="pago-saldo">0.00</span></p>
            </div>
            <div class="col-md-1 align-self-end mb-3"><button class="btn btn-primary" type="submit">Guardar</button></div>
          </form>
        <?php } ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Pagos registrados</div>
      <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Monto</th><th>Método</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if (!$pagos) { ?><tr><td colspan="7" class="text-center">No hay pagos registrados.</td></tr><?php } ?>
            <?php foreach ($pagos as $pago) {
              $formId = "editar-pago-" . (int) $pago["idpago"];
            ?>
              <tr>
                <td><?php echo $escaparPago($pago["fecha_pago"]); ?></td>
                <td>#<?php echo (int) $pago["idpedido"]; ?></td>
                <td><?php echo $escaparPago($pago["cliente"]); ?></td>
                <td><?php if ($pago["estado_pago"] === "registrado") { ?><input type="number" class="form-control" name="monto" form="<?php echo $formId; ?>" value="<?php echo $escaparPago($pago["monto"]); ?>" min="0.01" max="999999999.99" step="0.01" required><?php } else { echo "$" . number_format((float) $pago["monto"], 2); } ?></td>
                <td><?php if ($pago["estado_pago"] === "registrado") { ?><input class="form-control" name="metodo_pago" form="<?php echo $formId; ?>" value="<?php echo $escaparPago($pago["metodo_pago"]); ?>" maxlength="50" required><?php } else { echo $escaparPago($pago["metodo_pago"]); } ?></td>
                <td><?php echo $escaparPago($pago["estado_pago"]); ?><?php if (!empty($pago["motivo_anulacion"])) { ?><br><small><?php echo $escaparPago($pago["motivo_anulacion"]); ?></small><?php } ?></td>
                <td>
                  <?php if ($pago["estado_pago"] === "registrado") { ?>
                  <form id="<?php echo $formId; ?>" method="post">
                    <input type="hidden" name="accionPago" value="editar">
                    <input type="hidden" name="idpago" value="<?php echo (int) $pago["idpago"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $escaparPago($_SESSION["csrf_token"] ?? ""); ?>">
                    <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('El pago quedará anulado en el historial y dejará de contar para el saldo. ¿Continuar?');">
                    <input type="hidden" name="accionPago" value="anular">
                    <input type="hidden" name="idpago" value="<?php echo (int) $pago["idpago"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $escaparPago($_SESSION["csrf_token"] ?? ""); ?>">
                    <input type="text" name="motivo_anulacion" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motivo de anulación" required>
                    <button class="btn btn-danger btn-sm" type="submit">Anular</button>
                  </form>
                  <?php } else { ?><span class="text-muted">Sin acciones</span><?php } ?>
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
    var pedidos = document.getElementById("pago-pedido");
    if (!pedidos) return;
    function actualizarSaldo() {
      var opcion = pedidos.options[pedidos.selectedIndex];
      var total = Number(opcion ? opcion.getAttribute("data-total") || 0 : 0);
      var pagado = Number(opcion ? opcion.getAttribute("data-pagado") || 0 : 0);
      var saldo = Number(opcion ? opcion.getAttribute("data-saldo") || 0 : 0);
      document.getElementById("pago-total").textContent = total.toFixed(2);
      document.getElementById("pago-pagado").textContent = pagado.toFixed(2);
      document.getElementById("pago-saldo").textContent = saldo.toFixed(2);
      var monto = document.getElementById("pago-monto");
      if (opcion && opcion.value) monto.max = saldo.toFixed(2);
      else monto.removeAttribute("max");
    }
    pedidos.addEventListener("change", actualizarSaldo);
    actualizarSaldo();
  })();
</script>
