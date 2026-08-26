<?php
/* =========================================================
   REPORTE DE BAJAS
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";
$dbtransportistas = new db();
$dbtransportistas->conectar();

/* SESIÓN */
$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);
$id_reporte = (int)($_REQUEST['id_reporte'] ?? 0);

/* =========================================================
   EMPRESAS SEGÚN ROL
   ========================================================= */

if ($rol === 'ADMIN' || $rol === 'ADMINISTRADOR') {
    $sql_empresas = "SELECT id_empresa,nombre_empresa
                     FROM empresas ORDER BY nombre_empresa ASC";

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $sql_empresas = "SELECT e.id_empresa,e.nombre_empresa
                     FROM empresas e
                     INNER JOIN usuario_empresas ue ON ue.id_empresa=e.id_empresa
                     WHERE ue.id_usuario=$id_usuario
                     ORDER BY e.nombre_empresa ASC";

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $sql_empresas = "SELECT id_empresa,nombre_empresa
                     FROM empresas
                     WHERE id_empresa=$id_empresa_sesion LIMIT 1";

} else {
    $sql_empresas = "SELECT id_empresa,nombre_empresa FROM empresas WHERE 1=0";
}

$empresas = $dbtransportistas->obtenerRegistros($sql_empresas);

/* =========================================================
   OPERADORES ACTIVOS SEGÚN ROL
   ========================================================= */

if ($rol === 'ADMIN' || $rol === 'ADMINISTRADOR') {
    $sql_operadores = "SELECT id_operador,id_empresa,estatus,nombres,
                       primer_apellido,segundo_apellido
                       FROM operadores
                       WHERE estatus=1
                       ORDER BY primer_apellido ASC";

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $sql_operadores = "SELECT o.id_operador,o.id_empresa,o.estatus,o.nombres,
                       o.primer_apellido,o.segundo_apellido
                       FROM operadores o
                       INNER JOIN usuario_empresas ue ON ue.id_empresa=o.id_empresa
                       WHERE ue.id_usuario=$id_usuario AND o.estatus=1
                       ORDER BY o.primer_apellido ASC";

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $sql_operadores = "SELECT id_operador,id_empresa,estatus,nombres,
                       primer_apellido,segundo_apellido
                       FROM operadores
                       WHERE id_empresa=$id_empresa_sesion AND estatus=1
                       ORDER BY primer_apellido ASC";

} else {
    $sql_operadores = "SELECT id_operador,id_empresa,estatus,nombres,
                       primer_apellido,segundo_apellido
                       FROM operadores WHERE 1=0";
}

$operadores = $dbtransportistas->obtenerRegistros($sql_operadores);

/* =========================================================
   FILTROS DE REPORTES
   ========================================================= */

$busqueda = trim($_GET['busqueda'] ?? '');
$estatus_filtro = strtolower(trim($_GET['estatus'] ?? 'todos'));
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$registros_por_pagina = 5;
$condiciones = [];

/* PERMISOS */
if ($rol === 'RRHH' || ($rol === 'PROPIETARIO' && $multiempresa === 0)) {
    $condiciones[] = "rb.id_empresa=$id_empresa_sesion";

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $condiciones[] = "rb.id_empresa IN (
        SELECT id_empresa FROM usuario_empresas WHERE id_usuario=$id_usuario
    )";

} elseif ($rol !== 'ADMIN' && $rol !== 'ADMINISTRADOR') {
    $condiciones[] = "1=0";
}

/* ESTATUS */
if ($estatus_filtro === 'pendientes') {
    $condiciones[] = "rb.estatus_evaluacion='PENDIENTE'";
} elseif ($estatus_filtro === 'completados') {
    $condiciones[] = "rb.estatus_evaluacion='COMPLETADA'";
}

/* BUSCADOR */
if ($busqueda !== '') {
    $b = addslashes($busqueda);

    $condiciones[] = "(
        o.rfc LIKE '%$b%' OR
        CONCAT(o.nombres,' ',o.primer_apellido,' ',o.segundo_apellido) LIKE '%$b%' OR
        e.nombre_empresa LIKE '%$b%' OR
        REPLACE(rb.motivo_baja,'_',' ') LIKE '%$b%' OR
        rb.calif_cualitativa LIKE '%$b%'
    )";
}

$where = $condiciones ? "WHERE " . implode(" AND ", $condiciones) : "";

/* =========================================================
   PAGINACIÓN
   ========================================================= */

$sql_total = "SELECT COUNT(*) AS total
              FROM reportes_baja rb
              INNER JOIN operadores o ON rb.id_operador=o.id_operador
              INNER JOIN empresas e ON rb.id_empresa=e.id_empresa
              $where";

$res_total = $dbtransportistas->obtenerRegistros($sql_total);
$total_registros = (int)($res_total[0]['total'] ?? 0);
$total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

$offset = ($pagina_actual - 1) * $registros_por_pagina;

/* =========================================================
   REPORTES
   ========================================================= */

$sql = "SELECT
            rb.id_reporte,rb.id_operador,rb.id_empresa,
            rb.motivo_baja,rb.calificacion_cuantitativa,rb.calif_cualitativa,
            rb.eval_distancia,rb.eval_tiempo,rb.eval_ganancias,rb.promedio_servicio,
            rb.eval_cuidado_vehiculo,rb.eval_productividad,
            rb.eval_rendimiento,rb.eval_cuidado_fisico,
            rb.fecha_registro,rb.fecha_ingreso,rb.fecha_baja,rb.estatus_evaluacion,
            CONCAT(o.nombres,' ',o.primer_apellido,' ',o.segundo_apellido) AS nombre_operador,
            e.nombre_empresa
        FROM reportes_baja rb
        INNER JOIN operadores o ON rb.id_operador=o.id_operador
        INNER JOIN empresas e ON rb.id_empresa=e.id_empresa
        $where
        ORDER BY rb.id_reporte DESC
        LIMIT $registros_por_pagina OFFSET $offset";

$datos2 = $dbtransportistas->obtenerRegistros($sql);
?>

<div class="main-wrapper modulo-reportes">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button" class="btn-back" onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>
        <h3>REPORTE BAJA</h3>

        <?php include_once "../reporte_baja/frm.php"; ?>

        <div id="contenedor3">
            <?php include_once "../reporte_baja/tabla.php"; ?>
        </div>
    </section>

</div>