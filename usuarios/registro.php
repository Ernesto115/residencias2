<?php

/* =========================================================
   SESIÓN CENTRALIZADA
   ========================================================= */

require_once __DIR__ . "/../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

header(
    'Content-Type: application/json; charset=utf-8'
);


/* =========================================================
   ROL DE LA SESIÓN
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


/* =========================================================
   SOLO ADMIN
   ========================================================= */

if ($rol !== 'ADMIN') {

    http_response_code(403);

    echo json_encode(
        [
            'error' =>
                'Acceso no autorizado'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   ID DEL USUARIO
   ========================================================= */

$id =
    (int)(
        $_GET['id'] ?? 0
    );


if ($id <= 0) {

    http_response_code(400);

    echo json_encode(
        [
            'error' =>
                'Usuario no válido'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

require_once __DIR__ . "/../DB/db.php";


$db = new db();

$db->conectar();


/* =========================================================
   CONSULTAR USUARIO
   ========================================================= */

$stmt =
    $db->conn->prepare(

        "SELECT
            id_usuario,
            nombre_usuario,
            nombres,
            primer_apellido,
            segundo_apellido,
            rol,
            correo_electronico,
            id_empresa,
            multiempresa

         FROM usuarios

         WHERE id_usuario = :id

         LIMIT 1"
    );


$stmt->execute([
    ':id' => $id
]);


$usuario =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


$db->desconectar();


/* =========================================================
   USUARIO NO ENCONTRADO
   ========================================================= */

if (!$usuario) {

    http_response_code(404);

    echo json_encode(
        [
            'error' =>
                'Usuario no encontrado'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   RESPUESTA
   ========================================================= */

/*
 * Nunca devolvemos la contraseña.
 *
 * Al editar, el campo de contraseña permanece vacío
 * y la contraseña almacenada actualmente se conserva.
 */

echo json_encode(
    $usuario,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

?>