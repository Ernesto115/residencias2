<?php
require_once "../configuracion/sesion.php";
verificarSesion();

include_once "../db/db.php";

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rol === 'ADMINISTRADOR') $rol = 'ADMIN';
if (in_array($rol, ['RH','RECURSOS HUMANOS'], true)) $rol = 'RRHH';

if (!in_array($rol, ['ADMIN','PROPIETARIO','RRHH'], true)) {
    http_response_code(403);
    echo '<div class="alert alert-danger">No tienes permiso para acceder a Reporte de Baja.</div>';
    exit;
}

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$dbtransportistas = new db();
$dbtransportistas->conectar();


/* EMPRESAS */
if ($rol === 'ADMIN') {
    $sql_empresas = "SELECT id_empresa,nombre_empresa
                     FROM empresas ORDER BY nombre_empresa";

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $sql_empresas = "SELECT e.id_empresa,e.nombre_empresa
                     FROM empresas e
                     INNER JOIN usuario_empresas ue ON ue.id_empresa=e.id_empresa
                     WHERE ue.id_usuario=$id_usuario
                     ORDER BY e.nombre_empresa";

} else {
    $sql_empresas = "SELECT id_empresa,nombre_empresa
                     FROM empresas
                     WHERE id_empresa=$id_empresa_sesion LIMIT 1";
}

$empresas = $dbtransportistas->obtenerRegistros($sql_empresas);


/* OPERADORES ACTIVOS */
if ($rol === 'ADMIN') {
    $sql_operadores = "SELECT id_operador,id_empresa,estatus,nombres,
                       primer_apellido,segundo_apellido
                       FROM operadores
                       WHERE estatus=1
                       ORDER BY primer_apellido";

} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $sql_operadores = "SELECT o.id_operador,o.id_empresa,o.estatus,o.nombres,
                       o.primer_apellido,o.segundo_apellido
                       FROM operadores o
                       INNER JOIN usuario_empresas ue ON ue.id_empresa=o.id_empresa
                       WHERE ue.id_usuario=$id_usuario AND o.estatus=1
                       ORDER BY o.primer_apellido";

} else {
    $sql_operadores = "SELECT id_operador,id_empresa,estatus,nombres,
                       primer_apellido,segundo_apellido
                       FROM operadores
                       WHERE id_empresa=$id_empresa_sesion AND estatus=1
                       ORDER BY primer_apellido";
}

$operadores = $dbtransportistas->obtenerRegistros($sql_operadores);


/* FILTROS */
$busqueda = trim($_GET['busqueda'] ?? '');
$estatus_filtro = strtolower(trim($_GET['estatus'] ?? 'todos'));

if (!in_array($estatus_filtro, ['todos','pendientes','completados'], true)) {
    $estatus_filtro = 'todos';
}

$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$registros_por_pagina = 5;
$condiciones = [];


/* PERMISOS */
if ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $condiciones[] = "rb.id_empresa IN (
        SELECT id_empresa FROM usuario_empresas
        WHERE id_usuario=$id_usuario
    )";

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $condiciones[] = $id_empresa_sesion > 0
        ? "rb.id_empresa=$id_empresa_sesion"
        : "1=0";
}


/* ESTATUS */
if ($estatus_filtro === 'pendientes') {
    $condiciones[] = "rb.estatus_evaluacion='PENDIENTE'";
} elseif ($estatus_filtro === 'completados') {
    $condiciones[] = "rb.estatus_evaluacion='COMPLETADA'";
}


/* BÚSQUEDA */
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

$where = $condiciones ? "WHERE ".implode(" AND ", $condiciones) : "";


/* PAGINACIÓN */
$res_total = $dbtransportistas->obtenerRegistros(
    "SELECT COUNT(*) total
     FROM reportes_baja rb
     INNER JOIN operadores o ON o.id_operador=rb.id_operador
     INNER JOIN empresas e ON e.id_empresa=rb.id_empresa
     $where"
);

$total_registros = (int)($res_total[0]['total'] ?? 0);
$total_paginas = max(1, (int)ceil($total_registros / $registros_por_pagina));

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

$offset = ($pagina_actual - 1) * $registros_por_pagina;


/* REPORTES */
$datos2 = $dbtransportistas->obtenerRegistros(
    "SELECT rb.*,
        CONCAT(o.nombres,' ',o.primer_apellido,' ',o.segundo_apellido) nombre_operador,
        e.nombre_empresa
     FROM reportes_baja rb
     INNER JOIN operadores o ON o.id_operador=rb.id_operador
     INNER JOIN empresas e ON e.id_empresa=rb.id_empresa
     $where
     ORDER BY rb.id_reporte DESC
     LIMIT $registros_por_pagina OFFSET $offset"
);
?>

<div class="main-wrapper modulo-reportes">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">
        <button type="button"
                class="btn-back"
                onclick="irAApartadoEmpresas()">
            ⬅️ Volver al Inicio
        </button>
    </nav>

    <section>

        <h3>REPORTE BAJA</h3>

        <?php include "../reporte_baja/frm.php"; ?>

        <div id="contenedor3">
            <?php include "../reporte_baja/tabla.php"; ?>
        </div>

    </section>

</div>

<?php $dbtransportistas->desconectar(); ?>