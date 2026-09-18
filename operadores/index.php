<?php

require_once "../configuracion/sesion.php";

verificarSesion();


define(
    'OPERADORES_TABLA_INTERNA',
    true
);


/* =========================================================
   ROL DEL MÓDULO
   ========================================================= */

$rolModulo =
    strtoupper(
        trim($_SESSION['rol'] ?? '')
    );


// ... TODO TU CÓDIGO ACTUAL DE ROLES ...


include_once "../db/db.php";

$dbtransportistas = new db();

$dbtransportistas->conectar();


/* =========================================================
   PETICIÓN SOLO DE TABLA
   ========================================================= */

if (
    isset($_GET['solo_tabla']) &&
    $_GET['solo_tabla'] === '1'
) {

    include "../operadores/tabla.php";

    $dbtransportistas->desconectar();

    exit;
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

include_once "../db/db.php";

$dbtransportistas = new db();

$dbtransportistas->conectar();

?>


<div class="main-wrapper modulo-operadores">


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
            onclick="window.location.href='../index.php'"
        >

            ⬅️ Volver al Inicio

        </button>

    </nav>


    <section>


        <h3>
            OPERADORES
        </h3>


        <!-- =================================================
             HERRAMIENTAS CSV

             ADMIN:
             solo consulta.

             PROPIETARIO / RRHH:
             pueden realizar cargas masivas.
             ================================================= -->

        <?php if (
            in_array(
                $rolModulo,
                ['PROPIETARIO', 'RRHH'],
                true
            )
        ): ?>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    align-items:center;
                    flex-wrap:wrap;
                    gap:10px;
                    margin:0 0 18px 0;
                "
            >

                            <!-- Descargar plantilla -->

            <button
                type="button"
                class="btn-action btn-info"
                id="btnPlantillaOperadores"
                onclick="window.location.href='/operadores/plantilla_excel.php'"
                title="Descargar plantilla para alta masiva de operadores"
                >
                ⬇️ Descargar plantilla Excel
                </button>


                <!-- IMPORTAR CSV -->

                <button
                type="button"
                class="btn-action btn-edit"
                id="btnImportarOperadoresExcel"
                onclick="window.location.href='/operadores/importar_excel.php'"
                title="Importar operadores desde un archivo Excel"
                >
                    📊 Importar Excel
                    </button>


            </div>


        <?php endif; ?>


        <!-- =================================================
             REGISTRO MANUAL
             NO SE MODIFICA
             ================================================= -->

        <?php
        include "../operadores/frm.php";
        ?>


        <!-- =================================================
             TABLA
             NO SE MODIFICA
             ================================================= -->

        <div id="contenedor3">

            <?php
            include "../operadores/tabla.php";
            ?>

        </div>


    </section>


</div>


<?php

$dbtransportistas->desconectar();

?>