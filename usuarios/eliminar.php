<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";

$db = new db();
$db->conectar();


$rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rol === 'ADMINISTRADOR') $rol = 'ADMIN';

$idSesion = (int)($_SESSION['id_usuario'] ?? 0);
$idUsuario = (int)($_GET['id'] ?? 0);


/* SOLO ADMIN */
if ($rol !== 'ADMIN') {

    http_response_code(403);
    exit('No tienes permiso para eliminar usuarios.');
}


if ($idUsuario <= 0) {

    http_response_code(400);
    exit('El usuario seleccionado no es válido.');
}


/* NO ELIMINARSE A SÍ MISMO */
if ($idUsuario === $idSesion) {

    http_response_code(409);

    exit(
        'No puedes eliminar tu propio usuario mientras tienes la sesión iniciada.'
    );
}


/* EXISTENCIA */
$stmt = $db->conn->prepare(
    "SELECT id_usuario,nombre_usuario
     FROM usuarios
     WHERE id_usuario=:id
     LIMIT 1"
);

$stmt->execute([
    ':id'=>$idUsuario
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

    http_response_code(404);
    exit('El usuario que intentas eliminar no existe.');
}


/* ELIMINAR */
try {

    $db->conn->beginTransaction();


    /* Relaciones multiempresa */
    $stmt = $db->conn->prepare(
        "DELETE FROM usuario_empresas
         WHERE id_usuario=:id"
    );

    $stmt->execute([
        ':id'=>$idUsuario
    ]);


    /* Usuario */
    $stmt = $db->conn->prepare(
        "DELETE FROM usuarios
         WHERE id_usuario=:id"
    );

    $stmt->execute([
        ':id'=>$idUsuario
    ]);


    if ($stmt->rowCount() !== 1) {
        throw new Exception(
            'No fue posible eliminar el usuario.'
        );
    }


    $db->conn->commit();

} catch (Throwable $e) {

    if ($db->conn->inTransaction()) {
        $db->conn->rollBack();
    }

    http_response_code(500);

    exit(
        'No se pudo eliminar el usuario debido a una relación existente.'
    );
}


/* RECARGAR */
include "../usuarios/tabla.php";

$db->desconectar();
?>