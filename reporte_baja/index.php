<?php 
include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();

// 1. Llave primaria por si se edita un reporte
$id_reporte = (isset($_REQUEST['id_reporte']) ? $_REQUEST['id_reporte'] : '0');

// 2. Obtener empresas para el select del formulario
$sql_empresas = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC";
$empresas = $dbtransportistas->obtenerRegistros($sql_empresas);

// 3. Obtener operadores para el select del formulario
$sql_operadores = "SELECT id_operador, nombres, primer_apellido, segundo_apellido FROM operadores ORDER BY primer_apellido ASC";
$operadores = $dbtransportistas->obtenerRegistros($sql_operadores);

// --- LÓGICA DE PAGINACIÓN ---
$registros_por_pagina = 5; 
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Total de registros de la tabla reportes_baja
$sql_total = "SELECT COUNT(*) AS total FROM reportes_baja";
$res_total = $dbtransportistas->obtenerRegistros($sql_total);
$total_registros = isset($res_total[0]['total']) ? $res_total[0]['total'] : 0;
$total_paginas = ceil($total_registros / $registros_por_pagina);

// 4. Consulta paginada con JOINS e inclusión de calificacion_cuantitativa
$sql = "SELECT rb.id_reporte, 
               rb.id_operador, 
               rb.id_empresa, 
               rb.motivo_baja, 
               rb.calificacion_cuantitativa, 
               rb.calif_cualitativa, 
               rb.fecha_registro,
               CONCAT(o.nombres, ' ', o.primer_apellido, ' ', o.segundo_apellido) AS nombre_operador,
               e.nombre_empresa 
        FROM reportes_baja rb
        INNER JOIN operadores o ON rb.id_operador = o.id_operador
        INNER JOIN empresas e ON rb.id_empresa = e.id_empresa
        ORDER BY rb.id_reporte DESC 
        LIMIT $registros_por_pagina OFFSET $offset";
$datos2 = $dbtransportistas->obtenerRegistros($sql);
?>

<div class="main-wrapper modulo-reportes">
    <!-- Menú de Navegación / Controles Superiores Alineados a la Derecha -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>REPORTE BAJA</h3>

        <?php include_once "../reporte_baja/frm.php"; ?>
        
        <div id="contenedor3">
            <div id="contenedor3">
                <?php include_once "../reporte_baja/tabla.php"; ?> 
            </div>
        </div>
    </section>
</div>