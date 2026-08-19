<!-- CABECERA DE LA TABLA CON EL BOTÓN AGREGAR -->
<div class="table-header-title">
    <div class="table-tabs-wrapper">
        <button type="button" class="btn-agregar-op" onclick="abrirModalOperador()">
            + Agregar Operador
        </button>
    </div>
</div>


<!-- ESTRUCTURA DEL MODAL FLOTANTE -->
<div id="modalOperador" class="modal-overlay">
    <div class="modal-container">

        <div class="modal-header">
            <h2 class="modal-title-text">Formulario de Operador</h2>

            <button
                type="button"
                class="btn-cerrar-modal"
                onclick="cerrarModalOperador()">
                &times;
            </button>
        </div>


        <div class="modal-body-scroll">

            <form
                id="frm"
                class="form-grid"
                action="javascript:void(0);"
                enctype="multipart/form-data"
                onsubmit="guardar('operadores', 'frm', event)">


                <!-- Campo oculto para el ID -->
                <input
                    type="hidden"
                    id="id_operador"
                    name="id_operador"
                    value="">


                <!-- =====================================================
                     SECCIÓN 1: DATOS PERSONALES
                     CAMPOS OBLIGATORIOS EN LA BASE DE DATOS
                     ===================================================== -->

                <p style="font-weight: bold; color: var(--accent-color); margin-bottom: 10px;">
                    👤 Datos Personales (Obligatorio)
                </p>


                <div class="form-row">

                    <!-- OBLIGATORIO -->
                    <div class="form-group">
                        <label class="form-label">Nombres *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="nombres"
                            id="nombres"
                            required
                            maxlength="30"
                            placeholder="Ej. Juan Carlos">
                    </div>


                    <!-- OBLIGATORIO -->
                    <div class="form-group">
                        <label class="form-label">Primer Apellido *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="primer_apellido"
                            id="primer_apellido"
                            required
                            maxlength="30"
                            placeholder="Ej. Gómez">
                    </div>


                    <!-- OBLIGATORIO -->
                    <div class="form-group">
                        <label class="form-label">Segundo Apellido *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="segundo_apellido"
                            id="segundo_apellido"
                            required
                            maxlength="30"
                            placeholder="Ej. Martínez">
                    </div>

                </div>


                <div class="form-row">

                    <!-- OBLIGATORIO -->
                    <div class="form-group">
                        <label class="form-label">RFC *</label>

                        <input
                            type="text"
                            class="form-control"
                            name="rfc"
                            id="rfc"
                            required
                            maxlength="13"
                            placeholder="13 caracteres">
                    </div>


                    <!-- OBLIGATORIO -->
                    <div class="form-group">
                        <label class="form-label">Teléfono Celular *</label>

                        <input
                            type="tel"
                            class="form-control"
                            name="telefono_celular"
                            id="telefono_celular"
                            required
                            maxlength="10"
                            placeholder="Ej. 8789876543">
                    </div>


                    <!-- YA TIENE ACTIVO COMO VALOR PREDETERMINADO -->
                    <div class="form-group">
                        <label class="form-label">Estatus del Operador</label>

                        <select
                            class="form-control"
                            name="estatus"
                            id="estatus">

                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>

                        </select>
                    </div>

                </div>


                <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--borde-sutil);">


                <!-- =====================================================
                     SECCIÓN 2: DIRECCIÓN
                     TODO OPCIONAL
                     ===================================================== -->

                <p style="font-weight: bold; color: var(--accent-color); margin-bottom: 10px;">
                    📍 Domicilio - (Rellenar si se cuenta con la información)
                </p>


                <div class="form-row">

                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Calle y Número</label>

                        <input
                            type="text"
                            class="form-control"
                            name="calle_y_numero"
                            id="calle_y_numero"
                            maxlength="50"
                            placeholder="Ej. Av. Industrial #123">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Colonia</label>

                        <input
                            type="text"
                            class="form-control"
                            name="colonia"
                            id="colonia"
                            maxlength="30"
                            placeholder="Ej. Centro">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Código Postal</label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo_postal"
                            id="codigo_postal"
                            maxlength="5"
                            placeholder="Ej. 26000">
                    </div>

                </div>


                <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--borde-sutil);">


                <!-- =====================================================
                     SECCIÓN 3: LICENCIA FEDERAL
                     TODO OPCIONAL
                     ===================================================== -->

                <p style="font-weight: bold; color: var(--accent-color); margin-bottom: 10px;">
                    💳 Licencia Federal - (Rellenar si se cuenta con la información)
                </p>


                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">No. Licencia Federal Actual</label>

                        <input
                            type="text"
                            class="form-control"
                            name="licencia_federal_actual"
                            id="licencia_federal_actual"
                            maxlength="20"
                            placeholder="Ej. 1234567890">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Vencimiento Licencia Federal</label>

                        <input
                            type="date"
                            class="form-control"
                            name="vencimiento_lic_federal"
                            id="vencimiento_lic_federal">
                    </div>


                    <div class="form-group">
                        <label class="form-label">PDF Licencia Federal</label>

                        <input
                            type="file"
                            class="form-control"
                            name="archivo_pdf_licencia"
                            id="archivo_pdf_licencia"
                            accept="application/pdf">
                    </div>

                </div>


                <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--borde-sutil);">


                <!-- =====================================================
                     SECCIÓN 4: APTO MÉDICO
                     TODO OPCIONAL
                     ===================================================== -->

                <p style="font-weight: bold; color: var(--accent-color); margin-bottom: 10px;">
                    🩺 Apto Médico - (Rellenar si se cuenta con la información)
                </p>


                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">No. Apto Médico Actual</label>

                        <input
                            type="text"
                            class="form-control"
                            name="apto_medico_actual"
                            id="apto_medico_actual"
                            maxlength="20"
                            placeholder="Ej. 178280">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Vencimiento Apto Médico</label>

                        <input
                            type="date"
                            class="form-control"
                            name="vencimiento_apto_medico"
                            id="vencimiento_apto_medico">
                    </div>


                    <div class="form-group">
                        <label class="form-label">PDF Apto Médico</label>

                        <input
                            type="file"
                            class="form-control"
                            name="archivo_pdf_apto_medico"
                            id="archivo_pdf_apto_medico"
                            accept="application/pdf">
                    </div>

                </div>


                <hr style="margin: 15px 0; border: 0; border-top: 1px solid var(--borde-sutil);">


                <!-- =====================================================
                     SECCIÓN 5: CRUCE INTERNACIONAL
                     VISA Y FAST SON OPCIONALES
                     ===================================================== -->

                <p style="font-weight: bold; color: var(--accent-color); margin-bottom: 10px;">
                    🇺🇸 Cruce Internacional - (Rellenar si se cuenta con la información)
                </p>


                <!-- VISA -->
                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">No. VISA Laser / LPR</label>

                        <input
                            type="text"
                            class="form-control"
                            name="visa"
                            id="visa"
                            maxlength="20"
                            placeholder="Ej. 12 o 13 dígitos">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Vencimiento VISA</label>

                        <input
                            type="date"
                            class="form-control"
                            name="vencimiento_visa"
                            id="vencimiento_visa">
                    </div>


                    <div class="form-group">
                        <label class="form-label">PDF VISA</label>

                        <input
                            type="file"
                            class="form-control"
                            name="archivo_pdf_visa"
                            id="archivo_pdf_visa"
                            accept="application/pdf">
                    </div>

                </div>


                <!-- FAST / SENTRI -->
                <div class="form-row">

                    <div class="form-group">
                        <label class="form-label">No. Gafete FAST / SENTRI</label>

                        <input
                            type="text"
                            class="form-control"
                            name="fast"
                            id="fast"
                            maxlength="20"
                            placeholder="Ej. PASS ID o tradicional">
                    </div>


                    <div class="form-group">
                        <label class="form-label">Vencimiento FAST</label>

                        <input
                            type="date"
                            class="form-control"
                            name="vencimiento_fast"
                            id="vencimiento_fast">
                    </div>


                    <div class="form-group">
                        <label class="form-label">PDF FAST</label>

                        <input
                            type="file"
                            class="form-control"
                            name="fast_pdf"
                            id="fast_pdf"
                            accept="application/pdf">
                    </div>

                </div>


                <!-- MENSAJES -->
                <div
                    id="contenedor-alertas-operadores"
                    class="mt-3">
                </div>


                <!-- BOTONES -->
                <div
                    class="form-actions"
                    style="margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end;">

                    <button
                        type="button"
                        class="btn-action btn-delete"
                        onclick="cerrarModalOperador()">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn-prof-primary">
                        Grabar Operador
                    </button>

                </div>

            </form>

        </div>

    </div>
</div>