<!-- TABLA DE RESULTADOS -->
<div class="table-container mt-4">
  <div class="table-header-title">
    <h3>Tabla de Empresas</h3>
  </div>
  
  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>Nombre de la Empresa</th>
          <th>Razón Social</th>
          <th>Dirección Fiscal</th>
          <th>Responsable</th>
          <th class="text-center">Editar</th>
          <th class="text-center">Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (isset($datos2) && (is_array($datos2) || is_object($datos2))): ?>
          <?php foreach($datos2 as $dato): ?>
            <?php 
              $id = isset($dato['id_empresa']) ? $dato['id_empresa'] : (isset($dato['id']) ? $dato['id'] : ''); 
            ?>
            <tr>
              <td class="font-medium"><?php echo htmlspecialchars($dato['nombre_empresa']); ?></td>
              <td><?php echo htmlspecialchars($dato['razon_social']); ?></td>
              <td><?php echo htmlspecialchars($dato['direccion_fiscal']); ?></td>
              <td><?php echo htmlspecialchars($dato['responsable']); ?></td>
              <td class="text-center">
                <!-- Llama a tu función editar universal exactamente como en Operadores -->
                <button type="button" class="btn-action btn-edit" onclick="editar('<?php echo $id; ?>', 'empresas', 'frm')">
                  ✏️ Editar
                </button>
              </td>
              <td class="text-center">
                <button type="button" class="btn-action btn-delete" onclick="eliminar('<?php echo $id; ?>', 'empresas')">
                  🗑️ Eliminar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>