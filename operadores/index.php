<?php 
include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '0');

// --- LÓGICA DE PAGINACIÓN ---
$registros_por_pagina = 5; // Cambia este valor para mostrar más o menos filas por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Total de registros
$sql_total = "SELECT COUNT(*) AS total FROM operadores";
$res_total = $dbtransportistas->obtenerRegistros($sql_total);
$total_registros = isset($res_total[0]['total']) ? $res_total[0]['total'] : 0;
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta paginada
$sql = "SELECT * FROM operadores ORDER BY id_operador DESC LIMIT $registros_por_pagina OFFSET $offset";
$datos2 = $dbtransportistas->obtenerRegistros($sql);
?>

<div class="main-wrapper modulo-operadores">
    <!-- Menú de Navegación -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>OPERADORES</h3>

        <?php include_once "../operadores/frm.php"; ?>

        <div id="contenedor3">
            <?php include_once "../operadores/tabla.php"; ?> 
        </div>
    </section>
</div>