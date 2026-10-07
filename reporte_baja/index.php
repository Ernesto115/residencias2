<?php
require_once "../configuracion/sesion.php";
verificarSesion();

include_once "../db/db.php";

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (in_array($rol, ['RH', 'RECURSOS HUMANOS'], true)) {
    $rol = 'RRHH';
}

if (!in_array($rol, ['ADMIN', 'PROPIETARIO', 'RRHH'], true)) {
    http_response_code(403);

    echo '<div class="alert alert-danger">
            No tienes permiso para acceder a Reporte de Baja.
          </div>';

    exit;
}


$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


$dbtransportistas = new db();
$dbtransportistas->conectar();


/* =========================================================
   FUNCIÓN LOCAL PARA CONSULTAS PREPARADAS
   ========================================================= */

$consultarPreparado = function (
    string $sql,
    array $parametros = []
) use ($dbtransportistas): array {

    try {

        if ($dbtransportistas->conn === null) {
            $dbtransportistas->conectar();
        }

        if ($dbtransportistas->conn === null) {
            return [];
        }

        $stmt = $dbtransportistas->conn->prepare($sql);

        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        /*
         * No mostramos información técnica al usuario.
         * Más adelante podremos mejorar el registro de errores.
         */
        return [];
    }
};


/* =========================================================
   EMPRESAS
   ========================================================= */

if ($rol === 'ADMIN') {

    $sql_empresas = "
        SELECT
            id_empresa,
            nombre_empresa
        FROM empresas
        ORDER BY nombre_empresa
    ";

    $empresas = $consultarPreparado(
        $sql_empresas
    );


} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $sql_empresas = "
        SELECT
            e.id_empresa,
            e.nombre_empresa
        FROM empresas e

        INNER JOIN usuario_empresas ue
            ON ue.id_empresa = e.id_empresa

        WHERE ue.id_usuario = :id_usuario

        ORDER BY e.nombre_empresa
    ";

    $empresas = $consultarPreparado(
        $sql_empresas,
        [
            'id_usuario' => $id_usuario
        ]
    );


} else {

    $sql_empresas = "
        SELECT
            id_empresa,
            nombre_empresa
        FROM empresas

        WHERE id_empresa = :id_empresa

        LIMIT 1
    ";

    $empresas = $consultarPreparado(
        $sql_empresas,
        [
            'id_empresa' => $id_empresa_sesion
        ]
    );
}


/* =========================================================
   OPERADORES ACTIVOS
   ========================================================= */

if ($rol === 'ADMIN') {

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

        ORDER BY primer_apellido
    ";

    $operadores = $consultarPreparado(
        $sql_operadores
    );


} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

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

        WHERE
            ue.id_usuario = :id_usuario
            AND o.estatus = 1

        ORDER BY o.primer_apellido
    ";

    $operadores = $consultarPreparado(
        $sql_operadores,
        [
            'id_usuario' => $id_usuario
        ]
    );


} else {

    $sql_operadores = "
        SELECT
            id_operador,
            id_empresa,
            estatus,
            nombres,
            primer_apellido,
            segundo_apellido
        FROM operadores

        WHERE
            id_empresa = :id_empresa
            AND estatus = 1

        ORDER BY primer_apellido
    ";

    $operadores = $consultarPreparado(
        $sql_operadores,
        [
            'id_empresa' => $id_empresa_sesion
        ]
    );
}


/* =========================================================
   FILTROS
   ========================================================= */

$busqueda = trim(
    $_GET['busqueda'] ?? ''
);

$estatus_filtro = strtolower(
    trim(
        $_GET['estatus'] ?? 'todos'
    )
);


if (
    !in_array(
        $estatus_filtro,
        [
            'todos',
            'pendientes',
            'completados'
        ],
        true
    )
) {

    $estatus_filtro = 'todos';
}


$pagina_actual = max(
    1,
    (int)($_GET['pagina'] ?? 1)
);

$registros_por_pagina = 5;

$condiciones = [];

$parametrosWhere = [];


/* =========================================================
   PERMISOS
   ========================================================= */

