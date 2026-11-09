<?php
$herramientasDashboard = ControladorHerramientas::ctrMostrarHerramientas();
$materialesDashboard = ControladorMateriales::ctrMostrarMateriales();
$pedidosDashboard = ControladorPedidos::ctrMostrarPedidos();
$movimientosDashboard = ControladorMovimientos::ctrMostrarMovimientos();
$unidadesHerramientas = 0;
foreach ($herramientasDashboard as $herramientaDashboard) {
  $unidadesHerramientas += (int) $herramientaDashboard["stock"];
}
?>
<div class="content-wrapper">
  
  <!-- Encabezado de bienvenida -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="font-weight-bold mb-0">Panel de Control - INNOVA</h3>
          <p class="text-muted mb-0">Sistema de Control de Inventario de Muebles y Herramientas</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Fila de Tarjetas Estadísticas -->
  <div class="row">
    
    <!-- Tarjeta 1: Productos / Stock -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
      <div class="card card-statistics">
        <div class="card-body">
          <div class="clearfix">
            <div class="float-left">
              <i class="mdi mdi-package-variant text-primary icon-lg"></i>
            </div>
            <div class="float-right">
              <p class="mb-0 text-right text-muted">Herramientas en stock</p>
              <div class="fluid-container">
                <h3 class="font-weight-medium text-right mb-0"><?php echo $unidadesHerramientas; ?></h3>
              </div>
            </div>
          </div>
          <p class="text-muted mt-3 mb-0">
            <i class="mdi mdi-bookmark-check mr-1" aria-hidden="true"></i> Unidades registradas
          </p>
        </div>
      </div>
    </div>

    <!-- Tarjeta 2: Categorías de Muebles -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
      <div class="card card-statistics">
        <div class="card-body">
          <div class="clearfix">
            <div class="float-left">
              <i class="mdi mdi-tag-multiple text-success icon-lg"></i>
            </div>
            <div class="float-right">
              <p class="mb-0 text-right text-muted">Materiales</p>
              <div class="fluid-container">
                <h3 class="font-weight-medium text-right mb-0"><?php echo count($materialesDashboard); ?></h3>
              </div>
            </div>
          </div>
          <p class="text-muted mt-3 mb-0">
            <i class="mdi mdi-cube-outline mr-1" aria-hidden="true"></i> Tipos de insumo
          </p>
        </div>
      </div>
    </div>

    <!-- Tarjeta 3: Usuarios del Sistema -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
      <div class="card card-statistics">
        <div class="card-body">
          <div class="clearfix">
            <div class="float-left">
              <i class="mdi mdi-account-multiple text-info icon-lg"></i>
            </div>
            <div class="float-right">
              <p class="mb-0 text-right text-muted">Pedidos</p>
              <div class="fluid-container">
                <h3 class="font-weight-medium text-right mb-0"><?php echo count($pedidosDashboard); ?></h3>
              </div>
            </div>
          </div>
          <p class="text-muted mt-3 mb-0">
            <i class="mdi mdi-clipboard-check mr-1" aria-hidden="true"></i> Pedidos registrados
          </p>
        </div>
      </div>
    </div>

    <!-- Tarjeta 4: Movimientos Registrados -->
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
      <div class="card card-statistics">
        <div class="card-body">
          <div class="clearfix">
            <div class="float-left">
              <i class="mdi mdi-swap-horizontal text-danger icon-lg"></i>
            </div>
            <div class="float-right">
              <p class="mb-0 text-right text-muted">Movimientos</p>
              <div class="fluid-container">
                <h3 class="font-weight-medium text-right mb-0"><?php echo count($movimientosDashboard); ?></h3>
              </div>
            </div>
          </div>
          <p class="text-muted mt-3 mb-0">
            <i class="mdi mdi-calendar-check mr-1" aria-hidden="true"></i> Movimientos de herramientas
          </p>
        </div>
      </div>
    </div>

  </div>

</div>