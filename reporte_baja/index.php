<?php

/* =========================================================
   1. SESIÓN
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   2. CONEXIÓN
   ========================================================= */

include_once "../db/db.php";

$dbtransportistas = new db();
$dbtransportistas->conectar();


/* =========================================================
   3. DATOS DE SESIÓN
   ========================================================= */

$rol = strtoupper($_SESSION['rol'] ?? '');

$id_usuario =
    (int)($_SESSION['id_usuario'] ?? 0);

$id_empresa_sesion =
    !empty($_SESSION['id_empresa'])
    ? (int)$_SESSION['id_empresa']
    : 0;

$multiempresa =
    (int)($_SESSION['multiempresa'] ?? 0);


/* Llave primaria por si se edita un reporte */
$id_reporte =
    isset($_REQUEST['id_reporte'])
    ? (int)$_REQUEST['id_reporte']
    : 0;


/* =========================================================
   4. EMPRESAS SEGÚN ROL
   ========================================================= */

if (
    $rol === 'ADMIN' ||
    $rol === 'ADMINISTRADOR'
) {

    /* ADMIN: todas las empresas */
    $sql_empresas = "
        SELECT
            id_empresa,
            nombre_empresa
        FROM empresas
        ORDER BY nombre_empresa ASC
    ";

} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    /* PROPIETARIO MULTIEMPRESA */
    $sql_empresas = "
        SELECT
            e.id_empresa,
            e.nombre_empresa
        FROM empresas e

        INNER JOIN usuario_empresas ue
            ON ue.id_empresa = e.id_empresa

        WHERE ue.id_usuario = $id_usuario

        ORDER BY e.nombre_empresa ASC
    ";

} elseif (
    $rol === 'PROPIETARIO' ||
    $rol === 'RRHH'
) {

    /* PROPIETARIO DE UNA EMPRESA O RRHH */
    $sql_empresas = "
        SELECT
            id_empresa,
            nombre_empresa
        FROM empresas

        WHERE id_empresa = $id_empresa_sesion

        LIMIT 1
    ";

} else {

    /* Sin permiso */
    $sql_empresas = "
        SELECT
            id_empresa,
            nombre_empresa
        FROM empresas
        WHERE 1 = 0
    ";
}


$empresas =
    $dbtransportistas->obtenerRegistros(
        $sql_empresas
    );


/* =========================================================
   5. OPERADORES SEGÚN ROL
   ========================================================= */

if (
    $rol === 'ADMIN' ||
    $rol === 'ADMINISTRADOR'
) {

    /* ADMIN: todos los operadores activos */
    $sql_operadores = "
        SELECT
            id_operador,
            id_empresa,
            estatus,
            nombres,
            primer_apellido,
            segundo_apellido

        FROM operadores

        WHERE estatus = 1

        ORDER BY primer_apellido ASC
    ";

} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    /* PROPIETARIO MULTIEMPRESA:
       solo operadores de sus empresas */
    $sql_operadores = "
        SELECT
            o.id_operador,
            o.id_empresa,
            o.estatus,
            o.nombres,
            o.primer_apellido,
            o.segundo_apellido

        FROM operadores o

        INNER JOIN usuario_empresas ue
            ON ue.id_empresa = o.id_empresa

        WHERE ue.id_usuario = $id_usuario
        AND o.estatus = 1

        ORDER BY o.primer_apellido ASC
    ";

} elseif (
    $rol === 'PROPIETARIO' ||
    $rol === 'RRHH'
) {

    /* RRHH / PROPIETARIO DE UNA EMPRESA:
       solo operadores de su empresa */
    $sql_operadores = "
        SELECT
            id_operador,
            id_empresa,
            estatus,
            nombres,
            primer_apellido,
            segundo_apellido

        FROM operadores

        WHERE id_empresa = $id_empresa_sesion
        AND estatus = 1

        ORDER BY primer_apellido ASC
    ";

} else {

    /* Sin permiso */
    $sql_operadores = "
        SELECT
            id_operador,
            id_empresa,
            estatus,
            nombres,
            primer_apellido,
            segundo_apellido

        FROM operadores

        WHERE 1 = 0
    ";
}


$operadores =
    $dbtransportistas->obtenerRegistros(
        $sql_operadores
    );


/* =========================================================
   6. PAGINACIÓN
   ========================================================= */

$registros_por_pagina = 5;

$pagina_actual =
    isset($_GET['pagina'])
    ? (int)$_GET['pagina']
    : 1;

if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

$offset =
    ($pagina_actual - 1)
    * $registros_por_pagina;


/* =========================================================
   7. FILTRO DE REPORTES SEGÚN ROL
   ========================================================= */

$where = "";


/* RRHH: solo reportes de su empresa */
if ($rol === 'RRHH') {

    $where =
        "WHERE rb.id_empresa = $id_empresa_sesion";


/* PROPIETARIO MULTIEMPRESA:
   reportes de sus empresas */
} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $where =
        "WHERE rb.id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $id_usuario
        )";


/* PROPIETARIO DE UNA EMPRESA */
} elseif ($rol === 'PROPIETARIO') {

    $where =
        "WHERE rb.id_empresa = $id_empresa_sesion";
}


/* ADMIN no necesita filtro */


/* =========================================================
   8. TOTAL DE REPORTES
   ========================================================= */

$sql_total = "
    SELECT COUNT(*) AS total
    FROM reportes_baja rb
    $where
";


$res_total =
    $dbtransportistas->obtenerRegistros(
        $sql_total
    );


$total_registros =
    isset($res_total[0]['total'])
    ? (int)$res_total[0]['total']
    : 0;


$total_paginas =
    $total_registros > 0
    ? ceil(
        $total_registros /
        $registros_por_pagina
    )
    : 1;


/* =========================================================
   9. CONSULTA DE REPORTES
   ========================================================= */

$sql = "
    SELECT
        rb.id_reporte,
        rb.id_operador,
        rb.id_empresa,
        rb.motivo_baja,
        rb.calificacion_cuantitativa,
        rb.calif_cualitativa,
        rb.fecha_registro,
        rb.fecha_ingreso,
        rb.fecha_baja,
        rb.estatus_evaluacion,

        CONCAT(
            o.nombres,
            ' ',
            o.primer_apellido,
            ' ',
            o.segundo_apellido
        ) AS nombre_operador,

        e.nombre_empresa

    FROM reportes_baja rb

    INNER JOIN operadores o
        ON rb.id_operador = o.id_operador

    INNER JOIN empresas e
        ON rb.id_empresa = e.id_empresa

    $where

    ORDER BY rb.id_reporte DESC

    LIMIT $registros_por_pagina
    OFFSET $offset
";


$datos2 =
    $dbtransportistas->obtenerRegistros(
        $sql
    );

?>


<div class="main-wrapper modulo-reportes">

    <!-- NAVEGACIÓN -->
    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">

        <button
            type="button"
            class="btn-back"
            onclick="irAApartadoEmpresas()">

            ⬅️ Volver al Inicio

        </button>

    </nav>


    <section>

        <h3>REPORTE BAJA</h3>


        <!-- FORMULARIO -->
        <?php
        include_once "../reporte_baja/frm.php";
        ?>


        <!-- TABLA -->
        <div id="contenedor3">

            <?php
            include_once "../reporte_baja/tabla.php";
            ?>

        </div>

    </section>

</div>