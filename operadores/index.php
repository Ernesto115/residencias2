<?php
require_once "../configuracion/sesion.php";
verificarSesion();

$rolModulo = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rolModulo === 'ADMINISTRADOR') $rolModulo = 'ADMIN';
if (in_array($rolModulo, ['RH','RECURSOS HUMANOS'], true)) $rolModulo = 'RRHH';

if (!in_array($rolModulo, ['ADMIN','PROPIETARIO','RRHH'], true)) {
    http_response_code(403);
    echo '<div class="alert alert-danger">No tienes permiso para acceder a Operadores.</div>';
    exit;
}

include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();
?>

<div class="main-wrapper modulo-operadores">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back"
                onclick="window.location.href='../index.php'">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>OPERADORES</h3>

        <?php include "../operadores/frm.php"; ?>

        <div id="contenedor3">
            <?php include "../operadores/tabla.php"; ?>
        </div>
    </section>

</div>

<?php $dbtransportistas->desconectar(); ?>