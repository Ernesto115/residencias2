<?php

/* =========================================================
   SESIÓN CENTRALIZADA
   ========================================================= */

require_once __DIR__ . "/../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   ROL DE LA SESIÓN
   ========================================================= */

$rolSesion =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if ($rolSesion === 'ADMINISTRADOR') {

    $rolSesion = 'ADMIN';
}


/* =========================================================
   SOLO PROPIETARIO
   ========================================================= */

/*
 * La creación de cuentas RRHH corresponde
 * exclusivamente al PROPIETARIO.
 *
 * ADMIN no utiliza este formulario.
 * RRHH tampoco puede acceder.
 */

if ($rolSesion !== 'PROPIETARIO') {

    http_response_code(403);

    echo '
        <div class="alert alert-danger">
            No tienes permiso para crear cuentas de RRHH.
        </div>
    ';

    return;
}

?>


<!-- =========================================================
     BOTÓN AGREGAR RRHH
     ========================================================= -->

<div class="table-header-title">

    <div class="table-tabs-wrapper">

        <button
            type="button"
            class="btn-agregar-op"
            onclick="abrirModalUsuario()"
        >
            + Agregar RRHH
        </button>

    </div>

</div>


<!-- =========================================================
     MODAL RRHH
     ========================================================= -->

<div
    id="modalUsuario"
    class="modal-overlay"
>


    <div class="modal-container">


        <!-- =================================================
             ENCABEZADO
             ================================================= -->

        <div class="modal-header">


            <h2 class="modal-title-text">

                Nuevo RRHH

            </h2>


            <button
                type="button"
                class="btn-cerrar-modal"
                onclick="cerrarModalUsuario()"
            >
                &times;
            </button>


        </div>


        <!-- =================================================
             CONTENIDO
             ================================================= -->

        <div class="modal-body-scroll">


            <!--
                El envío se habilitará cuando terminemos
                la validación del backend para PROPIETARIO.
            -->

            <form
                id="formGuardarUsuario"
                class="form-grid"
                action="javascript:void(0);"
                onsubmit="return false;"
            >


                <!-- =================================================
                     ID
                     ================================================= -->

                <input
                    type="hidden"
                    id="id_usuario"
                    name="id_usuario"
                    value=""
                >


                <!-- =================================================
                     DATOS PERSONALES
                     ================================================= -->

                <p
                    style="
                        font-weight:bold;
                        color:var(--accent-color);
                        margin-bottom:10px;
                    "
                >
                    👤 Datos Personales
                </p>


                <div class="form-row">


                    <!-- NOMBRES -->

                    <div class="form-group">


                        <label class="form-label">

                            Nombre(s) *

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="nombres"
                            id="nombres"
                            maxlength="50"
                            required
                            autocomplete="off"
                            placeholder="Ej. Juan Carlos"
                        >


                    </div>


                    <!-- PRIMER APELLIDO -->

                    <div class="form-group">


                        <label class="form-label">

                            Primer Apellido *

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="primer_apellido"
                            id="primer_apellido"
                            maxlength="50"
                            required
                            autocomplete="off"
                            placeholder="Ej. Martínez"
                        >


                    </div>


                    <!-- SEGUNDO APELLIDO -->

                    <div class="form-group">


                        <label class="form-label">

                            Segundo Apellido *

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="segundo_apellido"
                            id="segundo_apellido"
                            maxlength="50"
                            required
                            autocomplete="off"
                            placeholder="Ej. López"
                        >


                    </div>


                </div>


                <hr
                    style="
                        margin:15px 0;
                        border:0;
                        border-top:1px solid var(--borde-sutil);
                    "
                >


                <!-- =================================================
                     DATOS DE ACCESO
                     ================================================= -->

                <p
                    style="
                        font-weight:bold;
                        color:var(--accent-color);
                        margin-bottom:10px;
                    "
                >
                    🔐 Datos de Acceso
                </p>


                <div class="form-row">


                    <!-- RFC / USUARIO -->

                    <div class="form-group">


                        <label class="form-label">

                            Nombre de Usuario (RFC / ID) *

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="nombre_usuario"
                            id="nombre_usuario"
                            maxlength="13"
                            required
                            autocomplete="off"
                            placeholder="Ej. ABCD123456XYZ"
                        >


                    </div>


                    <!-- CORREO -->

                    <div class="form-group">


                        <label class="form-label">

                            Correo Electrónico *

                        </label>


                        <input
                            type="email"
                            class="form-control"
                            name="correo_electronico"
                            id="correo_electronico"
                            maxlength="100"
                            required
                            autocomplete="off"
                            placeholder="usuario@correo.com"
                        >


                    </div>


                </div>


                <!-- =================================================
                     INFORMACIÓN DEL SISTEMA
                     ================================================= -->

                <div
                    style="
                        margin-top:18px;
                        padding:14px 16px;
                        border:1px solid var(--borde-sutil);
                        border-radius:10px;
                        background:var(--input-bg);
                        color:var(--texto-secundario);
                        font-size:.82rem;
                        line-height:1.5;
                    "
                >

                    <strong
                        style="
                            color:var(--texto-principal);
                        "
                    >
                        🔐 Acceso protegido
                    </strong>


                    <div
                        style="
                            margin-top:5px;
                        "
                    >

                        El propietario no establecerá la contraseña
                        del nuevo RRHH.

                        El sistema generará automáticamente una
                        contraseña temporal segura y la cuenta deberá
                        cambiarla durante su primer inicio de sesión.

                    </div>

                </div>


                <!-- =================================================
                     ALERTAS
                     ================================================= -->

                <div
                    id="contenedor-alertas-usuarios"
                    class="mt-3"
                >
                </div>


                <!-- =================================================
                     ACCIONES
                     ================================================= -->

                <div
                    class="form-actions"
                    style="
                        margin-top:20px;
                        display:flex;
                        gap:12px;
                        justify-content:flex-end;
                    "
                >


                    <button
                        type="button"
                        class="btn-action btn-delete"
                        onclick="cerrarModalUsuario()"
                    >

                        Cancelar

                    </button>


                    <!--
                        TEMPORALMENTE DESHABILITADO.

                        Se habilitará cuando terminemos:

                        - empresa automática
                        - multiempresa
                        - autorización backend
                        - contraseña temporal
                    -->

                    <button
                        type="button"
                        class="btn-prof-primary"
                        disabled
                        title="La creación se habilitará al terminar la configuración segura del RRHH."
                    >

                        Crear RRHH

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>