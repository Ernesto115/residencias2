<?php 
include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();

$id = ( isset($_REQUEST['id']) ? $_REQUEST['id'] : '0' );

// 1. Obtener lista completa de empresas para el <select> en frm.php
$sql_empresas = "SELECT * FROM empresas ORDER BY id_empresa DESC";
$empresas = $dbtransportistas->obtenerRegistros($sql_empresas);

// 2. Obtener usuarios con los datos de su empresa asociada para la tabla
$sql = "SELECT u.*, e.nombre_empresa 
        FROM usuarios u 
        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa 
        ORDER BY u.id_usuario DESC";
$datos2 = $dbtransportistas->obtenerRegistros($sql);

$dbtransportistas->desconectar();
?>

<div class="main-wrapper modulo-usuarios">
    <!-- Menú de Navegación / Controles Superiores Alineados a la Derecha -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>USUARIOS</h3>

        <!-- Formulario de Registro / Edición -->
        <?php include_once "../usuarios/frm.php"; ?>

        <!-- Contenedor dinámico para la tabla de resultados -->
        <div id="contenedor3" class="mt-4">
            <?php include_once "../usuarios/tabla.php"; ?> 
        </div>
    </section>
</div>