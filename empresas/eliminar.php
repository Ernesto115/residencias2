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
   2. DATOS
   ========================================================= */

$id_empresa = (int)($_REQUEST['id'] ?? 0);

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   3. VALIDAR EMPRESA
   ========================================================= */

if ($id_empresa <= 0) {
    http_response_code(400);
    exit("Empresa no válida.");
}


/* =========================================================
   4. VALIDAR PERMISOS
   ========================================================= */

$permitido = false;


/* ADMIN puede administrar cualquier empresa */
if ($rol === 'ADMIN' || $rol === 'ADMINISTRADOR') {

    $permitido = true;


/* PROPIETARIO */
} elseif ($rol === 'PROPIETARIO') {

    /* Multiempresa */
    if ($multiempresa === 1) {

        $resultado = $db->obtenerRegistros(
            "SELECT id_empresa
             FROM usuario_empresas
             WHERE id_usuario = $id_usuario
             AND id_empresa = $id_empresa
             LIMIT 1"
        );

        $permitido = !empty($resultado);

    /* Una sola empresa */
    } else {

        $permitido = ($id_empresa === $id_empresa_sesion);
    }
}


if (!$permitido) {

    http_response_code(403);

    exit(
        "No tienes permiso para eliminar esta empresa."
    );
}


/* =========================================================
   5. VALIDAR OPERADORES
   ========================================================= */

$resultado = $db->obtenerRegistros(
    "SELECT COUNT(*) AS total
     FROM operadores
     WHERE id_empresa = $id_empresa"
);

$total_operadores =
    (int)($resultado[0]['total'] ?? 0);


/* =========================================================
   6. VALIDAR USUARIOS
   ========================================================= */

$resultado = $db->obtenerRegistros(
    "SELECT COUNT(*) AS total
     FROM usuarios
     WHERE id_empresa = $id_empresa"
);

$total_usuarios =
    (int)($resultado[0]['total'] ?? 0);


/* =========================================================
   7. VALIDAR REPORTES DE BAJA
   ========================================================= */

$resultado = $db->obtenerRegistros(
    "SELECT COUNT(*) AS total
     FROM reportes_baja
     WHERE id_empresa = $id_empresa"
);

$total_reportes =
    (int)($resultado[0]['total'] ?? 0);


/* =========================================================
   8. BLOQUEAR SI TIENE INFORMACIÓN
   ========================================================= */

if (
    $total_operadores > 0 ||
    $total_usuarios > 0 ||
    $total_reportes > 0
) {

    $motivos = [];

    if ($total_operadores > 0) {
        $motivos[] =
            "$total_operadores operador(es)";
    }

    if ($total_usuarios > 0) {
        $motivos[] =
            "$total_usuarios usuario(s)";
    }

    if ($total_reportes > 0) {
        $motivos[] =
            "$total_reportes reporte(s) de baja";
    }

    http_response_code(409);

    exit(
        "No se puede eliminar esta empresa porque tiene "
        . implode(", ", $motivos)
        . " relacionados."
    );
}


/* =========================================================
   9. EVITAR QUE PROPIETARIO BORRE SU ÚNICA EMPRESA
   ========================================================= */

if ($rol === 'PROPIETARIO') {

    if ($multiempresa === 1) {

        $resultado = $db->obtenerRegistros(
            "SELECT COUNT(*) AS total
             FROM usuario_empresas
             WHERE id_usuario = $id_usuario"
        );

        $cantidad_empresas =
            (int)($resultado[0]['total'] ?? 0);

    } else {

        $cantidad_empresas = 1;
    }


    if ($cantidad_empresas <= 1) {

        http_response_code(409);

        exit(
            "No puedes eliminar tu única empresa."
        );
    }
}


/* =========================================================
   10. GUARDAR PROPIETARIOS RELACIONADOS
   ========================================================= */

$propietarios = $db->obtenerRegistros(
    "SELECT id_usuario
     FROM usuario_empresas
     WHERE id_empresa = $id_empresa"
);


/* =========================================================
   11. ELIMINAR EMPRESA
   ========================================================= */

$eliminado = $db->eliminar(
    "DELETE FROM empresas
     WHERE id_empresa = $id_empresa"
);


if (!$eliminado) {

    http_response_code(500);

    exit(
        "No se pudo eliminar la empresa."
    );
}


/* =========================================================
   12. REVISAR SI ALGÚN PROPIETARIO QUEDÓ CON 1 EMPRESA
   ========================================================= */

foreach ($propietarios as $propietario) {

    $id_propietario =
        (int)$propietario['id_usuario'];


    $empresas_restantes =
        $db->obtenerRegistros(
            "SELECT id_empresa
             FROM usuario_empresas
             WHERE id_usuario = $id_propietario
             ORDER BY id_empresa ASC"
        );


    $cantidad_restantes =
        count($empresas_restantes);


    /* Si quedó solamente con una empresa */
    if ($cantidad_restantes === 1) {

        $empresa_restante =
            (int)$empresas_restantes[0]['id_empresa'];


        $db->actualizar(
            "UPDATE usuarios
             SET multiempresa = 0,
                 id_empresa = $empresa_restante
             WHERE id_usuario = $id_propietario"
        );


        /* Actualizar sesión si es el propietario conectado */
        if ($id_propietario === $id_usuario) {

            $_SESSION['multiempresa'] = 0;

            $_SESSION['id_empresa'] =
                $empresa_restante;

            $multiempresa = 0;

            $id_empresa_sesion =
                $empresa_restante;
        }
    }
}


/* =========================================================
   13. RECARGAR TABLA SEGÚN ROL
   ========================================================= */

if ($rol === 'ADMIN' || $rol === 'ADMINISTRADOR') {

    $sql =
        "SELECT *
         FROM empresas
         ORDER BY id_empresa DESC";


} elseif (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $sql =
        "SELECT e.*
         FROM empresas e
         INNER JOIN usuario_empresas ue
             ON e.id_empresa = ue.id_empresa
         WHERE ue.id_usuario = $id_usuario
         ORDER BY e.id_empresa DESC";


} elseif ($rol === 'PROPIETARIO') {

    $sql =
        "SELECT *
         FROM empresas
         WHERE id_empresa = $id_empresa_sesion";


} else {

    $sql =
        "SELECT *
         FROM empresas
         WHERE 1 = 0";
}


$datos2 =
    $db->obtenerRegistros($sql);


/* =========================================================
   14. DEVOLVER TABLA
   ========================================================= */

include "../empresas/tabla.php";

$db->desconectar();

?>