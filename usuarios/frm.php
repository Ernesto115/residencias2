<!-- CABECERA DE LA TABLA CON EL BOTÓN AGREGAR -->
<div class="table-header-title">
  <div class="table-tabs-wrapper">
    <!-- BOTÓN QUE ABRE EL MODAL -->
    <button type="button" class="btn-agregar-op" onclick="abrirModalUsuario()">
      + Agregar Usuario
    </button>
  </div>
</div>

<!-- ESTRUCTURA DEL MODAL FLOTANTE -->
<div id="modalUsuario" class="modal-overlay">
  <div class="modal-container">
    
    <!-- ENCABEZADO DEL MODAL CON BOTÓN DE CERRAR -->
    <div class="modal-header">
      <h2 class="modal-title-text">Nuevo Usuario</h2>
      <button type="button" class="btn-cerrar-modal" onclick="cerrarModalUsuario()">&times;</button>
    </div>

    <div class="modal-body-scroll">
      <!-- FORMULARIO DE USUARIO -->
      <form id="formGuardarUsuario" class="form-grid" action="javascript:void(0);" onsubmit="guardar('usuarios', 'formGuardarUsuario')">
        
        <!-- CAMPO OCULTO PARA LA EDICIÓN -->
        <input type="hidden" id="id_usuario" name="id_usuario" value="">

        <!-- Campos de entrada principales -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre de Usuario (RFC / ID)</label>
            <input type="text" class="form-control" name="nombre_usuario" id="nombre_usuario" maxlength="13" required placeholder="Ej. ABCD123456XYZ">
          </div>

          <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control" name="correo_electronico" id="correo_electronico" maxlength="100" required placeholder="usuario@correo.com">
          </div>

          <!-- CAMPO CONTRASEÑA CON ICONO SVG ESTÁNDAR -->
          <div class="form-group">
            <label class="form-label">Contraseña</label>
            <div class="password-wrapper">
              <input type="password" class="form-control" name="contrasena" id="contrasena" minlength="12" maxlength="12" placeholder="12 caract (1 Mayús, 1 Minús, 1 Símbolo)">
              <button type="button" class="btn-toggle-password" onclick="togglePassword('contrasena', this)" title="Mostrar/Ocultar contraseña">
                <!-- Icono Ojo Abierto -->
                <svg class="eye-icon eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <!-- Icono Ojo Tachado (Oculto por defecto) -->
                <svg class="eye-icon eye-hide" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Selección de Rol y Asignación de Empresa -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Rol de Usuario</label>
            <select class="form-control" id="rol" name="rol" required onchange="controlarDespliegueEmpresa()">
              <option value="" selected disabled>-- Selecciona un Rol --</option>
              <option value="ADMINISTRADOR">Administrador</option>
              <option value="PROPIETARIO">Propietario</option>
              <option value="RRHH">RRHH</option>
            </select>
          </div>

          <!-- Menú Desplegable de Empresa -->
          <div class="form-group" id="seccion_empresa" style="display: none;">
            <label class="form-label">Empresa / Transportista</label>
            <select class="form-control" id="id_empresa" name="id_empresa">
              <option value="">-- Selecciona una Empresa --</option>
              <?php if (isset($empresas) && (is_array($empresas) || is_object($empresas))): ?>
                <?php foreach ($empresas as $emp): ?>
                  <option value="<?php echo $emp['id_empresa']; ?>">
                    <?php 
                      echo htmlspecialchars($emp['nombre_empresa'] ?? $emp['razon_social'] ?? ''); 
                    ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        </div>

        <!-- Espacio reservado para avisos del sistema -->
        <div id="contenedor-alertas-usuarios" class="mt-3"></div>

        <!-- Botones de Guardado / Acción -->
        <div class="form-actions" style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
          <button type="button" class="btn-action btn-delete" onclick="cerrarModalUsuario()">Cancelar</button>
          <button type="submit" class="btn-prof-primary">Grabar Usuario</button>
        </div>
      </form>
    </div>

  </div>
</div>