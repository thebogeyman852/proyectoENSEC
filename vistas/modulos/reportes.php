<?php
$escaparReporte = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
try {
  $datosReportes = ControladorReportes::ctrMostrar();
  $errorReporte = null;
} catch (InvalidArgumentException $error) {
  $datosReportes = array();
  $errorReporte = $error->getMessage();
}
$imprimirTablaReporte = static function ($columnas, $filas) use ($escaparReporte) {
  ?>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead><tr><?php foreach ($columnas as $etiqueta) { ?><th><?php echo $escaparReporte($etiqueta); ?></th><?php } ?></tr></thead>
      <tbody>
        <?php if (!$filas) { ?><tr><td colspan="<?php echo count($columnas); ?>" class="text-center">Sin resultados para mostrar.</td></tr><?php } ?>
        <?php foreach ($filas as $fila) { ?>
          <tr><?php foreach (array_keys($columnas) as $campo) { ?><td><?php echo $escaparReporte($fila[$campo] ?? ""); ?></td><?php } ?></tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Reportes</h1></section>
  <section class="content">
    <?php if ($errorReporte !== null) { ?><div class="alert alert-danger"><?php echo $escaparReporte($errorReporte); ?></div><?php } ?>
    <div class="card mb-3">
      <div class="card-header">Periodo</div>
      <div class="card-body">
        <form method="get" class="form-inline">
          <input type="hidden" name="ruta" value="reportes">
          <label for="reporte-desde" class="mr-2">Desde</label>
          <input id="reporte-desde" type="date" name="desde" class="form-control mr-3" value="<?php echo $escaparReporte($datosReportes["desde"] ?? ""); ?>">
          <label for="reporte-hasta" class="mr-2">Hasta</label>
          <input id="reporte-hasta" type="date" name="hasta" class="form-control mr-3" value="<?php echo $escaparReporte($datosReportes["hasta"] ?? ""); ?>">
          <button type="submit" class="btn btn-primary">Filtrar</button>
          <a href="index.php?ruta=reportes" class="btn btn-light ml-2">Limpiar</a>
        </form>
      </div>
    </div>
    <?php if (!$errorReporte) { ?>
      <div class="card mb-3"><div class="card-header">Existencias actuales <small>(el stock de productos es legado y solo se informa)</small></div><div class="card-body">
        <?php $imprimirTablaReporte(array("tipo" => "Tipo", "id" => "ID", "nombre" => "Artículo", "unidad" => "Unidad", "stock" => "Stock"), $datosReportes["existencias"]); ?>
      </div></div>
      <div class="card mb-3"><div class="card-header">Entradas, salidas y ajustes</div><div class="card-body">
        <?php $imprimirTablaReporte(array("fecha" => "Fecha", "tipo_item" => "Tipo", "articulo" => "Artículo", "tipo_movimiento" => "Movimiento", "cantidad" => "Cantidad", "stock_anterior" => "Stock anterior", "stock_resultante" => "Stock resultante", "usuario" => "Usuario", "referencia_tipo" => "Referencia", "motivo" => "Motivo"), $datosReportes["movimientos"]); ?>
      </div></div>
      <div class="card mb-3"><div class="card-header">Consumo real de materiales por pedido</div><div class="card-body">
        <?php $imprimirTablaReporte(array("fecha" => "Fecha", "idpedido" => "Pedido", "cliente" => "Cliente", "material" => "Material", "cantidad" => "Cantidad", "unidad_medida" => "Unidad", "usuario" => "Responsable", "motivo" => "Motivo"), $datosReportes["consumos"]); ?>
      </div></div>
      <div class="card mb-3"><div class="card-header">Compras por proveedor</div><div class="card-body">
        <?php $imprimirTablaReporte(array("idproveedor" => "ID", "nombre_proveedor" => "Proveedor", "cantidad_compras" => "Compras", "total_vigente" => "Total vigente", "total_anulado" => "Total anulado"), $datosReportes["compras"]); ?>
      </div></div>
      <div class="card mb-3"><div class="card-header">Herramientas y movimientos de kardex</div><div class="card-body">
        <?php $imprimirTablaReporte(array("idherramienta" => "ID", "nombre" => "Herramienta", "estado" => "Estado", "stock" => "Existencias", "entradas" => "Entradas", "salidas" => "Salidas"), $datosReportes["herramientas"]); ?>
      </div></div>
      <div class="card mb-3"><div class="card-header">Pedidos y saldos</div><div class="card-body">
        <?php $imprimirTablaReporte(array("idpedido" => "Pedido", "fecha_pedido" => "Fecha", "cliente" => "Cliente", "estado" => "Estado", "total" => "Total", "total_pagado" => "Pagado", "saldo" => "Saldo", "usuario" => "Registrado por"), $datosReportes["pedidos"]); ?>
      </div></div>
      <div class="card"><div class="card-header">Pagos realizados</div><div class="card-body">
        <?php $imprimirTablaReporte(array("idpago" => "Pago", "idpedido" => "Pedido", "fecha_pago" => "Fecha", "cliente" => "Cliente", "monto" => "Monto", "metodo_pago" => "Método", "estado_pago" => "Estado"), $datosReportes["pagos"]); ?>
      </div></div>
    <?php } ?>
  </section>
</div>
