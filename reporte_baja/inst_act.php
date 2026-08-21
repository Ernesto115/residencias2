<?php

/* =========================================================
   1. SESIÓN Y CONEXIÓN
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   2. DATOS DE SESIÓN
   ========================================================= */

$rol = strtoupper($_SESSION['rol'] ?? '');

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

$id_empresa_sesion =
    !empty($_SESSION['id_empresa'])
    ? (int)$_SESSION['id_empresa']
    : 0;

$multiempresa =
    (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   3. DATOS DEL FORMULARIO
   ========================================================= */

$id_reporte =
    isset($_REQUEST['id_reporte'])
    ? (int)$_REQUEST['id_reporte']
    : 0;

$id_operador =
    isset($_REQUEST['id_operador'])
    ? (int)$_REQUEST['id_operador']
    : 0;

$motivo_baja =
    addslashes(
        trim($_REQUEST['motivo_baja'] ?? '')
    );

$calif_cualitativa =
    addslashes(
        trim($_REQUEST['calif_cualitativa'] ?? '')
    );


/* =========================================================
   4. FUNCIÓN DE ERROR
   ========================================================= */

function errorReporte($mensaje, $db)
{
    echo "<!-- Error MySQL -->

    <script>

        if (typeof Swal !== 'undefined') {

            Swal.fire({
                icon: 'warning',
                title: 'No se pudo registrar la baja',
                text: '" . addslashes($mensaje) . "',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#e11d48',
                background: '#1e293b',
                color: '#ffffff'
            });

        } else if (typeof mostrarToast === 'function') {

            mostrarToast(
                '⚠️ " . addslashes($mensaje) . "'
            );

        } else {

            alert(
                '⚠️ " . addslashes($mensaje) . "'
            );
        }

    </script>";

    $db->desconectar();

    exit;
}


/* =========================================================
   5. VALIDACIONES BÁSICAS
   ========================================================= */

if ($id_operador <= 0) {

    errorReporte(
        'Debes seleccionar un operador.',
        $db
    );
}


if ($motivo_baja === '') {

    errorReporte(
        'Debes seleccionar el motivo de baja.',
        $db
    );
}


/* =========================================================
   6. OBTENER OPERADOR
   ========================================================= */

$operador = $db->obtenerRegistros(
    "SELECT
        id_operador,
        id_empresa,
        estatus,
        fecha_ingreso,
        nombres,
        primer_apellido,
        segundo_apellido
     FROM operadores
     WHERE id_operador = $id_operador
     LIMIT 1"
);


if (empty($operador)) {

    errorReporte(
        'El operador seleccionado no existe.',
        $db
    );
}


$operador =
    $operador[0];


$id_empresa_operador =
    (int)$operador['id_empresa'];


$estatus_operador =
    (int)$operador['estatus'];


/* =========================================================
   7. EL OPERADOR DEBE ESTAR ACTIVO
   ========================================================= */

if ($estatus_operador !== 1) {

    errorReporte(
        'Este operador ya se encuentra inactivo.',
        $db
    );
}


/* =========================================================
   8. VALIDAR PERMISO SEGÚN ROL
   ========================================================= */

$permitido = false;


/* ADMINISTRADOR */
if (
    $rol === 'ADMIN' ||
    $rol === 'ADMINISTRADOR'
) {

    $permitido = true;


/* RRHH: SOLO SU EMPRESA */
} elseif ($rol === 'RRHH') {

    $permitido =
        ($id_empresa_operador === $id_empresa_sesion);


/* PROPIETARIO MULTIEMPRESA */
} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $permiso = $db->obtenerRegistros(
        "SELECT id_empresa
         FROM usuario_empresas
         WHERE id_usuario = $id_usuario
         AND id_empresa = $id_empresa_operador
         LIMIT 1"
    );

    $permitido =
        !empty($permiso);


/* PROPIETARIO DE UNA EMPRESA */
} elseif ($rol === 'PROPIETARIO') {

    $permitido =
        ($id_empresa_operador === $id_empresa_sesion);
}


