<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include_once "../db/db.php";


$db = new db();

$db->conectar();


function errorEliminarEmpresa(
    $mensaje,
    $codigo = 409
) {

    http_response_code(
        $codigo
    );

    exit(
        $mensaje
    );
}


$rol =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if ($rol === 'ADMINISTRADOR') {

    $rol = 'ADMIN';
}


$id_empresa =
    (int)(
        $_GET['id'] ??
        $_REQUEST['id'] ??
        0
    );


/* =========================================================
   SOLO ADMIN PUEDE ELIMINAR EMPRESAS
   ========================================================= */

if ($rol !== 'ADMIN') {

    errorEliminarEmpresa(
        'Solo el administrador puede eliminar empresas.',
        403
    );
}


/* =========================================================
   VALIDAR ID
   ========================================================= */

if ($id_empresa <= 0) {

    errorEliminarEmpresa(
        'La empresa seleccionada no es válida.',
        400
    );
}


/* =========================================================
   VALIDAR EXISTENCIA
   ========================================================= */

$stmt =
    $db->conn->prepare(

        "SELECT
            id_empresa,
            nombre_empresa

         FROM empresas

         WHERE id_empresa = :empresa

         LIMIT 1"
    );


$stmt->execute([

    ':empresa' =>
        $id_empresa

]);


$empresa =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$empresa) {

    errorEliminarEmpresa(
        'La empresa que intentas eliminar no existe.',
        404
    );
}


/* =========================================================
   VALIDAR DEPENDENCIAS
   ========================================================= */

$dependencias = [];


/* OPERADORES */

$stmt =
    $db->conn->prepare(

        "SELECT COUNT(*)

         FROM operadores

         WHERE id_empresa = :empresa"
    );


$stmt->execute([

    ':empresa' =>
        $id_empresa

]);


$total_operadores =
    (int)$stmt->fetchColumn();


if ($total_operadores > 0) {

    $dependencias[] =
        "$total_operadores operador(es)";
}


/* USUARIOS */

$stmt =
    $db->conn->prepare(

        "SELECT COUNT(*)

         FROM usuarios

         WHERE id_empresa = :empresa"
    );


$stmt->execute([

    ':empresa' =>
        $id_empresa

]);


$total_usuarios =
    (int)$stmt->fetchColumn();


if ($total_usuarios > 0) {

    $dependencias[] =
        "$total_usuarios usuario(s)";
}


/* REPORTES DE BAJA */

$stmt =
    $db->conn->prepare(

        "SELECT COUNT(*)

         FROM reportes_baja

         WHERE id_empresa = :empresa"
    );


$stmt->execute([

    ':empresa' =>
        $id_empresa

]);


$total_reportes =
    (int)$stmt->fetchColumn();


if ($total_reportes > 0) {

    $dependencias[] =
        "$total_reportes reporte(s) de baja";
}


/* =========================================================
   BLOQUEAR SI EXISTEN RELACIONES
   ========================================================= */

if ($dependencias) {

    errorEliminarEmpresa(

        'No se puede eliminar esta empresa porque tiene ' .

        implode(
            ', ',
            $dependencias
        ) .

        ' relacionados.'
    );
}


/* =========================================================
   PROPIETARIOS ASOCIADOS
   ========================================================= */

$stmt =
    $db->conn->prepare(

        "SELECT DISTINCT
            id_usuario

         FROM usuario_empresas

         WHERE id_empresa = :empresa"
    );


$stmt->execute([

    ':empresa' =>
        $id_empresa

]);


$propietarios =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   ELIMINAR EN TRANSACCIÓN
   ========================================================= */

try {

    $db->conn
        ->beginTransaction();


    /* QUITAR RELACIONES */

    $stmt =
        $db->conn->prepare(

            "DELETE FROM usuario_empresas

             WHERE id_empresa = :empresa"
        );


    $stmt->execute([

        ':empresa' =>
            $id_empresa

    ]);


    /* ELIMINAR EMPRESA */

    $stmt =
        $db->conn->prepare(

            "DELETE FROM empresas

             WHERE id_empresa = :empresa"
        );


    $stmt->execute([

        ':empresa' =>
            $id_empresa

    ]);


    if (
        $stmt->rowCount() !== 1
    ) {

        throw new Exception(
            'No fue posible eliminar la empresa.'
        );
    }


    /* =====================================================
       AJUSTAR PROPIETARIOS AFECTADOS
       ===================================================== */

    foreach (
        $propietarios
        as $propietario
    ) {

        $id_propietario =
            (int)$propietario[
                'id_usuario'
            ];


        $stmt =
            $db->conn->prepare(

                "SELECT id_empresa

                 FROM usuario_empresas

                 WHERE id_usuario = :usuario

                 ORDER BY id_empresa ASC"
            );


        $stmt->execute([

            ':usuario' =>
                $id_propietario

        ]);


        $restantes =
            $stmt->fetchAll(
                PDO::FETCH_COLUMN
            );


        $cantidad =
            count(
                $restantes
            );


        /* QUEDÓ CON UNA EMPRESA */

        if ($cantidad === 1) {

            $empresa_restante =
                (int)$restantes[0];


            $stmt =
                $db->conn->prepare(

                    "UPDATE usuarios

                     SET
                        multiempresa = 0,
                        id_empresa = :empresa

                     WHERE id_usuario = :usuario"
                );


            $stmt->execute([

                ':empresa' =>
                    $empresa_restante,

                ':usuario' =>
                    $id_propietario

            ]);


            /* LIMPIAR TABLA PUENTE */

            $stmt =
                $db->conn->prepare(

                    "DELETE FROM usuario_empresas

                     WHERE id_usuario = :usuario"
                );


            $stmt->execute([

                ':usuario' =>
                    $id_propietario

            ]);


        /* SIGUE SIENDO MULTIEMPRESA */

        } elseif (
            $cantidad >= 2
        ) {

            $stmt =
                $db->conn->prepare(

                    "SELECT id_empresa

                     FROM usuarios

                     WHERE id_usuario = :usuario

                     LIMIT 1"
                );


            $stmt->execute([

                ':usuario' =>
                    $id_propietario

            ]);


            $empresaBase =
                (int)$stmt
                    ->fetchColumn();


            $restantesInt =
                array_map(
                    'intval',
                    $restantes
                );


            if (
                !in_array(
                    $empresaBase,
                    $restantesInt,
                    true
                )
            ) {

                $empresaBase =
                    (int)$restantes[0];
            }


            $stmt =
                $db->conn->prepare(

                    "UPDATE usuarios

                     SET
                        multiempresa = 1,
                        id_empresa = :empresa

                     WHERE id_usuario = :usuario"
                );


            $stmt->execute([

                ':empresa' =>
                    $empresaBase,

                ':usuario' =>
                    $id_propietario

            ]);
        }
    }


    $db->conn
        ->commit();


} catch (Throwable $e) {


    if (
        $db->conn
            ->inTransaction()
    ) {

        $db->conn
            ->rollBack();
    }


    errorEliminarEmpresa(

        'No se pudo eliminar la empresa debido a una relación existente.',

        500
    );
}


/* =========================================================
   RECARGAR TABLA PARA ADMIN
   ========================================================= */

$datos2 =
    $db->obtenerRegistros(

        "SELECT *

         FROM empresas

         ORDER BY id_empresa DESC"
    );


include "../empresas/tabla.php";


$db->desconectar();

?>