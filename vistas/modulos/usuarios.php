<?php
ControladorUsuarios::ctrProcesarUsuario();
$escaparUsuario = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
};
?>
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Gestionar Usuarios
    </h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      <li class="active">Gestionar Usuarios</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <?php if (!empty($_SESSION["mensaje"])) { ?>
      <div class="alert alert-<?php echo $_SESSION["mensaje"]["tipo"] === "success" ? "success" : "danger"; ?>">
        <?php echo $escaparUsuario($_SESSION["mensaje"]["texto"]); ?>
      </div>
      <?php unset($_SESSION["mensaje"]); ?>
    <?php } ?>
    <div class="box">
      <div class="box-header with-border">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarUsuario">
          Agregar usuario
        </button>
      </div>
      <div class="box-body">
        <table class="table table-bordered table-striped dt-responsive tablas" width="100%">
          <thead>
            <tr>
              <th style="width:10px">#</th>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Gmail</th>
              <th>Foto</th>
              <th>Perfil</th>
              <th>Estado</th>
              <th>Último login</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $item = null;
            $valor = null;
            $usuarios = ControladorUsuarios::ctrMostrarUsuarios($item, $valor);

            if ($usuarios) {
              foreach ($usuarios as $key => $value) {
                $idUsuario = (int) $value["idusuario"];
                $formId = "editar-usuario-" . $idUsuario;
                echo '<tr>
                        <td>'.($key+1).'</td>
                        <td><input class="form-control" name="nombre" form="'.$formId.'" value="'.$escaparUsuario($value["nombre"]).'" maxlength="150" required></td>
                        <td><input class="form-control" name="usuario" form="'.$formId.'" value="'.$escaparUsuario($value["usuario"]).'" maxlength="50" required></td>';
                echo '<td><input class="form-control" type="email" name="correo" form="'.$formId.'" value="'.$escaparUsuario($value["correo"] ?? "").'" maxlength="254" pattern=".+@gmail\.com" required></td>';

                if (!empty($value["foto"])) {
                  echo '<td><img src="'.$escaparUsuario($value["foto"]).'" class="img-thumbnail" width="40px" alt="Foto"></td>';
                } else {
                  echo '<td><img src="vistas/img/usuarios/default/anonymous.png" class="img-thumbnail" width="40px"></td>';
                }

                echo '<td>
                        <select class="form-control" name="perfil" form="'.$formId.'">
                          <option value="Administrador" '.($value["perfil"] === "Administrador" ? "selected" : "").'>Administrador</option>
                          <option value="Especial" '.($value["perfil"] === "Especial" ? "selected" : "").'>Especial</option>
                          <option value="Vendedor" '.($value["perfil"] === "Vendedor" ? "selected" : "").'>Vendedor</option>
                        </select>
                      </td>';

                if ((int) $value["estado"] !== 0) {
                  echo '<td><span class="badge badge-success">Activo</span></td>';
                } else {
                  echo '<td><span class="badge badge-secondary">Inactivo</span></td>';
                }

                echo '<td>'.$escaparUsuario($value["ultimo_login"]).'</td>
                        <td>
                          <form id="'.$formId.'" method="post">
                            <input type="hidden" name="accionUsuario" value="editar">
                            <input type="hidden" name="idusuario" value="'.$idUsuario.'">
                            <input type="hidden" name="csrf_token" value="'.$escaparUsuario($_SESSION["csrf_token"] ?? "").'">
                            <input class="form-control" type="password" name="password" form="'.$formId.'" maxlength="72" placeholder="Nueva contraseña (opcional)">
                            <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                          </form>
                          <form method="post" class="mt-1">
                            <input type="hidden" name="accionUsuario" value="estado">
                            <input type="hidden" name="idusuario" value="'.$idUsuario.'">
                            <input type="hidden" name="csrf_token" value="'.$escaparUsuario($_SESSION["csrf_token"] ?? "").'">
                            <input type="hidden" name="estado" value="'.((int) $value["estado"] === 0 ? 1 : 0).'">
                            <button class="btn btn-info btn-sm" type="submit">'.((int) $value["estado"] === 0 ? "Activar" : "Desactivar").'</button>
                          </form>
                          <form method="post" class="mt-1" onsubmit="return confirm(\'¿Desactivar este usuario conservando su historial?\');">
                            <input type="hidden" name="accionUsuario" value="eliminar">
                            <input type="hidden" name="idusuario" value="'.$idUsuario.'">
                            <input type="hidden" name="csrf_token" value="'.$escaparUsuario($_SESSION["csrf_token"] ?? "").'">
                            <button class="btn btn-danger btn-sm" type="submit">Desactivar y conservar</button>
                          </form>
                        </td>
                      </tr>';
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if (($_SESSION["perfil"] ?? "") === "Administrador") {
      try {
        $solicitudesRecuperacion = ControladorRecuperacion::ctrListarSolicitudes();
        $errorSolicitudes = null;
      } catch (PDOException $error) {
        $solicitudesRecuperacion = array();
        $errorSolicitudes = "No se pudieron consultar las solicitudes de recuperación.";
      }
    ?>
      <div class="box mt-3">
        <div class="box-header with-border"><h3>Historial de solicitudes de recuperación</h3></div>
        <div class="box-body table-responsive">
          <?php if ($errorSolicitudes) { ?><div class="alert alert-warning"><?php echo $escaparUsuario($errorSolicitudes); ?></div><?php } ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Solicitada</th><th>Usuario</th><th>Nombre</th><th>Gmail registrado</th>
                <th>Estado</th><th>Aprobada</th><th>Vence</th><th>Usada</th>
                <th>Administrador</th><th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$solicitudesRecuperacion) { ?><tr><td colspan="10">No hay solicitudes de recuperación.</td></tr><?php } ?>
              <?php foreach ($solicitudesRecuperacion as $solicitud) { ?>
                <tr>
                  <td><?php echo $escaparUsuario($solicitud["solicitado_en"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["usuario"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["nombre"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["correo"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["estado"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["aprobado_en"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["expira_en"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["usado_en"]); ?></td>
                  <td><?php echo $escaparUsuario($solicitud["nombre_administrador"]); ?></td>
                  <td>
                    <?php if ($solicitud["estado"] === "pendiente") { ?>
                    <form method="post" onsubmit="return confirm('Se generará una contraseña temporal de 10 caracteres y se enviará al Gmail registrado. ¿Aprobar?');">
                      <input type="hidden" name="accionUsuario" value="aprobar-recuperacion">
                      <input type="hidden" name="id_solicitud" value="<?php echo (int) $solicitud["id_solicitud"]; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo $escaparUsuario($_SESSION["csrf_token"] ?? ""); ?>">
                      <button type="submit" class="btn btn-warning btn-sm">Aprobar y enviar</button>
                    </form>
                    <form method="post" class="mt-1" onsubmit="return confirm('¿Rechazar esta solicitud?');">
                      <input type="hidden" name="accionUsuario" value="rechazar-recuperacion">
                      <input type="hidden" name="id_solicitud" value="<?php echo (int) $solicitud["id_solicitud"]; ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo $escaparUsuario($_SESSION["csrf_token"] ?? ""); ?>">
                      <button type="submit" class="btn btn-secondary btn-sm">Rechazar</button>
                    </form>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php } ?>
  </section>
</div>

<!-- MODAL AGREGAR USUARIO -->
<div class="modal fade" id="modalAgregarUsuario" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $escaparUsuario($_SESSION["csrf_token"] ?? ""); ?>">
        
        <div class="modal-header" style="background:#3c8dbc; color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar usuario</h4>
        </div>
        
        <div class="modal-body">
          <div class="box-body">
            
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoNombre" placeholder="Ingresar nombre" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoUsuario" placeholder="Ingresar usuario" id="nuevoUsuario" required>
              </div>
            </div>
            <div class="form-group">
              <label>Gmail</label>
              <input type="email" class="form-control input-lg" name="nuevoCorreo" maxlength="254" pattern=".+@gmail\.com" required>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input type="password" class="form-control input-lg" name="nuevoPassword" placeholder="Ingresar contraseña" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg" name="nuevoPerfil">
                  <option value="">Seleccionar perfil</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Especial">Especial</option>
                  <option value="Vendedor">Vendedor</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <div class="panel">SUBIR FOTO</div>
              <input type="file" class="nuevaFoto" name="nuevaFoto">
              <p class="help-block">Peso máximo de la foto 2MB</p>
              <img src="vistas/img/usuarios/default/anonymous.png" class="img-thumbnail previsualizar" width="100px">
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar usuario</button>
        </div>

        <?php
        $crearUsuario = new ControladorUsuarios();
        $crearUsuario->ctrCrearUsuario();
        ?>

      </form>
    </div>
  </div>
</div>