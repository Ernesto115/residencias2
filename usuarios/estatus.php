<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

header(
    'Content-Type: application/json; charset=UTF-8'
);


/* =========================================================
   FUNCIÓN RESPUESTA
   ========================================================= */

function responderEstatus(
    $ok,
    $mensaje,
    $codigo = 200
) {

    http_response_code($codigo);

    echo json_encode(
        [
            'ok' => $ok,
            'mensaje' => $mensaje
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

    responderEstatus(
        false,
        'Método no permitido.',
        405
    );
}


/* =========================================================
   SESIÓN
   ========================================================= */

$rol =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}


$idUsuarioSesion =
    (int)(
        $_SESSION['id_usuario'] ?? 0
    );


/* =========================================================
   SOLO ADMIN
   ========================================================= */

if ($rol !== 'ADMIN') {

    responderEstatus(
        false,
        'No tienes permiso para cambiar el estatus de usuarios.',
        403
    );
}


/* =========================================================
   DATOS RECIBIDOS
   ========================================================= */

$idUsuario =
    (int)(
        $_POST['id_usuario'] ?? 0
    );


$nuevoEstatus =
    isset($_POST['estatus'])
        ? (int)$_POST['estatus']
        : -1;


/* =========================================================
   VALIDAR ID
   ========================================================= */

if ($idUsuario <= 0) {

    responderEstatus(
        false,
        'El usuario seleccionado no es válido.',
        400
    );
}


/* =========================================================
   VALIDAR ESTATUS
   ========================================================= */

if (
    !in_array(
        $nuevoEstatus,
        [0, 1],
        true
    )
) {

    responderEstatus(
        false,
        'El estatus solicitado no es válido.',
        400
    );
}


/* =========================================================
   NO MODIFICAR LA PROPIA CUENTA
   ========================================================= */

if ($idUsuario === $idUsuarioSesion) {

    responderEstatus(
        false,
        'No puedes desactivar o modificar el estatus de tu propia cuenta.',
        409
    );
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

require_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   BUSCAR USUARIO
   ========================================================= */

$stmt =
    $db->conn->prepare(
        "SELECT
            id_usuario,
            nombre_usuario,
            rol,
            estatus
         FROM usuarios
         WHERE id_usuario = :id
         LIMIT 1"
    );


$stmt->execute([
    ':id' => $idUsuario
]);


$usuario =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$usuario) {

    $db->desconectar();

    responderEstatus(
        false,
        'El usuario seleccionado no existe.',
        404
    );
}


/* =========================================================
   ROL DEL USUARIO
   ========================================================= */

$rolUsuario =
    strtoupper(
        trim(
            $usuario['rol'] ?? ''
        )
    );


if ($rolUsuario === 'ADMIN') {
    $rolUsuario = 'ADMINISTRADOR';
}


/* =========================================================
   NO CAMBIAR ADMINISTRADORES DESDE ESTE CONTROL
   ========================================================= */

/*
 * Por ahora esta opción está destinada exclusivamente a:
 *
 * - PROPIETARIO
 * - RRHH
 *
 * Las cuentas ADMIN quedan protegidas.
 */

if (
    !in_array(
        $rolUsuario,
        [
            'PROPIETARIO',
            'RRHH'
        ],
        true
    )
) {

    $db->desconectar();

    responderEstatus(
        false,
        'Las cuentas de administrador no pueden desactivarse desde esta opción.',
        409
    );
}


/* =========================================================
   ESTATUS ACTUAL
   ========================================================= */

$estatusActual =
    (int)(
        $usuario['estatus'] ?? 1
    );


/* =========================================================
   YA TIENE ESE ESTATUS
   ========================================================= */

if ($estatusActual === $nuevoEstatus) {

    $db->desconectar();

    responderEstatus(
        true,
        $nuevoEstatus === 1
            ? 'La cuenta ya se encuentra activa.'
            : 'La cuenta ya se encuentra desactivada.'
    );
}


/* =========================================================
   ACTUALIZAR ESTATUS
   ========================================================= */

try {

    $stmt =
        $db->conn->prepare(
            "UPDATE usuarios
             SET estatus = :estatus
             WHERE id_usuario = :id"
        );


    $stmt->execute([
        ':estatus' => $nuevoEstatus,
        ':id' => $idUsuario
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No fue posible actualizar el usuario.'
        );
    }


} catch (Throwable $e) {

    $db->desconectar();

    responderEstatus(
        false,
        'No fue posible cambiar el estatus del usuario.',
        500
    );
}


/* =========================================================
   FINALIZAR
   ========================================================= */

$db->desconectar();


if ($nuevoEstatus === 1) {

    responderEstatus(
        true,
        'Usuario reactivado correctamente.'
    );
}


responderEstatus(
    true,
    'Usuario desactivado correctamente.'
);

?>