if (!$permitido) {

    errorReporte(
        'No tienes permiso para reportar la baja de este operador.',
        $db
    );
}


/* =========================================================
   9. EVITAR DOS BAJAS PENDIENTES
   ========================================================= */

$pendiente = $db->obtenerRegistros(
    "SELECT id_reporte
     FROM reportes_baja
     WHERE id_operador = $id_operador
     AND estatus_evaluacion = 'PENDIENTE'
     LIMIT 1"
);


if (
    !empty($pendiente) &&
    $id_reporte <= 0
) {

    errorReporte(
        'Este operador ya tiene una solicitud de baja pendiente.',
        $db
    );
}


/* =========================================================
   10. COMENTARIO DEL MOTIVO
   ========================================================= */

if ($motivo_baja !== 'OTRO') {

    $calif_cualitativa = '';
}


/* =========================================================
   11. INSERTAR SOLICITUD DE BAJA
   ========================================================= */

if ($id_reporte <= 0) {

    $fecha_ingreso =
        !empty($operador['fecha_ingreso'])
        ? "'" . addslashes($operador['fecha_ingreso']) . "'"
        : "NULL";


    $sql = "
        INSERT INTO reportes_baja (
            id_operador,
            id_empresa,
            motivo_baja,
            calificacion_cuantitativa,
            calif_cualitativa,
            fecha_ingreso,
            fecha_baja,
            estatus_evaluacion
        )
        VALUES (
            $id_operador,
            $id_empresa_operador,
            '$motivo_baja',
            NULL,
            '$calif_cualitativa',
            $fecha_ingreso,
            NULL,
            'PENDIENTE'
        )
    ";


    $guardado =
        $db->insertar($sql);


    if (!$guardado) {

        errorReporte(
            'No se pudo registrar la solicitud de baja.',
            $db
        );
    }


    /*
       IMPORTANTE:

       RRHH SOLAMENTE SOLICITA LA BAJA.

       NO HACEMOS:

       UPDATE operadores
       SET estatus = 0

       El operador continúa ACTIVO hasta que
       el PROPIETARIO confirme la baja.
    */
}


/* =========================================================
   12. PAGINACIÓN
   ========================================================= */

$registros_por_pagina = 5;


$pagina_actual =
    isset($_REQUEST['pagina'])
    ? (int)$_REQUEST['pagina']
    : 1;


if ($pagina_actual < 1) {

    $pagina_actual = 1;
}


$offset =
    ($pagina_actual - 1)
    * $registros_por_pagina;


/* =========================================================
   13. FILTRO DE REPORTES SEGÚN ROL
   ========================================================= */

$where = "";


/* RRHH */
if ($rol === 'RRHH') {

    $where =
        "WHERE rb.id_empresa = $id_empresa_sesion";


/* PROPIETARIO MULTIEMPRESA */
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


/* PROPIETARIO UNA EMPRESA */
} elseif ($rol === 'PROPIETARIO') {

    $where =
        "WHERE rb.id_empresa = $id_empresa_sesion";
}


/* =========================================================
   14. TOTAL DE REPORTES
   ========================================================= */

$sql_total = "
    SELECT COUNT(*) AS total
    FROM reportes_baja rb
    $where
";


$res_total =
    $db->obtenerRegistros($sql_total);


$total_registros =
    (int)($res_total[0]['total'] ?? 0);


$total_paginas =
    $total_registros > 0
    ? ceil(
        $total_registros /
        $registros_por_pagina
    )
    : 1;


/* =========================================================
   15. CONSULTAR REPORTES
   ========================================================= */

$sql = "
    SELECT
        rb.*,

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
    $db->obtenerRegistros($sql);


/* =========================================================
   16. DEVOLVER TABLA
   ========================================================= */

include_once "../reporte_baja/tabla.php";


$db->desconectar();

?>