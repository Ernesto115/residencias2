<?php

require_once "../configuracion/sesion.php";

verificarSesion();


$requiereCambio =
    (int)(
        $_SESSION['requiere_cambio_contrasena']
        ?? 0
    );


if ($requiereCambio !== 1) {

    header(
        "Location: /index.php"
    );

    exit;
}


$nombreMostrar =
    trim(
        $_SESSION['nombre_completo']
        ?? ''
    );


if ($nombreMostrar === '') {

    $nombreMostrar =
        $_SESSION['nombre_usuario']
        ?? 'Usuario';
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Actualizar Contraseña | Transportes 1° de Mayo
    </title>

    <link
        href="/css/styles.css"
        rel="stylesheet"
    >

    <script
        src="/JS/funciones.js"
        defer
    ></script>

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LADO IZQUIERDO
         ===================================================== -->

    <section class="login-left">

        <h2>
            Consejo Binacional
            <br>
            de Transportistas
        </h2>


        <p class="login-left-description">

            Sistema Integral para la gestión de operadores,
            empresas transportistas y documentación laboral.

        </p>


        <div class="login-features">

            <h3>
                Seguridad de tu cuenta
            </h3>


            <div class="login-feature">

                <span class="login-check">
                    ✓
                </span>

                <span>
                    Utiliza una contraseña segura.
                </span>

            </div>


            <div class="login-feature">

                <span class="login-check">
                    ✓
                </span>

                <span>
                    No compartas tus credenciales.
                </span>

            </div>


            <div class="login-feature">

                <span class="login-check">
                    ✓
                </span>

                <span>
                    Protege el acceso a tu cuenta.
                </span>

            </div>

        </div>

    </section>


    <!-- =====================================================
         LADO DERECHO
         ===================================================== -->

    <section class="login-right">


        <div class="login-card-custom">


            <div class="login-system-badge">

                Seguridad de la Cuenta

            </div>


            <div class="login-main-icon">

                🔐

            </div>


            <h1>

                Actualiza tu contraseña

            </h1>


            <p class="login-card-subtitle">

                Hola,
                <strong>
                    <?= htmlspecialchars(
                        $nombreMostrar,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>.

                <br>

                Para continuar, crea una nueva contraseña segura.

            </p>


            <!-- =================================================
                 FORMULARIO
                 ================================================= -->

            <form
                id="formCambioContrasena"
                method="POST"
                autocomplete="off"
            >


                <!-- NUEVA CONTRASEÑA -->

                <div class="login-form-group">

                    <label for="nueva_contrasena">

                        Nueva contraseña

                    </label>


                    <div class="login-input-wrapper">

                        <input
                            type="password"
                            id="nueva_contrasena"
                            name="nueva_contrasena"
                            class="login-input login-password-input"
                            placeholder="Escribe tu nueva contraseña"
                            autocomplete="new-password"
                            required
                        >


                        <span
                            class="login-eye"
                            id="iconoNuevaContrasena"
                            title="Mostrar contraseña"
                        >

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />

                            </svg>

                        </span>

                    </div>

                </div>


                <!-- REQUISITOS -->

                <div
                    id="requisitosContrasena"
                    style="
                        margin-top:10px;
                        margin-bottom:18px;
                        padding:12px 14px;
                        border-radius:10px;
                        background:rgba(15,23,42,.35);
                        border:1px solid rgba(148,163,184,.25);
                        text-align:left;
                        font-size:.82rem;
                        line-height:1.7;
                    "
                >

                    <div
                        style="
                            font-weight:700;
                            margin-bottom:5px;
                        "
                    >

                        Debe incluir:

                    </div>


                    <div id="reqLongitud">
                        ❌ 12 caracteres
                    </div>


                    <div id="reqMayuscula">
                        ❌ Una mayúscula
                    </div>


                    <div id="reqMinuscula">
                        ❌ Una minúscula
                    </div>


                    <div id="reqNumero">
                        ❌ Un número
                    </div>


                    <div id="reqEspecial">
                        ❌ Un carácter especial
                    </div>

                </div>


                <!-- CONFIRMAR CONTRASEÑA -->

                <div class="login-form-group">

                    <label for="confirmar_contrasena">

                        Confirmar contraseña

                    </label>


                    <div class="login-input-wrapper">

                        <input
                            type="password"
                            id="confirmar_contrasena"
                            name="confirmar_contrasena"
                            class="login-input login-password-input"
                            placeholder="Repite tu nueva contraseña"
                            autocomplete="new-password"
                            required
                        >


                        <span
                            class="login-eye"
                            id="iconoConfirmarContrasena"
                            title="Mostrar contraseña"
                        >

                            <svg viewBox="0 0 24 24">

                                <path
                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />

                            </svg>

                        </span>

                    </div>

                </div>


                <!-- COINCIDENCIA -->

                <div
                    id="reqCoincidencia"
                    style="
                        margin-top:-8px;
                        margin-bottom:18px;
                        text-align:left;
                        font-size:.82rem;
                    "
                >

                    ❌ Las contraseñas no coinciden

                </div>


                <!-- MENSAJES -->

                <div
                    id="contenedorCambioContrasena"
                ></div>


                <!-- BOTÓN -->

                <button
                    type="submit"
                    id="btnGuardarNuevaContrasena"
                    class="login-button-custom"
                    disabled
                >

                    Guardar y continuar

                </button>


            </form>


            <!-- =================================================
                 PIE
                 ================================================= -->

            <div class="login-footer">

                © 2026 Consejo Binacional de Transportistas

                <br>

                Todos los derechos reservados a
                Transportes 1° de Mayo S.A. de C.V.


                <div class="login-version">

                    Versión 1.0.2

                </div>

            </div>


        </div>

    </section>

</div>


</body>

</html>