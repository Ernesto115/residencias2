<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


header(
    'Content-Type: application/json; charset=utf-8'
);


$rol =
    strtoupper(
        trim($_SESSION['rol'] ?? '')
    );


if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}


/* =========================================================
   PERMISOS
   ========================================================= */

if (
    !in_array(
        $rol,
        ['ADMIN','PROPIETARIO'],
        true
    )
) {

    http_response_code(403);

    echo json_encode([
        'error' =>
            'No tienes permiso para consultar esta empresa.'
    ]);

    exit;
}


/* =========================================================
   DATOS
   ========================================================= */

$id_empresa =
    (int)($_GET['id'] ?? 0);

$id_usuario =
    (int)($_SESSION['id_usuario'] ?? 0);

$id_empresa_sesion =
    (int)($_SESSION['id_empresa'] ?? 0);

$multiempresa =
    (int)($_SESSION['multiempresa'] ?? 0);


if ($id_empresa <= 0) {

    http_response_code(400);

    echo json_encode([
        'error' =>
            'Empresa no válida.'
    ]);

    exit;
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   ADMIN
   ========================================================= */

if ($rol === 'ADMIN') {

    $stmt =
        $db->conn->prepare(
            "SELECT
                id_empresa,
                nombre_empresa,
                razon_social,
                direccion_fiscal,
                responsable

             FROM empresas

             WHERE id_empresa = :empresa

             LIMIT 1"
        );


    $stmt->execute([
        ':empresa' => $id_empresa
    ]);


/* =========================================================
   PROPIETARIO MULTIEMPRESA
   ========================================================= */

} elseif ($multiempresa === 1) {

    $stmt =
        $db->conn->prepare(
            "SELECT
                e.id_empresa,
                e.nombre_empresa,
                e.razon_social,
                e.direccion_fiscal,
                e.responsable

             FROM empresas e

             INNER JOIN usuario_empresas ue
                ON ue.id_empresa = e.id_empresa

             WHERE e.id_empresa = :empresa
             AND ue.id_usuario = :usuario

             LIMIT 1"
        );


    $stmt->execute([
        ':empresa' => $id_empresa,
        ':usuario' => $id_usuario
    ]);


/* =========================================================
   PROPIETARIO INDIVIDUAL
   ========================================================= */

} else {

    $stmt =
        $db->conn->prepare(
            "SELECT
                id_empresa,
                nombre_empresa,
                razon_social,
                direccion_fiscal,
                responsable

             FROM empresas

             WHERE id_empresa = :empresa
             AND id_empresa = :empresa_sesion

             LIMIT 1"
        );


    $stmt->execute([
        ':empresa' => $id_empresa,
        ':empresa_sesion' => $id_empresa_sesion
    ]);
}


$empresa =
    $stmt->fetch(PDO::FETCH_ASSOC);


$db->desconectar();


if (!$empresa) {

    http_response_code(404);

    echo json_encode([
        'error' =>
            'Empresa no encontrada o fuera de tu alcance.'
    ]);

    exit;
}


echo json_encode(
    $empresa,
    JSON_UNESCAPED_UNICODE
);
?>