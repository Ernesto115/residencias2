<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";
$db = new db();
$db->conectar();

/* =========================================================
   CONSULTAR OPERADOR PARA EDITAR
   ========================================================= */

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

header('Content-Type: application/json');

if ($id <= 0) {
    echo json_encode([]);
    exit;
}

/* ADMIN: cualquier operador */
$where = "id_operador = $id";

/* PROPIETARIO MULTIEMPRESA */
if ($rol === 'PROPIETARIO' && $multiempresa === 1) {
    $where .= " AND id_empresa IN (
        SELECT id_empresa
        FROM usuario_empresas
        WHERE id_usuario = $id_usuario
    )";

/* PROPIETARIO NORMAL Y RRHH */
} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
    $where .= " AND id_empresa = $id_empresa";

/* Rol no permitido */
} elseif ($rol !== 'ADMIN') {
    echo json_encode([]);
    exit;
}

$registros = $db->obtenerRegistros(
    "SELECT * FROM operadores WHERE $where LIMIT 1"
);

$db->desconectar();

echo !empty($registros)
    ? json_encode($registros[0])
    : json_encode([]);
?>