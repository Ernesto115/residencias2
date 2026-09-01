<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   ELIMINAR OPERADOR SEGÚN PERMISOS
   ========================================================= */

$id = isset($_REQUEST['id'])
    ? (int)$_REQUEST['id']
    : 0;

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (in_array($rol, ['RH', 'RECURSOS HUMANOS'], true)) {
    $rol = 'RRHH';
}

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   ADMIN = SOLO CONSULTA
   ========================================================= */

if ($rol === 'ADMIN') {

    $db->desconectar();

    http_response_code(403);

    echo 'El administrador solo puede consultar la información de los operadores. No puede eliminarlos.';

    exit;
}


/* =========================================================
   VALIDAR ROL
   ========================================================= */

if (!in_array($rol, ['PROPIETARIO', 'RRHH'], true)) {

    $db->desconectar();

    http_response_code(403);

    echo 'No tienes permiso para eliminar operadores.';

    exit;
}


/* =========================================================
   VALIDAR OPERADOR
   ========================================================= */

if ($id <= 0) {

    $db->desconectar();

    http_response_code(400);

    echo 'El operador seleccionado no es válido.';

    exit;
}


/* =========================================================
   PROPIETARIO MULTIEMPRESA
   ========================================================= */

if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $sql = "
        DELETE FROM operadores
        WHERE id_operador = $id
        AND id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $id_usuario
        )
    ";


/* =========================================================
   PROPIETARIO NORMAL Y RRHH
   ========================================================= */

} else {

    $sql = "
        DELETE FROM operadores
        WHERE id_operador = $id
        AND id_empresa = $id_empresa
    ";
}


/* =========================================================
   ELIMINAR
   ========================================================= */

$db->eliminar($sql);


/* =========================================================
   RECARGAR TABLA
   ========================================================= */

include "../operadores/tabla.php";

$db->desconectar();

?>