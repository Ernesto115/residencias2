<?php include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();


$id = ( isset($_REQUEST['id']) ? $_REQUEST['id'] : '0' );


$sql = "SELECT * FROM empresas";
$datos2= $dbtransportistas->obtenerRegistros($sql);

?>

<div class="main-wrapper modulo-empresas">
    <!-- Menú de Navegación / Controles Superiores Alineados a la Derecha -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>EMPRESAS</h3>

        <?php include_once "../empresas/frm.php";?>
        <div id="contenedor3">

        <div id="contenedor3">
            <?php include_once "../empresas/tabla.php";?> 
        </div>

    </section>
</div>