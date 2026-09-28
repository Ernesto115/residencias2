<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   ROL DE LA SESIÓN
   ========================================================= */

$rolFormulario =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if ($rolFormulario === 'ADMINISTRADOR') {

    $rolFormulario = 'ADMIN';

}


/* =========================================================
   SEGURIDAD
   ========================================================= */

if (
    !in_array(
        $rolFormulario,
        [
            'ADMIN',
            'PROPIETARIO'
        ],
        true
    )
) {

    http_response_code(403);


    echo '
        <div class="alert alert-danger">
            No tienes permiso para administrar empresas.
        </div>
    ';


    return;
}

?>


<!-- =========================================================
     BOTÓN AGREGAR
     ========================================================= -->

<div class="table-header-title">

    <div class="table-tabs-wrapper">

        <button
            type="button"
            class="btn-agregar-op"
            onclick="abrirModalEmpresa()"
        >
            + Agregar Empresa
        </button>

    </div>

</div>



<!-- =========================================================
     MODAL EMPRESA
     ========================================================= -->

<div
    id="modalEmpresa"
    class="modal-overlay"
>

    <div class="modal-container">


        <!-- =====================================================
             ENCABEZADO
             ===================================================== -->

        <div class="modal-header">

            <h2 class="modal-title-text">
                Formulario de Empresa
            </h2>


            <button
                type="button"
                class="btn-cerrar-modal"
                onclick="cerrarModalEmpresa()"
            >
                &times;
            </button>

        </div>



        <!-- =====================================================
             CUERPO DEL MODAL
             ===================================================== -->

        <div class="modal-body-scroll">


            <form
                id="frm"
                class="form-grid"
                action="javascript:void(0);"
                onsubmit="guardar('empresas','frm',event)"
            >


                <!-- =================================================
                     ID DE EMPRESA

                     VACÍO   = NUEVA EMPRESA
                     CON ID  = EDITAR EMPRESA
                     ================================================= -->

                <input
                    type="hidden"
                    id="id_empresa"
                    name="id_empresa"
                    value=""
                >



                <!-- =================================================
                     ROL ACTUAL

                     JavaScript lo utiliza para decidir si debe
                     mostrar la sección del propietario.
                     ================================================= -->

                <input
                    type="hidden"
                    id="rol_formulario_empresa"
                    value="<?= htmlspecialchars(
                        $rolFormulario,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >



                <!-- =================================================
                     DATOS PRINCIPALES DE LA EMPRESA
                     ================================================= -->

                <p
                    style="
                        font-weight:bold;
                        color:var(--accent-color);
                        margin-bottom:10px;
                    "
                >
                    🏢 Datos de la Empresa
                </p>



                <div class="form-row">


                    <!-- NOMBRE COMERCIAL -->
                    <div class="form-group">

                        <label class="form-label">
                            Nombre Comercial de la Empresa
                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="nombre_empresa"
                            id="nombre_empresa"
                            required
                            maxlength="100"
                            placeholder="Ej. Logística Express"
                        >

                    </div>



                    <!-- RAZÓN SOCIAL -->
                    <div class="form-group">

                        <label class="form-label">
                            Razón Social
                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="razon_social"
                            id="razon_social"
                            required
                            maxlength="150"
                            placeholder="Ej. Logística Express S.A. de C.V."
                        >

                    </div>


                </div>



                <div class="form-row">


                    <!-- DIRECCIÓN FISCAL -->
                    <div
                        class="form-group"
                        style="flex:2;"
                    >

                        <label class="form-label">
                            Dirección Fiscal
                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="direccion_fiscal"
                            id="direccion_fiscal"
                            required
                            maxlength="200"
                            placeholder="Ej. Av. Hidalgo #456, Col. Centro"
                        >

                    </div>



                    <!-- RESPONSABLE ADMINISTRATIVO -->
                    <div class="form-group">

                        <label class="form-label">
                            Nombre del Responsable Administrativo
                        </label>


                        <input
                            type="text"
                            class="form-control"
                            name="responsable"
                            id="responsable"
                            required
                            maxlength="100"
                            placeholder="Ej. Lic. Roberto Gómez"
                        >

                    </div>


                </div>



                <!-- =================================================
                     CUENTA DEL PROPIETARIO

                     Se muestra únicamente cuando:

                     - La sesión es ADMIN.
                     - Se está creando una empresa nueva.

                     La contraseña NO se captura manualmente.
                     El sistema la generará automáticamente.
                     ================================================= -->

                <div
                    id="seccion_propietario_empresa"
                    style="display:none;"
                >


                    <hr
                        style="
                            margin:20px 0;
                            border:0;
                            border-top:1px solid var(--borde-sutil);
                        "
                    >



                    <p
                        style="
                            font-weight:bold;
                            color:var(--accent-color);
                            margin-bottom:10px;
                        "
                    >
                        👤 Cuenta del Propietario
                    </p>



                    <small
                        style="
                            display:block;
                            color:var(--texto-secundario);
                            margin-bottom:15px;
                        "
                    >
                        Esta cuenta tendrá acceso al sistema como
                        propietario de la empresa registrada.
                    </small>



                    <!-- =============================================
                         DATOS PERSONALES DEL PROPIETARIO
                         ============================================= -->

                    <div class="form-row">


                        <!-- NOMBRES -->
                        <div class="form-group">

                            <label class="form-label">
                                Nombre(s) *
                            </label>


                            <input
                                type="text"
                                class="
                                    form-control
                                    campo-propietario-empresa
                                "
                                name="prop_nombres"
                                id="prop_nombres"
                                maxlength="50"
                                placeholder="Ej. Juan Carlos"
                                disabled
                            >

                        </div>



                        <!-- PRIMER APELLIDO -->
                        <div class="form-group">

                            <label class="form-label">
                                Primer Apellido *
                            </label>


                            <input
                                type="text"
                                class="
                                    form-control
                                    campo-propietario-empresa
                                "
                                name="prop_primer_apellido"
                                id="prop_primer_apellido"
                                maxlength="50"
                                placeholder="Ej. Martínez"
                                disabled
                            >

                        </div>



                        <!-- SEGUNDO APELLIDO -->
                        <div class="form-group">

                            <label class="form-label">
                                Segundo Apellido *
                            </label>


                            <input
                                type="text"
                                class="
                                    form-control
                                    campo-propietario-empresa
                                "
                                name="prop_segundo_apellido"
                                id="prop_segundo_apellido"
                                maxlength="50"
                                placeholder="Ej. López"
                                disabled
                            >

                        </div>


                    </div>



                    <!-- =============================================
                         DATOS DE ACCESO DEL PROPIETARIO
                         ============================================= -->

                    <div class="form-row">


                        <!-- NOMBRE DE USUARIO / RFC -->
                        <div class="form-group">

                            <label class="form-label">
                                Nombre de Usuario (RFC / ID) *
                            </label>


                            <input
                                type="text"
                                class="
                                    form-control
                                    campo-propietario-empresa
                                "
                                name="prop_nombre_usuario"
                                id="prop_nombre_usuario"
                                maxlength="13"
                                placeholder="Ej. ABCD123456XYZ"
                                disabled
                            >

                        </div>



                        <!-- CORREO -->
                        <div class="form-group">

                            <label class="form-label">
                                Correo Electrónico *
                            </label>


                            <input
                                type="email"
                                class="
                                    form-control
                                    campo-propietario-empresa
                                "
                                name="prop_correo_electronico"
                                id="prop_correo_electronico"
                                maxlength="100"
                                placeholder="usuario@correo.com"
                                disabled
                            >

                        </div>


                    </div>



                    <!-- =============================================
                         CONTRASEÑA TEMPORAL AUTOMÁTICA
                         ============================================= -->

                    <div
                        style="
                            margin-top:10px;
                            padding:14px 16px;
                            border:1px solid var(--borde-sutil);
                            border-radius:10px;
                            background:rgba(59,130,246,0.08);
                        "
                    >

                        <div
                            style="
                                font-weight:700;
                                color:var(--accent-color);
                                margin-bottom:5px;
                            "
                        >
                            🔐 Contraseña temporal automática
                        </div>


                        <small
                            style="
                                color:var(--texto-secundario);
                                line-height:1.5;
                            "
                        >
                            El sistema generará automáticamente una
                            contraseña temporal segura para el propietario
                            al registrar la empresa.
                        </small>

                    </div>


                </div>



                <!-- =================================================
                     CONTENEDOR DE ALERTAS
                     ================================================= -->

                <div
                    id="contenedor-alertas-empresas"
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
                        onclick="cerrarModalEmpresa()"
                    >
                        Cancelar
                    </button>



                    <button
                        type="submit"
                        class="btn-prof-primary"
                    >
                        Grabar Empresa
                    </button>


                </div>


            </form>


        </div>

    </div>

</div>