if (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $condiciones[] = "
        rb.id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = :id_usuario_permiso
        )
    ";

    $parametrosWhere['id_usuario_permiso'] =
        $id_usuario;


} elseif (
    $rol === 'PROPIETARIO' ||
    $rol === 'RRHH'
) {

    if ($id_empresa_sesion > 0) {

        $condiciones[] = "
            rb.id_empresa = :id_empresa_permiso
        ";

        $parametrosWhere['id_empresa_permiso'] =
            $id_empresa_sesion;

    } else {

        /*
         * Si el usuario no tiene empresa válida,
         * no permitimos mostrar reportes.
         */
        $condiciones[] = "1 = 0";
    }
}


/* =========================================================
   ESTATUS
   ========================================================= */

if ($estatus_filtro === 'pendientes') {

    $condiciones[] = "
        rb.estatus_evaluacion = 'PENDIENTE'
    ";


} elseif ($estatus_filtro === 'completados') {

    $condiciones[] = "
        rb.estatus_evaluacion = 'COMPLETADA'
    ";
}


/* =========================================================
   BÚSQUEDA
   ========================================================= */

if ($busqueda !== '') {

    $condiciones[] = "
        (
            o.rfc LIKE :busqueda_rfc

            OR CONCAT(
                o.nombres,
                ' ',
                o.primer_apellido,
                ' ',
                o.segundo_apellido
            ) LIKE :busqueda_nombre

            OR e.nombre_empresa LIKE :busqueda_empresa

            OR REPLACE(
                rb.motivo_baja,
                '_',
                ' '
            ) LIKE :busqueda_motivo

            OR rb.calif_cualitativa LIKE :busqueda_calif
        )
    ";


    $valorBusqueda =
        '%' . $busqueda . '%';


    $parametrosWhere['busqueda_rfc'] =
        $valorBusqueda;

    $parametrosWhere['busqueda_nombre'] =
        $valorBusqueda;

    $parametrosWhere['busqueda_empresa'] =
        $valorBusqueda;

    $parametrosWhere['busqueda_motivo'] =
        $valorBusqueda;

    $parametrosWhere['busqueda_calif'] =
        $valorBusqueda;
}


/* =========================================================
   CONSTRUIR WHERE
   ========================================================= */

$where = $condiciones
    ? "WHERE " . implode(
        " AND ",
        $condiciones
    )
    : "";


/* =========================================================
   PAGINACIÓN
   ========================================================= */

$sql_total = "
    SELECT
        COUNT(*) AS total

    FROM reportes_baja rb

    INNER JOIN operadores o
        ON o.id_operador = rb.id_operador

    INNER JOIN empresas e
        ON e.id_empresa = rb.id_empresa

    $where
";


$res_total = $consultarPreparado(
    $sql_total,
    $parametrosWhere
);


$total_registros = (int)(
    $res_total[0]['total'] ?? 0
);


$total_paginas = max(
    1,
    (int)ceil(
        $total_registros /
        $registros_por_pagina
    )
);


if ($pagina_actual > $total_paginas) {

    $pagina_actual =
        $total_paginas;
}


$offset =
    ($pagina_actual - 1) *
    $registros_por_pagina;


/* =========================================================
   REPORTES
   ========================================================= */

$sql_reportes = "
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
        ON o.id_operador = rb.id_operador

    INNER JOIN empresas e
        ON e.id_empresa = rb.id_empresa

    $where

    ORDER BY rb.id_reporte DESC

    LIMIT $registros_por_pagina
    OFFSET $offset
";


$datos2 = $consultarPreparado(
    $sql_reportes,
    $parametrosWhere
);

?>

<div class="main-wrapper modulo-reportes">

    <nav class="d-flex justify-content-end align-items-center gap-2 p-3">

        <button
            type="button"
            class="btn-back"
            onclick="irAApartadoEmpresas()"
        >
            ⬅️ Volver al Inicio
        </button>

    </nav>


    <section>

        <h3>
            REPORTE BAJA
        </h3>


        <?php
        include "../reporte_baja/frm.php";
        ?>


        <div id="contenedor3">

            <?php
            include "../reporte_baja/tabla.php";
            ?>

        </div>

    </section>

</div>

<?php
$dbtransportistas->desconectar();
?>