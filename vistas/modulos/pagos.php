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
            <div class="form-group col-md-5">
              <label for="pago-pedido">Pedido</label>
              <select id="pago-pedido" name="idpedido" class="form-control" required>
                <option value="">Seleccionar pedido</option>
                <?php foreach ($pedidosConSaldo as $pedido) { ?>
                  <option value="<?php echo (int) $pedido["idpedido"]; ?>">
                    #<?php echo (int) $pedido["idpedido"]; ?> — <?php echo $escaparPago($pedido["cliente"]); ?> — saldo $<?php echo number_format((float) $pedido["saldo"], 2); ?>
                  </option>
                <?php } ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="pago-monto">Monto</label>
              <input id="pago-monto" type="number" name="monto" class="form-control" min="0.01" max="999999999.99" step="0.01" required>
            </div>
            <div class="form-group col-md-3">
              <label for="pago-metodo">Método de pago</label>
              <input id="pago-metodo" name="metodo_pago" class="form-control" maxlength="50" required>
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
          <thead><tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Monto</th><th>Método</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php foreach ($pagos as $pago) {
              $formId = "editar-pago-" . (int) $pago["idpago"];
            ?>
              <tr>
                <td><?php echo $escaparPago($pago["fecha_pago"]); ?></td>
                <td>#<?php echo (int) $pago["idpedido"]; ?></td>
                <td><?php echo $escaparPago($pago["cliente"]); ?></td>
                <td><input type="number" class="form-control" name="monto" form="<?php echo $formId; ?>" value="<?php echo $escaparPago($pago["monto"]); ?>" min="0.01" max="999999999.99" step="0.01" required></td>
                <td><input class="form-control" name="metodo_pago" form="<?php echo $formId; ?>" value="<?php echo $escaparPago($pago["metodo_pago"]); ?>" maxlength="50" required></td>
                <td>
                  <form id="<?php echo $formId; ?>" method="post">
                    <input type="hidden" name="accionPago" value="editar">
                    <input type="hidden" name="idpago" value="<?php echo (int) $pago["idpago"]; ?>">
                    <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                  </form>
                  <form method="post" onsubmit="return confirm('¿Eliminar este pago?');">
                    <input type="hidden" name="accionPago" value="eliminar">
                    <input type="hidden" name="idpago" value="<?php echo (int) $pago["idpago"]; ?>">
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
