<!-- TABLA DE REPORTES DE BAJA REGISTRADOS -->
<div class="table-container">
  <div class="table-header-title">
    <h3>Tabla de Reportes de Baja</h3>
    <div class="d-flex justify-content-end gap-2">
      <!-- Espacio reservado para botones adicionales -->
    </div>
  </div>

  <div class="table-responsive">
    <table class="custom-table">
      <thead>
        <tr>
          <th>Operador</th>
          <th>Empresa</th>
          <th>Motivo de Baja</th>
          <th class="text-center">Calificación</th>
          <th class="text-center">Editar</th>
          <th class="text-center">Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($datos2)): ?>
          <?php foreach ($datos2 as $r): ?>
            <?php 
              // Lógica visual del semáforo para la calificación
              $calif = isset($r['calificacion_cuantitativa']) ? (int)$r['calificacion_cuantitativa'] : 0;
              
              if ($calif >= 1 && $calif <= 3) {
                  // Rojo (Mala)
                  $bg_color     = 'rgba(220, 53, 69, 0.15)';
                  $border_color = '#dc3545';
                  $text_color   = '#ff6b6b';
              } elseif ($calif >= 4 && $calif <= 5) {
                  // Naranja (Regular - Baja)
                  $bg_color     = 'rgba(253, 126, 20, 0.15)';
                  $border_color = '#fd7e14';
                  $text_color   = '#ffa502';
              } elseif ($calif >= 6 && $calif <= 7) {
                  // Amarillo (Regular - Alta)
                  $bg_color     = 'rgba(255, 193, 7, 0.15)';
                  $border_color = '#ffc107';
                  $text_color   = '#eccc68';
              } elseif ($calif >= 8 && $calif <= 10) {
                  // Verde (Buena)
                  $bg_color     = 'rgba(25, 135, 84, 0.15)';
                  $border_color = '#198754';
                  $text_color   = '#2ed573';
              } else {
                  // Sin calificación / N/A
                  $bg_color     = 'rgba(108, 117, 125, 0.15)';
                  $border_color = '#6c757d';
                  $text_color   = '#a4b0be';
              }
            ?>
            <tr>
              <td class="font-medium"><?php echo htmlspecialchars($r['nombre_operador']); ?></td>
              <td><?php echo htmlspecialchars($r['nombre_empresa']); ?></td>
              <td>
                <span class="badge-role role-default">
                  <?php 
                    if (strtoupper($r['motivo_baja']) === 'OTRO' && !empty($r['calif_cualitativa'])) {
                        echo 'OTRO: ' . htmlspecialchars($r['calif_cualitativa']);
                    } else {
                        echo htmlspecialchars($r['motivo_baja']);
                    }
                  ?>
                </span>
              </td>

              <!-- CALIFICACIÓN DIPLOMADA Y ESTILIZADA -->
              <td class="text-center font-medium">
                <?php if ($calif > 0): ?>
                  <span style="background: <?php echo $bg_color; ?>; border: 1px solid <?php echo $border_color; ?>; color: <?php echo $text_color; ?>; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                    ⭐ <?php echo $calif; ?> / 10
                  </span>
                <?php else: ?>
                  <span style="background: rgba(108, 117, 125, 0.15); border: 1px solid #6c757d; color: #a4b0be; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem;">
                    N/A
                  </span>
                <?php endif; ?>
              </td>

              <!-- BOTÓN EDITAR DESHABILITADO -->
              <td class="text-center">
                <button type="button" 
                        class="btn-action btn-edit" 
                        disabled 
                        style="opacity: 0.35; cursor: not-allowed; pointer-events: none;" 
                        title="Registro definitivo - Editar deshabilitado">
                  ✏️ Editar
                </button>
              </td>

              <!-- BOTÓN ELIMINAR DESHABILITADO -->
              <td class="text-center">
                <button type="button" 
                        class="btn-action btn-delete" 
                        disabled 
                        style="opacity: 0.35; cursor: not-allowed; pointer-events: none;" 
                        title="Registro definitivo - Eliminar deshabilitado">
                  🗑️ Eliminar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center">No hay reportes de baja registrados.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>