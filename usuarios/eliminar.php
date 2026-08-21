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

$rolSesion = strtoupper($_SESSION['rol'] ?? '');
$idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);

$id_usuario = isset($_REQUEST['id'])
    ? (int)$_REQUEST['id']
    : 0;


/* =========================================================
   3. SOLO ADMIN PUEDE ELIMINAR USUARIOS
   ========================================================= */

if (
    $rolSesion !== 'ADMIN' &&
    $rolSesion !== 'ADMINISTRADOR'
) {

    http_response_code(403);

    exit(
        "No tienes permiso para eliminar usuarios."
    );
}


/* =========================================================
   4. VALIDAR ID
   ========================================================= */

if ($id_usuario <= 0) {

    http_response_code(400);

    exit(
        "El usuario seleccionado no es válido."
    );
}


/* =========================================================
   5. EVITAR QUE EL ADMIN SE ELIMINE A SÍ MISMO
   ========================================================= */

if ($id_usuario === $idUsuarioSesion) {

    http_response_code(409);

    exit(
        "No puedes eliminar tu propio usuario mientras tienes la sesión iniciada."
    );
}


/* =========================================================
   6. COMPROBAR QUE EL USUARIO EXISTE
   ========================================================= */

$usuario = $db->obtenerRegistros(
    "SELECT
        id_usuario,
        nombre_usuario,
        rol,
        id_empresa,
        multiempresa
     FROM usuarios
     WHERE id_usuario = $id_usuario
     LIMIT 1"
);


if (empty($usuario)) {

    http_response_code(404);

    exit(
        "El usuario que intentas eliminar no existe."
    );
}


$usuarioEliminar = $usuario[0];
$rolEliminar = strtoupper($usuarioEliminar['rol'] ?? '');


/* =========================================================
   7. ELIMINAR USUARIO
   ========================================================= */

/*
   IMPORTANTE:

   - NO eliminamos empresas.
   - NO eliminamos operadores.
   - NO eliminamos reportes.

   Si es PROPIETARIO y tiene registros en usuario_empresas,
   MySQL elimina únicamente esas relaciones gracias al
   ON DELETE CASCADE de usuario_empresas.
*/

$eliminado = $db->eliminar(
    "DELETE FROM usuarios
     WHERE id_usuario = $id_usuario"
);


if (!$eliminado) {

    http_response_code(500);

    exit(
        "No se pudo eliminar el usuario."
    );
}


/* =========================================================
   8. RECARGAR TABLA DE USUARIOS
   ========================================================= */

include_once "../usuarios/tabla.php";

$db->desconectar();

?>  