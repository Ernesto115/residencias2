<?php
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));
if ($rol === 'ADMINISTRADOR') $rol = 'ADMIN';

if ($rol !== 'ADMIN') {
    http_response_code(403);

    echo json_encode([
        'error'=>'Acceso no autorizado'
    ]);

    exit;
}


$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);

    echo json_encode([
        'error'=>'Usuario no válido'
    ]);

    exit;
}


include_once "../db/db.php";

$db = new db();
$db->conectar();


$stmt = $db->conn->prepare(
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
     WHERE id_usuario=:id
     LIMIT 1"
);

$stmt->execute([
    ':id'=>$id
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$db->desconectar();


if (!$usuario) {
    http_response_code(404);

    echo json_encode([
        'error'=>'Usuario no encontrado'
    ]);

    exit;
}


/*
   NO devolvemos la contraseña.
   Al editar quedará vacía y PHP conservará la actual.
*/
echo json_encode(
    $usuario,
    JSON_UNESCAPED_UNICODE
);
?>