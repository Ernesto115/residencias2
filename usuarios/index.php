<?php

/* =========================================================
   1. INICIAR SESIÓN
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   2. CONEXIÓN A BASE DE DATOS
   ========================================================= */

include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '0';


/* =========================================================
   3. EMPRESAS PARA EL FORMULARIO
   ========================================================= */

$sql_empresas = "SELECT *
                 FROM empresas
                 ORDER BY id_empresa DESC";

$empresas = $dbtransportistas->obtenerRegistros($sql_empresas);


/* =========================================================
   4. USUARIOS
   ========================================================= */

$sql = "SELECT u.*, e.nombre_empresa
        FROM usuarios u
        LEFT JOIN empresas e
            ON u.id_empresa = e.id_empresa
        ORDER BY u.id_usuario DESC";

$datos2 = $dbtransportistas->obtenerRegistros($sql);

$dbtransportistas->desconectar();

?>


<div class="main-wrapper modulo-usuarios">

    <!-- NAVEGACIÓN -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">

        <button
            type="button"
            class="btn-back"
            onclick="irAApartadoEmpresas()"
        >
            ⬅️ Volver al Inicio
        </button>

    </nav>


    <section>

        <h3>USUARIOS</h3>


        <!-- FORMULARIO -->
        <?php include_once "../usuarios/frm.php"; ?>


        <!-- TABLA -->
        <div id="contenedor3" class="mt-4">

            <?php include_once "../usuarios/tabla.php"; ?>

        </div>

    </section>

</div>