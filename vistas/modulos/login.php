<?php $hayUsuarios = ControladorUsuarios::ctrHayUsuarios(); ?>
<div class="container-scroller">
  <div class="container-fluid page-body-wrapper full-page-wrapper">
    <div class="content-wrapper d-flex align-items-center auth auth-bg-1 theme-one">
      <div class="row w-100">
        <div class="col-lg-4 mx-auto">
          <div class="auto-form-wrapper">
            
            <?php if ($hayUsuarios) { ?>
            <form action="" method="POST">
              
              <div class="form-group">
                <label class="label">Username</label>
                <div class="input-group">
                  <input type="text" class="form-control" name="ingUsuario" placeholder="user" required>
                  <div class="input-group-append">
                    <span class="input-group-text">
                      <i class="mdi mdi-check-circle-outline"></i>
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label class="label">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" name="ingPassword" placeholder="Password" required>
                  <div class="input-group-append">
                    <span class="input-group-text">
                      <i class="mdi mdi-check-circle-outline"></i>
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <button type="submit" class="btn btn-primary submit-btn btn-block">Login</button>
              </div>

              <?php
                // Instanciamos al controlador de usuarios para procesar el ingreso
                $login = new ControladorUsuarios();
                $login->crtIngresoUsuario();
              ?>

            </form>
            <?php } else { ?>
              <h4 class="text-center mb-3">Configuración inicial</h4>
              <p class="text-muted text-center">Crea la cuenta administradora para comenzar a usar el sistema.</p>
              <form action="" method="POST">
                <input type="hidden" name="crearPrimerUsuario" value="1">
                <div class="form-group">
                  <label class="label">Nombre del administrador</label>
                  <input type="text" class="form-control" name="primerNombre" maxlength="150" required>
                </div>
                <div class="form-group">
                  <label class="label">Usuario</label>
                  <input type="text" class="form-control" name="primerUsuario" maxlength="50" pattern="[A-Za-z0-9]+" required>
                </div>
                <div class="form-group">
                  <label class="label">Contraseña (mínimo 8 caracteres)</label>
                  <input type="password" class="form-control" name="primerPassword" minlength="8" maxlength="72" pattern="[A-Za-z0-9]+" required>
                </div>
                <button type="submit" class="btn btn-primary submit-btn btn-block">Crear administrador</button>
                <?php ControladorUsuarios::ctrCrearPrimerUsuario(); ?>
              </form>
            <?php } ?>
            
          </div>
        </div>
      </div>
    </div>
    <!-- content-wrapper ends -->
  </div>
  <!-- page-body-wrapper ends -->
</div>
