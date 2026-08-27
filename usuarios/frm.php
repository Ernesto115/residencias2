<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$rolSesion = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rolSesion === 'ADMINISTRADOR') $rolSesion = 'ADMIN';

if ($rolSesion !== 'ADMIN') {
    http_response_code(403);
    echo '<div class="alert alert-danger">No tienes permiso para administrar usuarios.</div>';
    return;
}

/* Si se abre directamente, cargar empresas */
if (!isset($empresas)) {

    include_once "../db/db.php";

    $dbFrm = new db();
    $dbFrm->conectar();

    $empresas = $dbFrm->obtenerRegistros(
        "SELECT id_empresa,nombre_empresa
         FROM empresas
         ORDER BY nombre_empresa"
    );

    $dbFrm->desconectar();
}
?>

<div class="table-header-title">
    <div class="table-tabs-wrapper">

        <button type="button"
                class="btn-agregar-op"
                onclick="abrirModalUsuario()">
            + Agregar Usuario
        </button>

    </div>
</div>


<div id="modalUsuario" class="modal-overlay">

<div class="modal-container">

    <div class="modal-header">

        <h2 class="modal-title-text">
            Nuevo Usuario
        </h2>

        <button type="button"
                class="btn-cerrar-modal"
                onclick="cerrarModalUsuario()">
            &times;
        </button>

    </div>


    <div class="modal-body-scroll">

        <form id="formGuardarUsuario"
              class="form-grid"
              action="javascript:void(0);"
              onsubmit="guardar('usuarios','formGuardarUsuario',event)">

            <input type="hidden"
                   id="id_usuario"
                   name="id_usuario"
                   value="">


            <!-- DATOS PERSONALES -->
            <p style="font-weight:bold;color:var(--accent-color);margin-bottom:10px;">
                👤 Datos Personales
            </p>

            <div class="form-row">

                <div class="form-group">
                    <label class="form-label">Nombre(s) *</label>

                    <input type="text"
                           class="form-control"
                           name="nombres"
                           id="nombres"
                           maxlength="50"
                           required
                           placeholder="Ej. Juan Carlos">
                </div>


                <div class="form-group">
                    <label class="form-label">Primer Apellido *</label>

                    <input type="text"
                           class="form-control"
                           name="primer_apellido"
                           id="primer_apellido"
                           maxlength="50"
                           required
                           placeholder="Ej. Martínez">
                </div>


                <div class="form-group">
                    <label class="form-label">Segundo Apellido *</label>

                    <input type="text"
                           class="form-control"
                           name="segundo_apellido"
                           id="segundo_apellido"
                           maxlength="50"
                           required
                           placeholder="Ej. López">
                </div>

            </div>


            <hr style="margin:15px 0;border:0;border-top:1px solid var(--borde-sutil);">


            <!-- ACCESO -->
            <p style="font-weight:bold;color:var(--accent-color);margin-bottom:10px;">
                🔐 Datos de Acceso
            </p>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nombre de Usuario (RFC / ID) *
                    </label>

                    <input type="text"
                           class="form-control"
                           name="nombre_usuario"
                           id="nombre_usuario"
                           maxlength="13"
                           required
                           placeholder="Ej. ABCD123456XYZ">

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Correo Electrónico *
                    </label>

                    <input type="email"
                           class="form-control"
                           name="correo_electronico"
                           id="correo_electronico"
                           maxlength="100"
                           required
                           placeholder="usuario@correo.com">

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Contraseña
                    </label>

                    <div class="password-wrapper">

                        <input type="password"
                               class="form-control"
                               name="contrasena"
                               id="contrasena"
                               minlength="12"
                               maxlength="12"
                               placeholder="12 caracteres">

                        <button type="button"
                                class="btn-toggle-password"
                                onclick="togglePassword('contrasena',this)"
                                title="Mostrar/Ocultar contraseña">

                            <svg class="eye-icon eye-show"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>

                            </svg>

                            <svg class="eye-icon eye-hide"
                                 style="display:none;"
                                 viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2">

                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>

                            </svg>

                        </button>

                    </div>

                    <small style="color:var(--texto-secundario);">
                        Nueva cuenta: obligatoria, 12 caracteres,
                        mayúscula, minúscula y símbolo.
                        Al editar puedes dejarla vacía.
                    </small>

                </div>

            </div>


            <!-- ROL Y EMPRESA -->
            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Rol de Usuario *
                    </label>

                    <select class="form-control"
                            id="rol"
                            name="rol"
                            required
                            onchange="controlarDespliegueEmpresa()">

                        <option value="" selected disabled>
                            -- Selecciona un Rol --
                        </option>

                        <option value="ADMINISTRADOR">
                            Administrador
                        </option>

                        <option value="PROPIETARIO">
                            Propietario
                        </option>

                        <option value="RRHH">
                            RRHH
                        </option>

                    </select>

                </div>


                <div class="form-group"
                     id="seccion_empresa"
                     style="display:none;">

                    <label class="form-label">
                        Empresa / Transportista
                    </label>

                    <select class="form-control"
                            id="id_empresa"
                            name="id_empresa">

                        <option value="">
                            -- Selecciona una Empresa --
                        </option>

                        <?php foreach ($empresas as $emp): ?>

                            <option value="<?= (int)$emp['id_empresa'] ?>">
                                <?= htmlspecialchars($emp['nombre_empresa']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <div id="contenedor-alertas-usuarios"
                 class="mt-3">
            </div>


            <div class="form-actions"
                 style="margin-top:20px;display:flex;gap:12px;justify-content:flex-end">

                <button type="button"
                        class="btn-action btn-delete"
                        onclick="cerrarModalUsuario()">
                    Cancelar
                </button>

                <button type="submit"
                        class="btn-prof-primary">
                    Grabar Usuario
                </button>

            </div>

        </form>

    </div>

</div>

</div>