<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";
$db = new db();
$db->conectar();

/* =========================================================
   1. DATOS
   ========================================================= */

$id_empresa = (int)($_REQUEST['id_empresa'] ?? 0);

$nombre_empresa = addslashes(trim($_REQUEST['nombre_empresa'] ?? ''));
$razon_social = addslashes(trim($_REQUEST['razon_social'] ?? ''));
$direccion_fiscal = addslashes(trim($_REQUEST['direccion_fiscal'] ?? ''));
$responsable = addslashes(trim($_REQUEST['responsable'] ?? ''));

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   2. ACTUALIZAR
   ========================================================= */

if ($id_empresa > 0) {

    $permitido = false;

    if ($rol === 'ADMIN') {
        $permitido = true;

    } elseif ($rol === 'PROPIETARIO') {

        if ($multiempresa === 1) {

            $permiso = $db->obtenerRegistros(
                "SELECT id_empresa FROM usuario_empresas
                 WHERE id_usuario = $id_usuario
                 AND id_empresa = $id_empresa LIMIT 1"
            );

            $permitido = !empty($permiso);

        } else {
            $permitido = ($id_empresa === $id_empresa_sesion);
        }
    }

    if ($permitido) {

        $sql = "UPDATE empresas SET
                nombre_empresa='$nombre_empresa',
                razon_social='$razon_social',
                direccion_fiscal='$direccion_fiscal',
                responsable='$responsable'
                WHERE id_empresa=$id_empresa";

        $db->actualizar($sql);
    }


/* =========================================================
   3. INSERTAR
   ========================================================= */

} else {

    if ($rol === 'ADMIN' || $rol === 'PROPIETARIO') {

        $sql = "INSERT INTO empresas
                (nombre_empresa, razon_social, direccion_fiscal, responsable)
                VALUES
                ('$nombre_empresa','$razon_social','$direccion_fiscal','$responsable')";

        $db->insertar($sql);

        /* ID REAL DE LA EMPRESA RECIÉN CREADA */
        $nueva_empresa = (int)$db->conn->lastInsertId();


        /* PROPIETARIO: relacionar sus empresas */
        if ($rol === 'PROPIETARIO' && $nueva_empresa > 0) {

            /* Empresa original */
            if ($id_empresa_sesion > 0) {
                $db->insertar(
                    "INSERT IGNORE INTO usuario_empresas
                     (id_usuario, id_empresa)
                     VALUES ($id_usuario, $id_empresa_sesion)"
                );
            }

            /* Empresa nueva */
            $db->insertar(
                "INSERT IGNORE INTO usuario_empresas
                 (id_usuario, id_empresa)
                 VALUES ($id_usuario, $nueva_empresa)"
            );

            /* Convertirlo en multiempresa */
            $db->actualizar(
                "UPDATE usuarios
                 SET multiempresa = 1
                 WHERE id_usuario = $id_usuario"
            );

            $_SESSION['multiempresa'] = 1;
            $multiempresa = 1;
        }
    }
}


/* =========================================================
   4. RECARGAR TABLA
   ========================================================= */

/* ADMIN: todas */
if ($rol === 'ADMIN') {

    $sql = "SELECT *
            FROM empresas
            ORDER BY id_empresa DESC";

/* PROPIETARIO MULTIEMPRESA: solo las suyas */
} elseif ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $sql = "SELECT e.*
            FROM empresas e
            INNER JOIN usuario_empresas ue
                ON e.id_empresa = ue.id_empresa
            WHERE ue.id_usuario = $id_usuario
            ORDER BY e.id_empresa DESC";

/* PROPIETARIO NORMAL */
} elseif ($rol === 'PROPIETARIO') {

    $sql = "SELECT *
            FROM empresas
            WHERE id_empresa = $id_empresa_sesion";

} else {

    $sql = "SELECT * FROM empresas WHERE 1 = 0";
}

$datos2 = $db->obtenerRegistros($sql);

include "../empresas/tabla.php";

$db->desconectar();
?>