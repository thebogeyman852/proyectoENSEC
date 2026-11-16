<?php
$escaparCambio = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Cambiar contraseña temporal</h1></section>
  <section class="content">
    <div class="alert alert-warning">Por seguridad, la contraseña temporal vence en cinco minutos y obliga a establecer una nueva contraseña antes de usar otros módulos.</div>
    <?php if (!empty($_SESSION["mensajeCambioPassword"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensajeCambioPassword"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparCambio($_SESSION["mensajeCambioPassword"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensajeCambioPassword"]); ?>
    <?php } ?>
    <form method="post" class="card card-body">
      <input type="hidden" name="csrf_token" value="<?php echo $escaparCambio($_SESSION["csrf_token"] ?? ""); ?>">
      <div class="form-group">
        <label for="nueva-password">Nueva contraseña (8 a 72 caracteres alfanuméricos, diferente de la temporal)</label>
        <input id="nueva-password" type="password" name="nuevaPassword" class="form-control" minlength="8" maxlength="72" pattern="[A-Za-z0-9]+" autocomplete="new-password" required>
      </div>
      <div class="form-group">
        <label for="confirmar-password">Confirmar nueva contraseña</label>
        <input id="confirmar-password" type="password" name="confirmarPassword" class="form-control" minlength="8" maxlength="72" pattern="[A-Za-z0-9]+" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary" type="submit">Guardar contraseña</button>
    </form>
  </section>
</div>
