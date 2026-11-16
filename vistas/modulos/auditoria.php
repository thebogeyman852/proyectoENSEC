<?php
$escaparAuditoria = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
try {
  $auditorias = ControladorAuditoria::ctrListar();
  $errorAuditoria = null;
} catch (InvalidArgumentException $error) {
  $auditorias = array();
  $errorAuditoria = $error->getMessage();
} catch (PDOException $error) {
  $auditorias = array();
  $errorAuditoria = "El historial requiere aplicar la migración 2026-11-12 en una copia de pruebas.";
}
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Auditoría</h1></section>
  <section class="content">
    <?php if ($errorAuditoria) { ?><div class="alert alert-danger"><?php echo $escaparAuditoria($errorAuditoria); ?></div><?php } ?>
    <?php if (!$errorAuditoria) { ?>
      <div class="card mb-3">
        <div class="card-header">Filtrar eventos</div>
        <div class="card-body">
          <form method="get" class="form-row">
            <input type="hidden" name="ruta" value="auditoria">
            <div class="form-group col-md-3"><label>Módulo</label><input name="modulo" class="form-control" maxlength="80" value="<?php echo $escaparAuditoria($_GET["modulo"] ?? ""); ?>"></div>
            <div class="form-group col-md-2"><label>ID usuario</label><input type="number" name="id_usuario" class="form-control" min="1" value="<?php echo $escaparAuditoria($_GET["id_usuario"] ?? ""); ?>"></div>
            <div class="form-group col-md-2"><label>Desde</label><input type="date" name="desde" class="form-control" value="<?php echo $escaparAuditoria($_GET["desde"] ?? ""); ?>"></div>
            <div class="form-group col-md-2"><label>Hasta</label><input type="date" name="hasta" class="form-control" value="<?php echo $escaparAuditoria($_GET["hasta"] ?? ""); ?>"></div>
            <div class="col-md-3 align-self-end mb-3"><button class="btn btn-primary" type="submit">Filtrar</button> <a class="btn btn-light" href="index.php?ruta=auditoria">Limpiar</a></div>
          </form>
        </div>
      </div>
      <div class="card"><div class="card-header">Eventos recientes (máximo 1.000)</div><div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Módulo / tabla</th><th>ID</th><th>Anterior</th><th>Nuevo</th></tr></thead>
          <tbody>
            <?php if (!$auditorias) { ?><tr><td colspan="7" class="text-center">No hay eventos registrados.</td></tr><?php } ?>
            <?php foreach ($auditorias as $evento) { ?>
              <tr>
                <td><?php echo $escaparAuditoria($evento["fecha"]); ?></td>
                <td><?php echo $escaparAuditoria($evento["usuario"] ?? "Usuario eliminado"); ?></td>
                <td><?php echo $escaparAuditoria($evento["accion"]); ?></td>
                <td><?php echo $escaparAuditoria($evento["modulo"] . " / " . $evento["tabla_afectada"]); ?></td>
                <td><?php echo $escaparAuditoria($evento["id_registro"]); ?></td>
                <td><pre><?php echo $escaparAuditoria($evento["datos_anteriores"]); ?></pre></td>
                <td><pre><?php echo $escaparAuditoria($evento["datos_nuevos"]); ?></pre></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div></div>
      <p class="text-muted">Esta pantalla solo consulta auditoría. No incluye acciones para editar ni eliminar eventos.</p>
    <?php } ?>
  </section>
</div>
