<?php
$recurso = $_GET["ruta"];
$catalogos = array(
    "clientes" => array(
        "titulo" => "Clientes",
        "id" => "idcliente",
        "campos" => array("nombre" => "Nombre", "telefono" => "Teléfono", "direccion" => "Dirección")
    ),
    "proveedores" => array(
        "titulo" => "Proveedores",
        "id" => "idproveedor",
        "campos" => array("nombre_proveedor" => "Nombre del proveedor", "telefono" => "Teléfono", "direccion" => "Dirección")
    ),
    "tipos-material" => array(
        "titulo" => "Tipos de material",
        "id" => "idtipo_material",
        "campos" => array("nombre" => "Nombre", "descripcion" => "Descripción")
    )
);
$configuracion = $catalogos[$recurso];
ControladorCatalogos::ctrProcesar($recurso);
$registros = ControladorCatalogos::ctrMostrar($recurso);
$escaparCatalogo = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Administrar <?php echo $escaparCatalogo($configuracion["titulo"]); ?></h1>
    <ol class="breadcrumb">
      <li><a href="index.php?ruta=inicio">Inicio</a></li>
      <li class="active"><?php echo $escaparCatalogo($configuracion["titulo"]); ?></li>
    </ol>
  </section>
  <section class="content">
    <div class="card">
      <div class="card-body">
        <?php if (!empty($_SESSION["mensaje"])) { ?>
          <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
            <?php echo $escaparCatalogo($_SESSION["mensaje"]["texto"]); ?>
          </div>
          <?php unset($_SESSION["mensaje"]); ?>
        <?php } ?>
        <form method="post" class="form-inline mb-4">
          <input type="hidden" name="accionCatalogo" value="crear">
          <?php foreach ($configuracion["campos"] as $campo => $etiqueta) { ?>
            <label class="sr-only" for="nuevo-<?php echo $escaparCatalogo($campo); ?>"><?php echo $escaparCatalogo($etiqueta); ?></label>
            <input
              class="form-control mr-2 mb-2"
              id="nuevo-<?php echo $escaparCatalogo($campo); ?>"
              name="<?php echo $escaparCatalogo($campo); ?>"
              placeholder="<?php echo $escaparCatalogo($etiqueta); ?>"
              maxlength="<?php echo $campo === "direccion" ? 255 : ($campo === "telefono" ? 20 : ($campo === "descripcion" ? 65535 : ($recurso === "tipos-material" ? 100 : 150))); ?>"
              <?php echo $campo === "nombre" || $campo === "nombre_proveedor" ? "required" : ""; ?>>
          <?php } ?>
          <button type="submit" class="btn btn-primary mb-2">Agregar</button>
        </form>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr><th>#</th><?php foreach ($configuracion["campos"] as $etiqueta) { ?><th><?php echo $escaparCatalogo($etiqueta); ?></th><?php } ?><th>Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($registros as $indice => $registro) { ?>
                <?php $formId = "editar-" . $recurso . "-" . (int) $registro[$configuracion["id"]]; ?>
                <tr>
                  <td><?php echo $indice + 1; ?></td>
                  <?php foreach ($configuracion["campos"] as $campo => $etiqueta) { ?>
                    <td>
                      <label class="sr-only" for="campo-<?php echo $escaparCatalogo($campo . "-" . $registro[$configuracion["id"]]); ?>"><?php echo $escaparCatalogo($etiqueta); ?></label>
                      <input
                        class="form-control"
                        id="campo-<?php echo $escaparCatalogo($campo . "-" . $registro[$configuracion["id"]]); ?>"
                        name="<?php echo $escaparCatalogo($campo); ?>"
                        form="<?php echo $formId; ?>"
                        value="<?php echo $escaparCatalogo($registro[$campo]); ?>"
                        maxlength="<?php echo $campo === "direccion" ? 255 : ($campo === "telefono" ? 20 : ($campo === "descripcion" ? 65535 : ($recurso === "tipos-material" ? 100 : 150))); ?>"
                        <?php echo $campo === "nombre" || $campo === "nombre_proveedor" ? "required" : ""; ?>>
                    </td>
                  <?php } ?>
                  <td>
                    <form id="<?php echo $formId; ?>" method="post">
                      <input type="hidden" name="accionCatalogo" value="editar">
                      <input type="hidden" name="idCatalogo" value="<?php echo (int) $registro[$configuracion["id"]]; ?>">
                      <button type="submit" class="btn btn-warning btn-sm">Guardar</button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Eliminar este registro?');">
                      <input type="hidden" name="accionCatalogo" value="eliminar">
                      <input type="hidden" name="idCatalogo" value="<?php echo (int) $registro[$configuracion["id"]]; ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
