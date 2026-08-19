<?php
// 1. Verificación e inicialización de BD
if (!isset($dbtransportistas) && !isset($db)) {
    include_once "../db/db.php";
    $db = new db();
    $db->conectar();
    $conexion_local = true;
} else {
    $db = isset($dbtransportistas) ? $dbtransportistas : $db;
    $conexion_local = false;
}

// Capturar el filtro enviado por GET
$rol_filtro = isset($_GET['rol']) ? trim($_GET['rol']) : 'TODOS';

// 2. Parámetros de paginación
$registros_por_pagina = 5; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Construir condición WHERE
$where = "";
if (!empty($rol_filtro) && strtoupper($rol_filtro) !== 'TODOS') {
    $rol_clean = addslashes($rol_filtro);
    $where = " WHERE UPPER(u.rol) = UPPER('$rol_clean') ";
}

// Total de registros filtrados
$sql_total = "SELECT COUNT(*) AS total FROM usuarios u $where";
$res_total = $db->obtenerRegistros($sql_total);
$total_registros = isset($res_total[0]['total']) ? (int)$res_total[0]['total'] : 0;
$total_paginas = ($total_registros > 0) ? ceil($total_registros / $registros_por_pagina) : 1;

if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
}

// 3. Consulta paginada y filtrada
$sql = "SELECT u.*, e.nombre_empresa, e.razon_social 
        FROM usuarios u 
        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa 
        $where
        ORDER BY u.id_usuario DESC 
        LIMIT $registros_por_pagina OFFSET $offset";

$datos2 = $db->obtenerRegistros($sql);

if ($conexion_local) {
    $db->desconectar();
}
?>

<!-- TABLA DE RESULTADOS DE USUARIOS -->
<div class="table-container">
  <div class="table-header-title">
    
    <div class="table-tabs-wrapper">
      <div class="table-tabs">
        <button type="button" class="tab-btn tab-todos <?php echo (strtoupper($rol_filtro) === 'TODOS') ? 'active' : ''; ?>" onclick="filtrarUsuarios('TODOS', this)">Todos</button>
        <button type="button" class="tab-btn tab-propietario <?php echo (strtoupper($rol_filtro) === 'PROPIETARIO') ? 'active' : ''; ?>" onclick="filtrarUsuarios('PROPIETARIO', this)">Propietarios</button>
        <button type="button" class="tab-btn tab-admin <?php echo (strtoupper($rol_filtro) === 'ADMINISTRADOR') ? 'active' : ''; ?>" onclick="filtrarUsuarios('ADMINISTRADOR', this)">Administradores</button>
        <button type="button" class="tab-btn tab-rrhh <?php echo (strtoupper($rol_filtro) === 'RRHH') ? 'active' : ''; ?>" onclick="filtrarUsuarios('RRHH', this)">RRHH</button>
      </div>
    </div>
  </div>
  
  <div class="table-responsive">
    <table class="custom-table" id="tablaUsuarios">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Correo Electrónico</th>
          <th>Rol</th>
          <th>Empresa Asignada</th>
          <th class="text-center">Editar</th>
          <th class="text-center">Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (isset($datos2) && (is_array($datos2) || is_object($datos2)) && count($datos2) > 0): ?>
          <?php foreach($datos2 as $u): ?>
            <?php 
              $id = isset($u['id_usuario']) ? $u['id_usuario'] : (isset($u['id']) ? $u['id'] : ''); 
              $rol = strtoupper($u['rol'] ?? '');

              $badgeClass = 'role-default';
              if ($rol === 'ADMINISTRADOR') $badgeClass = 'role-admin';
              elseif ($rol === 'PROPIETARIO') $badgeClass = 'role-propietario';
              elseif ($rol === 'RRHH') $badgeClass = 'role-rrhh';
            ?>
            <tr data-rol="<?php echo htmlspecialchars($rol); ?>">
              <td class="font-medium"><?php echo htmlspecialchars($u['nombre_usuario'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($u['correo_electronico'] ?? ''); ?></td>
              
              <td>
                <span class="badge-role <?php echo $badgeClass; ?>">
                  <?php echo htmlspecialchars($u['rol'] ?? ''); ?>
                </span>
              </td>

              <td>
                <?php 
                  $empresaNombre = !empty($u['nombre_empresa']) ? $u['nombre_empresa'] : (!empty($u['razon_social']) ? $u['razon_social'] : '');

                  if (!empty($u['id_empresa']) && !empty($empresaNombre)) {
                      echo htmlspecialchars($empresaNombre);
                  } else {
                      echo '<span class="fecha-vencimiento">N/A (Global)</span>';
                  }
                ?>
              </td>

              <td class="text-center">
                <button type="button" class="btn-action btn-edit" onclick="editar('<?php echo $id; ?>', 'usuarios', 'formGuardarUsuario')">✏️ Editar</button>
              </td>

              <td class="text-center">
                <button type="button" class="btn-action btn-delete" onclick="eliminar('<?php echo $id; ?>', 'usuarios')">🗑️ Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center">No se encontraron registros para este apartado.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINACIÓN CON FILTRO DE ROL -->
  <?php if (isset($total_paginas) && $total_paginas > 1): ?>
  <div class="pagination-wrapper">
    <div class="pagination-info">
      Página <span><?php echo $pagina_actual; ?></span> de <span><?php echo $total_paginas; ?></span>
    </div>

    <div class="pagination-controls">
      <?php if ($pagina_actual > 1): ?>
        <button type="button" onclick="cambiarPaginaUsuarios(<?php echo $pagina_actual - 1; ?>, '<?php echo htmlspecialchars($rol_filtro); ?>')" class="pagination-btn">&#8592; Anterior</button>
      <?php else: ?>
        <button type="button" class="pagination-btn disabled" disabled>&#8592; Anterior</button>
      <?php endif; ?>

      <div class="pagination-current">Página <?php echo $pagina_actual; ?></div>

      <?php if ($pagina_actual < $total_paginas): ?>
        <button type="button" onclick="cambiarPaginaUsuarios(<?php echo $pagina_actual + 1; ?>, '<?php echo htmlspecialchars($rol_filtro); ?>')" class="pagination-btn">Siguiente &#8594;</button>
      <?php else: ?>
        <button type="button" class="pagination-btn disabled" disabled>Siguiente &#8594;</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>