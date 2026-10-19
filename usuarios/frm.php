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


/* =========================================================
   CONSULTAR PROPIETARIO ACTUAL
   ========================================================= */

/*
 * No confiamos solamente en:
 *
 * $_SESSION['id_empresa']
 * $_SESSION['multiempresa']
 *
 * Consultamos nuevamente MySQL para conocer
 * la situación actual del propietario.
 */

require_once __DIR__ . "/../DB/db.php";


$dbFrm =
    new db();


$dbFrm->conectar();


$idPropietario =
    (int)(
        $_SESSION['id_usuario']
        ?? 0
    );


$stmtPropietario =
    $dbFrm->conn->prepare(

        "SELECT

            u.id_usuario,

            u.id_empresa,

            u.multiempresa,

            u.rol,

            e.nombre_empresa

         FROM usuarios u

         LEFT JOIN empresas e
            ON
                e.id_empresa =
                u.id_empresa

         WHERE
            u.id_usuario =
            :id_usuario

         LIMIT 1"
    );


$stmtPropietario->execute([

    ':id_usuario' =>
        $idPropietario

]);


$propietario =
    $stmtPropietario->fetch(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   VALIDAR PROPIETARIO
   ========================================================= */

if (
    !$propietario ||
    strtoupper(
        trim(
            $propietario['rol']
            ?? ''
        )
    ) !== 'PROPIETARIO'
) {

    $dbFrm->desconectar();


    http_response_code(403);


    echo '
        <div class="alert alert-danger">
            No fue posible validar la cuenta del propietario.
        </div>
    ';


    return;
}


/* =========================================================
   DATOS DE EMPRESA
   ========================================================= */

$idEmpresaPropietario =
    (int)(
        $propietario[
            'id_empresa'
        ] ?? 0
    );


$esMultiempresa =
    (int)(
        $propietario[
            'multiempresa'
        ] ?? 0
    ) === 1;


$nombreEmpresaPropietario =
    trim(
        $propietario[
            'nombre_empresa'
        ] ?? ''
    );


$empresaUnicaValida =
    !$esMultiempresa &&
    $idEmpresaPropietario > 0 &&
    $nombreEmpresaPropietario !== '';


/* =========================================================
   EMPRESAS AUTORIZADAS - MULTIEMPRESA
   ========================================================= */

/*
 * Si el propietario es multiempresa,
 * únicamente cargamos las empresas
 * relacionadas mediante usuario_empresas.
 *
 * NO se consulta el catálogo global
 * de empresas.
 */

$empresasAutorizadas =
    [];


if ($esMultiempresa) {

    $stmtEmpresas =
        $dbFrm->conn->prepare(

            "SELECT

                e.id_empresa,

                e.nombre_empresa

             FROM usuario_empresas ue

             INNER JOIN empresas e
                ON
                    e.id_empresa =
                    ue.id_empresa

             WHERE
                ue.id_usuario =
                :id_usuario

             ORDER BY
                e.nombre_empresa"
        );


    $stmtEmpresas->execute([

        ':id_usuario' =>
            $idPropietario

    ]);


    $empresasAutorizadas =
        $stmtEmpresas->fetchAll(
            PDO::FETCH_ASSOC
        );
}


/* =========================================================
   VALIDAR EMPRESAS MULTIEMPRESA
   ========================================================= */

$tieneEmpresasAutorizadas =
    $esMultiempresa &&
    !empty(
        $empresasAutorizadas
    );


/* =========================================================
   CERRAR CONEXIÓN
   ========================================================= */

$dbFrm->desconectar();


/* =========================================================
   ESCAPAR HTML
   ========================================================= */

$h =
    fn($valor) =>
        htmlspecialchars(
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );

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
                El envío todavía se encuentra
                deshabilitado.

                Primero terminaremos:

                - Seguridad backend
                - Contraseña temporal
                - Cambio obligatorio
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


                <hr
                    style="
                        margin:15px 0;
                        border:0;
                        border-top:1px solid var(--borde-sutil);
                    "
                >


                <!-- =================================================
                     EMPRESA DEL RRHH
                     ================================================= -->

                <p
                    style="
                        font-weight:bold;
                        color:var(--accent-color);
                        margin-bottom:10px;
                    "
                >

                    🏢 Empresa del RRHH

                </p>


                <!-- =================================================
                     PROPIETARIO DE UNA EMPRESA
                     ================================================= -->

                <?php if ($empresaUnicaValida): ?>


                    <div class="form-row">


                        <div class="form-group">


                            <label class="form-label">

                                Empresa asignada

                            </label>


                            <!--
                                IMPORTANTE:

                                Este campo NO tiene atributo name.

                                Es únicamente informativo.

                                El backend obtendrá nuevamente
                                el id_empresa desde la cuenta
                                autenticada del propietario.
                            -->

                            <input
                                type="text"
                                class="form-control"
                                value="<?= $h($nombreEmpresaPropietario) ?>"
                                readonly
                                aria-readonly="true"
                            >


                            <small
                                style="
                                    color:var(--texto-secundario);
                                    display:block;
                                    margin-top:6px;
                                    line-height:1.45;
                                "
                            >

                                El nuevo RRHH será vinculado
                                automáticamente a esta empresa.

                            </small>


                        </div>


                    </div>


                <!-- =================================================
                     PROPIETARIO MULTIEMPRESA
                     ================================================= -->

                <?php elseif (
                    $esMultiempresa &&
                    $tieneEmpresasAutorizadas
                ): ?>


                    <div class="form-row">


                        <div class="form-group">


                            <label class="form-label">

                                Empresa / Transportista *

                            </label>


                            <!--
                                En una cuenta multiempresa es necesario
                                enviar una empresa seleccionada.

                                IMPORTANTE:

                                El backend NO confiará únicamente
                                en este valor.

                                Cuando habilitemos el guardado,
                                PHP comprobará nuevamente que
                                id_empresa pertenece al propietario
                                mediante usuario_empresas.
                            -->

                            <select
                                class="form-control"
                                id="id_empresa"
                                name="id_empresa"
                                required
                            >


                                <option
                                    value=""
                                    selected
                                    disabled
                                >

                                    -- Selecciona una Empresa --

                                </option>


                                <?php foreach (
                                    $empresasAutorizadas
                                    as
                                    $empresa
                                ): ?>


                                    <option
                                        value="<?= (int)$empresa['id_empresa'] ?>"
                                    >

                                        <?= $h(
                                            $empresa[
                                                'nombre_empresa'
                                            ] ?? ''
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                            <small
                                style="
                                    color:var(--texto-secundario);
                                    display:block;
                                    margin-top:6px;
                                    line-height:1.45;
                                "
                            >

                                Solo se muestran las empresas
                                autorizadas para su cuenta.

                            </small>


                        </div>


                    </div>


                <!-- =================================================
                     MULTIEMPRESA SIN EMPRESAS AUTORIZADAS
                     ================================================= -->

                <?php elseif ($esMultiempresa): ?>


                    <div
                        style="
                            padding:14px 16px;
                            border:1px solid rgba(239,68,68,.35);
                            border-radius:10px;
                            background:rgba(239,68,68,.08);
                            color:var(--texto-secundario);
                            font-size:.82rem;
                            line-height:1.5;
                        "
                    >


                        <strong
                            style="
                                color:#ef4444;
                            "
                        >

                            ⚠️ Sin empresas autorizadas

                        </strong>


                        <div
                            style="
                                margin-top:5px;
                            "
                        >

                            Esta cuenta está configurada como
                            multiempresa, pero no tiene empresas
                            relacionadas actualmente.

                            No se podrá crear un RRHH hasta corregir
                            esta asignación.

                        </div>


                    </div>


                <!-- =================================================
                     SIN EMPRESA VÁLIDA
                     ================================================= -->

                <?php else: ?>


                    <div
                        style="
                            padding:14px 16px;
                            border:1px solid rgba(239,68,68,.35);
                            border-radius:10px;
                            background:rgba(239,68,68,.08);
                            color:var(--texto-secundario);
                            font-size:.82rem;
                            line-height:1.5;
                        "
                    >


                        <strong
                            style="
                                color:#ef4444;
                            "
                        >

                            ⚠️ Empresa no disponible

                        </strong>


                        <div
                            style="
                                margin-top:5px;
                            "
                        >

                            No fue posible identificar una empresa
                            válida asociada a esta cuenta.

                            No se podrá crear un RRHH hasta corregir
                            esta asignación.

                        </div>


                    </div>


                <?php endif; ?>


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

                        Se habilitará después de terminar:

                        2D-D Seguridad backend
                        2D-E Contraseña temporal
                        2D-F Cambio obligatorio
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