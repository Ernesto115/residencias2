<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$rolFormulario =
    strtoupper(
        trim($_SESSION['rol'] ?? '')
    );


if ($rolFormulario === 'ADMINISTRADOR') {
    $rolFormulario = 'ADMIN';
}


if (
    !in_array(
        $rolFormulario,
        ['ADMIN','PROPIETARIO'],
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


<!-- BOTÓN AGREGAR -->
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



<!-- MODAL -->
<div
    id="modalEmpresa"
    class="modal-overlay"
>

    <div class="modal-container">


        <!-- ENCABEZADO -->
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



        <div class="modal-body-scroll">


            <form
                id="frm"
                class="form-grid"
                action="javascript:void(0);"
                onsubmit="guardar('empresas','frm',event)"
            >


                <input
                    type="hidden"
                    id="id_empresa"
                    name="id_empresa"
                    value=""
                >



                <!-- DATOS PRINCIPALES -->
                <div class="form-row">


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



                <div
                    id="contenedor-alertas-empresas"
                    class="mt-3"
                >
                </div>



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