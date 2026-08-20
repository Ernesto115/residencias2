<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();

/* =========================================================
   EMPRESAS SEGÚN EL USUARIO
   ========================================================= */

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

/* ADMIN: ve todas las empresas */
if ($rol === 'ADMIN') {

    $sql = "SELECT * FROM empresas ORDER BY id_empresa DESC";

/* PROPIETARIO MULTIEMPRESA: solo sus empresas */
} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $sql = "SELECT e.*
            FROM empresas e
            INNER JOIN usuario_empresas ue
                ON e.id_empresa = ue.id_empresa
            WHERE ue.id_usuario = $id_usuario
            ORDER BY e.id_empresa DESC";

/* PROPIETARIO DE UNA EMPRESA: solo la suya */
} elseif ($rol === 'PROPIETARIO') {

    $sql = "SELECT *
            FROM empresas
            WHERE id_empresa = $id_empresa";

/* Otros roles no tienen acceso */
} else {

    $sql = "SELECT * FROM empresas WHERE 1 = 0";
}

$datos2 = $dbtransportistas->obtenerRegistros($sql);
?>

<div class="main-wrapper modulo-empresas">

    <!-- NAVEGACIÓN -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back"
                onclick="window.location.href='../index.php'">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <!-- CONTENIDO -->
    <section>

        <h3>EMPRESAS</h3>

        <?php include_once "../empresas/frm.php"; ?>

        <div id="contenedor3">
            <?php include_once "../empresas/tabla.php"; ?>
        </div>

    </section>

</div>

<?php
$dbtransportistas->desconectar();
?>