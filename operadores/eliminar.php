<?php
session_start();

include_once "../db/db.php";
$db = new db();
$db->conectar();

/* =========================================================
   ELIMINAR OPERADOR SEGÚN PERMISOS
   ========================================================= */

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

if ($id > 0) {

    /* ADMIN: puede eliminar cualquier operador */
    if ($rol === 'ADMIN') {
        $sql = "DELETE FROM operadores WHERE id_operador = $id";

    /* PROPIETARIO MULTIEMPRESA: solo operadores de sus empresas */
    } elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {
        $sql = "DELETE FROM operadores
                WHERE id_operador = $id
                AND id_empresa IN (
                    SELECT id_empresa
                    FROM usuario_empresas
                    WHERE id_usuario = $id_usuario
                )";

    /* PROPIETARIO NORMAL Y RRHH: solo su empresa */
    } elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {
        $sql = "DELETE FROM operadores
                WHERE id_operador = $id
                AND id_empresa = $id_empresa";
    }

    if (isset($sql)) {
        $db->eliminar($sql);
    }
}

/* Recargar tabla */
include "../operadores/tabla.php";

$db->desconectar();
?>