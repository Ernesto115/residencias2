<?php

/* =========================================================
   SESIÓN CENTRAL
   ========================================================= */

require_once "../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   ROL
   ========================================================= */

$rol =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if (
    $rol === 'ADMINISTRADOR'
) {

    $rol = 'ADMIN';
}


/* =========================================================
   SOLO ADMIN Y PROPIETARIO
   ========================================================= */

if (
    !in_array(
        $rol,
        [
            'ADMIN',
            'PROPIETARIO'
        ],
        true
    )
) {

    http_response_code(
        403
    );


    exit(
        '<div class="alert alert-danger">
            No tienes permiso para consultar empresas.
        </div>'
    );
}


/* =========================================================
   BASE DE DATOS
   ========================================================= */

require_once "../DB/db.php";


$dbtransportistas =
    new db();


$dbtransportistas->conectar();


/* =========================================================
   DATOS DE SESIÓN
   ========================================================= */

$id_usuario =
    (int)(
        $_SESSION['id_usuario'] ?? 0
    );


$id_empresa =
    (int)(
        $_SESSION['id_empresa'] ?? 0
    );


$multiempresa =
    (int)(
        $_SESSION['multiempresa'] ?? 0
    );


/* =========================================================
   PARÁMETROS DE TABLA
   ========================================================= */

$pagina_actual =
    max(
        1,
        (int)(
            $_GET['pagina'] ?? 1
        )
    );


$registros_por_pagina =
    5;


$busqueda =
    trim(
        (string)(
            $_GET['busqueda'] ?? ''
        )
    );


if (
    mb_strlen(
        $busqueda,
        'UTF-8'
    ) > 120
) {

    $busqueda =
        mb_substr(
            $busqueda,
            0,
            120,
            'UTF-8'
        );
}


$solo_tabla =
    isset(
        $_GET['solo_tabla']
    ) &&
    $_GET['solo_tabla'] === '1';


/* =========================================================
   CONSTRUIR CONSULTA SEGÚN ROL
   ========================================================= */

$joins =
    '';


$condiciones =
    [];


$parametros =
    [];


/* =========================================================
   ADMIN
   ========================================================= */

if (
    $rol === 'ADMIN'
) {

    /*
     * Consulta global.
     * No necesita condición de empresa.
     */


/* =========================================================
   PROPIETARIO MULTIEMPRESA
   ========================================================= */

} elseif (
    $multiempresa === 1
) {

    $joins = "

        INNER JOIN usuario_empresas ue

            ON ue.id_empresa =
               e.id_empresa

    ";


    $condiciones[] =

        "ue.id_usuario =
         :id_usuario";


    $parametros[
        ':id_usuario'
    ] =
        $id_usuario;


/* =========================================================
   PROPIETARIO NORMAL
   ========================================================= */

} elseif (
    $id_empresa > 0
) {

    $condiciones[] =

        "e.id_empresa =
         :id_empresa";


    $parametros[
        ':id_empresa'
    ] =
        $id_empresa;


/* =========================================================
   SIN EMPRESA
   ========================================================= */

} else {

    $condiciones[] =
        '1 = 0';
}


/* =========================================================
   BÚSQUEDA
   ========================================================= */

if (
    $busqueda !== ''
) {

    $condiciones[] = "

        (

            e.nombre_empresa
                LIKE :busqueda_nombre

            OR

            e.razon_social
                LIKE :busqueda_razon

            OR

            e.direccion_fiscal
                LIKE :busqueda_direccion

            OR

            e.responsable
                LIKE :busqueda_responsable

        )

    ";


    $terminoBusqueda =
        '%' .
        $busqueda .
        '%';


    $parametros[
        ':busqueda_nombre'
    ] =
        $terminoBusqueda;


    $parametros[
        ':busqueda_razon'
    ] =
        $terminoBusqueda;


    $parametros[
        ':busqueda_direccion'
    ] =
        $terminoBusqueda;


    $parametros[
        ':busqueda_responsable'
    ] =
        $terminoBusqueda;
}


/* =========================================================
   WHERE
   ========================================================= */

$where =
    !empty(
        $condiciones
    )

        ? 'WHERE ' .
          implode(
              ' AND ',
              $condiciones
          )

        : '';


/* =========================================================
   TOTAL DE REGISTROS
   ========================================================= */

$stmt =
    $dbtransportistas
        ->conn
        ->prepare(
            "SELECT
                COUNT(
                    DISTINCT e.id_empresa
                )

             FROM empresas e

             $joins

             $where"
        );


foreach (
    $parametros
    as $clave => $valor
) {

    $stmt->bindValue(

        $clave,

        $valor,

        is_int($valor)
            ? PDO::PARAM_INT
            : PDO::PARAM_STR
    );
}


$stmt->execute();


$total_registros =
    (int)$stmt
        ->fetchColumn();


/* =========================================================
   TOTAL DE PÁGINAS
   ========================================================= */

$total_paginas =
    max(
        1,
        (int)ceil(
            $total_registros /
            $registros_por_pagina
        )
    );


if (
    $pagina_actual >
    $total_paginas
) {

    $pagina_actual =
        $total_paginas;
}


/* =========================================================
   OFFSET
   ========================================================= */

$offset =
    (
        $pagina_actual - 1
    ) *
    $registros_por_pagina;


/* =========================================================
   CONSULTAR EMPRESAS
   ========================================================= */

$stmt =
    $dbtransportistas
        ->conn
        ->prepare(
            "SELECT DISTINCT

                e.*

             FROM empresas e

             $joins

             $where

             ORDER BY
                e.id_empresa DESC

             LIMIT :limite

             OFFSET :offset"
        );


foreach (
    $parametros
    as $clave => $valor
) {

    $stmt->bindValue(

        $clave,

        $valor,

        is_int($valor)
            ? PDO::PARAM_INT
            : PDO::PARAM_STR
    );
}


$stmt->bindValue(

    ':limite',

    $registros_por_pagina,

    PDO::PARAM_INT
);


$stmt->bindValue(

    ':offset',

    $offset,

    PDO::PARAM_INT
);


$stmt->execute();


$datos2 =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   SOLO TABLA
   AJAX
   ========================================================= */

if (
    $solo_tabla
) {

    include
        "../empresas/tabla.php";


    $dbtransportistas
        ->desconectar();


    exit;
}

?>


<div class="main-wrapper modulo-empresas">


    <nav
        class="
            d-flex
            justify-content-end
            align-items-center
            gap-2
            p-3
        "
    >

        <button
            type="button"
            class="btn-back"
            onclick="
                window.location.href='../index.php'
            "
        >

            ⬅️ Volver al Inicio

        </button>

    </nav>


    <section>


        <h3>
            EMPRESAS
        </h3>


        <?php

        include
            "../empresas/frm.php";

        ?>


        <div id="contenedor3">

            <?php

            include
                "../empresas/tabla.php";

            ?>

        </div>


    </section>


</div>


<?php

$dbtransportistas
    ->desconectar();

?>