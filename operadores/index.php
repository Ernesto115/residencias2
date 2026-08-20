<?php
/* =========================================================
   MÓDULO DE OPERADORES
   ========================================================= */

include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();
?>

<div class="main-wrapper modulo-operadores">

    <!-- NAVEGACIÓN -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="window.location.href='../index.php'">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <!-- CONTENIDO -->
    <section>
        <h3>OPERADORES</h3>

        <!-- Formulario -->
        <?php include_once "../operadores/frm.php"; ?>

        <!-- Tabla -->
        <div id="contenedor3">
            <?php include_once "../operadores/tabla.php"; ?>
        </div>
    </section>

</div>

<?php
$dbtransportistas->desconectar();
?>