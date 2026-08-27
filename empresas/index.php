<?php
require_once "../configuracion/sesion.php";
verificarSesion();

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}


/* =========================================================
   SOLO ADMIN Y PROPIETARIO
   ========================================================= */

if (!in_array($rol, ['ADMIN','PROPIETARIO'], true)) {

    http_response_code(403);

    exit(
        '<div class="alert alert-danger">
            No tienes permiso para administrar empresas.
        </div>'
    );
}


include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();


$id_usuario =
    (int)($_SESSION['id_usuario'] ?? 0);

$id_empresa =
    (int)($_SESSION['id_empresa'] ?? 0);

$multiempresa =
    (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   EMPRESAS SEGÚN EL ROL
   ========================================================= */

if ($rol === 'ADMIN') {

    $sql =
        "SELECT *
         FROM empresas
         ORDER BY id_empresa DESC";


} elseif ($multiempresa === 1) {

    $sql =
        "SELECT e.*
         FROM empresas e

         INNER JOIN usuario_empresas ue
            ON ue.id_empresa = e.id_empresa

         WHERE ue.id_usuario = $id_usuario

         ORDER BY e.id_empresa DESC";


} elseif ($id_empresa > 0) {

    $sql =
        "SELECT *
         FROM empresas
         WHERE id_empresa = $id_empresa";


} else {

    $sql =
        "SELECT *
         FROM empresas
         WHERE 1 = 0";
}


$datos2 =
    $dbtransportistas->obtenerRegistros($sql);
?>


<div class="main-wrapper modulo-empresas">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">

        <button
            type="button"
            class="btn-back"
            onclick="window.location.href='../index.php'"
        >
            ⬅️ Volver al Inicio
        </button>

    </nav>


    <section>

        <h3>EMPRESAS</h3>


        <?php
        include "../empresas/frm.php";
        ?>


        <div id="contenedor3">

            <?php
            include "../empresas/tabla.php";
            ?>

        </div>

    </section>

</div>


<?php
$dbtransportistas->desconectar();
?>