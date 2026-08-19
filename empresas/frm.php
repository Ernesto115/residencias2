<!-- CABECERA DE LA TABLA CON EL BOTÓN AGREGAR -->
<div class="table-header-title">
  <div class="table-tabs-wrapper">
    <!-- BOTÓN QUE ABRE EL MODAL -->
    <button type="button" class="btn-agregar-op" onclick="abrirModalEmpresa()">
      + Agregar Empresa
    </button>
  </div>
</div>

<!-- ESTRUCTURA DEL MODAL FLOTANTE -->
<div id="modalEmpresa" class="modal-overlay" >
  <div class="modal-container">
    
    <!-- ENCABEZADO DEL MODAL CON BOTÓN DE CERRAR -->
    <div class="modal-header">
      <h2 class="modal-title-text">Formulario de Empresa</h2>
      <button type="button" class="btn-cerrar-modal" onclick="cerrarModalEmpresa()">&times;</button>
    </div>

    <div class="modal-body-scroll">
      <!-- FORMULARIO DE EMPRESA -->
      <form id="frm" class="form-grid" action="javascript:void(0);" enctype="multipart/form-data" onsubmit="guardar('empresas', 'frm')">
        
        <!-- CAMPO OCULTO IMPRESCINDIBLE PARA LA EDICIÓN UNIVERASAL -->
        <input type="hidden" id="id_empresa" name="id_empresa" value="">

        <!-- Datos Principales de la Empresa -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre Comercial de la Empresa</label>
            <input type="text" class="form-control" name="nombre_empresa" id="nombre_empresa" required maxlength="100" placeholder="Ej. Logística Express">
          </div>

          <div class="form-group">
            <label class="form-label">Razón Social</label>
            <input type="text" class="form-control" name="razon_social" id="razon_social" required maxlength="150" placeholder="Ej. Logística Express S.A. de C.V.">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group" style="flex: 2;">
            <label class="form-label">Dirección Fiscal</label>
            <input type="text" class="form-control" name="direccion_fiscal" id="direccion_fiscal" required maxlength="200" placeholder="Ej. Av. Hidalgo #456, Col. Centro">
          </div>

          <div class="form-group">
            <label class="form-label">Nombre del Responsable Administrativo</label>
            <input type="text" class="form-control" name="responsable" id="responsable" required maxlength="100" placeholder="Ej. Lic. Roberto Gómez">
          </div>
        </div>

        <!-- Div para mostrar alertas dinámicas dentro del formulario -->
        <div id="contenedor-alertas-empresas" class="mt-3"></div>

        <!-- Acciones del Formulario -->
        <div class="form-actions" style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">
          <button type="button" class="btn-action btn-delete" onclick="cerrarModalEmpresa()">Cancelar</button>
          <button type="submit" class="btn-prof-primary">Grabar Empresa</button>
        </div>
      </form>
    </div>

  </div>
</div>