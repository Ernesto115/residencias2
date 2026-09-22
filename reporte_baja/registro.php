<?php

/* =========================================================
   SESIÓN
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

header(
    'Content-Type: application/json; charset=utf-8'
);


include_once "../db/db.php";


$db = new db();
$db->conectar();


/* =========================================================
   ERROR CONTROLADO
   ========================================================= */

function errorRegistroReporte(
    $codigoHttp,
    $mensaje,
    $db
) {

    http_response_code(
        $codigoHttp
    );


    echo json_encode(
        [
            'ok' => false,
            'mensaje' => $mensaje
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    $db->desconectar();

    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {

    header(
        'Allow: POST'
    );


    errorRegistroReporte(
        405,
        'Método de solicitud no permitido.',
        $db
    );
}


/* =========================================================
   DATOS DE SESIÓN
   ========================================================= */

$rol =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if (
    $rol ===
    'ADMINISTRADOR'
) {

    $rol =
        'ADMIN';
}


if (
    in_array(
        $rol,
        [
            'RH',
            'RECURSOS HUMANOS'
        ],
        true
    )
) {

    $rol =
        'RRHH';
}


$id_usuario =
    (int)(
        $_SESSION['id_usuario'] ?? 0
    );


$id_empresa_sesion =
    (int)(
        $_SESSION['id_empresa'] ?? 0
    );


$multiempresa =
    (int)(
        $_SESSION['multiempresa'] ?? 0
    );


/* =========================================================
   SESIÓN VÁLIDA
   ========================================================= */

if (
    $id_usuario <= 0 ||
    $rol === ''
) {

    errorRegistroReporte(
        401,
        'Tu sesión no es válida o ha finalizado.',
        $db
    );
}


/* =========================================================
   ROLES QUE PUEDEN CONSULTAR REPORTES
   ========================================================= */

if (
    !in_array(
        $rol,
        [
            'ADMIN',
            'PROPIETARIO',
            'RRHH'
        ],
        true
    )
) {

    errorRegistroReporte(
        403,
        'No tienes permiso para consultar reportes de baja.',
        $db
    );
}


/* =========================================================
   ID DEL REPORTE
   ========================================================= */

$id_reporte = 0;


/*
 * La función editar() envía el ID mediante:
 *
 * registro.php?id=123
 *
 * También dejamos soporte para id_reporte.
 */

if (
    isset($_GET['id']) &&
    is_numeric($_GET['id'])
) {

    $id_reporte =
        (int)$_GET['id'];

} elseif (
    isset($_POST['id_reporte']) &&
    is_numeric($_POST['id_reporte'])
) {

    $id_reporte =
        (int)$_POST['id_reporte'];
}


/* =========================================================
   ID VÁLIDO
   ========================================================= */

if (
    $id_reporte <= 0
) {

    errorRegistroReporte(
        400,
        'El identificador del reporte no es válido.',
        $db
    );
}


/* =========================================================
   BUSCAR REPORTE
   ========================================================= */

$stmt =
    $db->conn->prepare(
        "SELECT *
         FROM reportes_baja
         WHERE id_reporte = :id
         LIMIT 1"
    );


$stmt->execute(
    [
        ':id' =>
            $id_reporte
    ]
);


$reporte =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   REPORTE EXISTENTE
   ========================================================= */

if (!$reporte) {

    errorRegistroReporte(
        404,
        'El reporte de baja no existe.',
        $db
    );
}


/* =========================================================
   EMPRESA REAL DEL REPORTE
   ========================================================= */

$id_empresa_reporte =
    (int)(
        $reporte['id_empresa'] ?? 0
    );


if (
    $id_empresa_reporte <= 0
) {

    errorRegistroReporte(
        404,
        'El reporte no tiene una empresa válida asociada.',
        $db
    );
}


/* =========================================================
   ADMIN
   CONSULTA GLOBAL
   ========================================================= */

/*
 * El administrador tiene permiso de consulta global,
 * pero los demás endpoints siguen impidiendo que registre
 * o finalice bajas.
 */

if ($rol === 'ADMIN') {

    echo json_encode(
        $reporte,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    $db->desconectar();

    exit;
}


/* =========================================================
   PROPIETARIO MULTIEMPRESA
   ========================================================= */

if (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $stmt =
        $db->conn->prepare(
            "SELECT 1
             FROM usuario_empresas
             WHERE id_usuario = :usuario
             AND id_empresa = :empresa
             LIMIT 1"
        );


    $stmt->execute(
        [
            ':usuario' =>
                $id_usuario,

            ':empresa' =>
                $id_empresa_reporte
        ]
    );


    if (
        !$stmt->fetchColumn()
    ) {

        errorRegistroReporte(
            403,
            'No tienes permiso para consultar reportes de esta empresa.',
            $db
        );
    }


    echo json_encode(
        $reporte,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    $db->desconectar();

    exit;
}


/* =========================================================
   PROPIETARIO NORMAL / RRHH
   ========================================================= */

if (
    $id_empresa_sesion <= 0 ||
    $id_empresa_reporte !==
    $id_empresa_sesion
) {

    errorRegistroReporte(
        403,
        'No tienes permiso para consultar reportes de esta empresa.',
        $db
    );
}


/* =========================================================
   RESPUESTA CORRECTA
   ========================================================= */

echo json_encode(
    $reporte,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);


$db->desconectar();

exit;

?>