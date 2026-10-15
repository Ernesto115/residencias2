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
   ROLES PERMITIDOS
   ========================================================= */

if (
    !in_array(
        $rolSesion,
        [
            'ADMIN',
            'PROPIETARIO'
        ],
        true
    )
) {

    http_response_code(403);

    exit(
        '<div class="alert alert-danger">
            No tienes permiso para acceder al módulo de usuarios.
        </div>'
    );
}


/* =========================================================
   VARIABLES GENERALES
   ========================================================= */

$dbtransportistas =
    null;


$empresas =
    [];


/* =========================================================
   ADMINISTRADOR
   ========================================================= */

/*
 * ADMIN conserva por ahora:
 *
 * - Tabla global
 * - Filtros
 * - Edición existente
 * - Activar / desactivar
 *
 * Pero ya NO crea usuarios desde este módulo.
 */

if ($rolSesion === 'ADMIN') {

    require_once __DIR__ . "/../DB/db.php";


    $dbtransportistas =
        new db();


    $dbtransportistas->conectar();
}

?>


<div class="main-wrapper modulo-usuarios">


    <!-- =====================================================
         NAVEGACIÓN
         ===================================================== -->

    <nav
        class="
            d-flex
            justify-content-end
            align-items-center
            gap-2
            p-3
        "
    >


        <button
            type="button"
            class="btn-back"
            onclick="window.location.href='/index.php'"
        >

            ⬅️ Volver al Inicio

        </button>


    </nav>


    <section>


        <!-- =================================================
             ADMINISTRADOR
             ================================================= -->

        <?php if (
            $rolSesion === 'ADMIN'
        ): ?>


            <h3>

                USUARIOS

            </h3>


            <!--
                ADMIN ya no carga frm.php.

                Su función será supervisar
                las cuentas del sistema.
            -->


            <div
                id="contenedor3"
                class="mt-4"
            >


                <?php

                    include __DIR__ . "/tabla.php";

                ?>


            </div>


        <!-- =================================================
             PROPIETARIO
             ================================================= -->

        <?php elseif (
            $rolSesion === 'PROPIETARIO'
        ): ?>


            <h3>

                RECURSOS HUMANOS

            </h3>


            <!-- =============================================
                 FORMULARIO RRHH
                 ============================================= -->

            <?php

                include __DIR__ . "/frm.php";

            ?>


            <!-- =============================================
                 TABLA RRHH
                 ============================================= -->

            <div
                id="contenedor3"
                class="mt-4"
            >


                <?php

                    include __DIR__ . "/tabla.php";

                ?>


            </div>


        <?php endif; ?>


    </section>


</div>


<?php

/* =========================================================
   CERRAR CONEXIÓN DEL ADMIN
   ========================================================= */

if (
    $dbtransportistas !== null
) {

    $dbtransportistas->desconectar();
}

?>