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
                            <input class="form-control" type="password" name="password" form="'.$formId.'" maxlength="72" placeholder="Nueva contraseña (opcional)">
                            <button class="btn btn-warning btn-sm" type="submit">Guardar</button>
                          </form>
                          <form method="post" class="mt-1">
                            <input type="hidden" name="accionUsuario" value="estado">
                            <input type="hidden" name="idusuario" value="'.$idUsuario.'">
                            <input type="hidden" name="estado" value="'.((int) $value["estado"] === 0 ? 1 : 0).'">
                            <button class="btn btn-info btn-sm" type="submit">'.((int) $value["estado"] === 0 ? "Activar" : "Desactivar").'</button>
                          </form>
                          <form method="post" class="mt-1" onsubmit="return confirm(\'¿Eliminar este usuario?\');">
                            <input type="hidden" name="accionUsuario" value="eliminar">
                            <input type="hidden" name="idusuario" value="'.$idUsuario.'">
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
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
  </section>
</div>

<!-- MODAL AGREGAR USUARIO -->
<div class="modal fade" id="modalAgregarUsuario" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        
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