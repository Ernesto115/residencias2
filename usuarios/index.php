<?php
require_once "../configuracion/sesion.php";
verificarSesion();

$rolSesion = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rolSesion === 'ADMINISTRADOR') $rolSesion = 'ADMIN';

if ($rolSesion !== 'ADMIN') {
    http_response_code(403);
    exit('<div class="alert alert-danger">No tienes permiso para administrar usuarios.</div>');
}

include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();

$empresas = $dbtransportistas->obtenerRegistros(
    "SELECT id_empresa,nombre_empresa
     FROM empresas
     ORDER BY nombre_empresa"
);
?>

<div class="main-wrapper modulo-usuarios">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button"
                class="btn-back"
                onclick="window.location.href='/index.php'">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>

        <h3>USUARIOS</h3>

        <?php include "../usuarios/frm.php"; ?>

        <div id="contenedor3" class="mt-4">
            <?php include "../usuarios/tabla.php"; ?>
        </div>

    </section>

</div>

<?php $dbtransportistas->desconectar(); ?>