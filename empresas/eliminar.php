<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   1. SESIÓN
   ========================================================= */

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$id_empresa = (int)(
    $_GET['id'] ??
    $_REQUEST['id'] ??
    0
);


/* =========================================================
   2. ERROR
   ========================================================= */

function errorEliminarEmpresa($mensaje, $codigo = 409)
{
    http_response_code($codigo);
    exit($mensaje);
}


/* =========================================================
   3. ROLES PERMITIDOS
   ========================================================= */

if (!in_array($rol, ['ADMIN', 'PROPIETARIO'], true)) {

    errorEliminarEmpresa(
        'No tienes permiso para eliminar empresas.',
        403
    );
}


/* =========================================================
   4. VALIDAR ID
   ========================================================= */

if ($id_empresa <= 0) {

    errorEliminarEmpresa(
        'La empresa seleccionada no es válida.',
        400
    );
}


/* =========================================================
   5. VALIDAR EXISTENCIA
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT id_empresa, nombre_empresa
     FROM empresas
     WHERE id_empresa = :empresa
     LIMIT 1"
);

$stmt->execute([
    ':empresa' => $id_empresa
]);

$empresa = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$empresa) {

    errorEliminarEmpresa(
        'La empresa que intentas eliminar no existe.',
        404
    );
}


/* =========================================================
   6. PERMISOS DEL PROPIETARIO
   ========================================================= */

if ($rol === 'PROPIETARIO') {


    /* PROPIETARIO MULTIEMPRESA */
    if ($multiempresa === 1) {

        $stmt = $db->conn->prepare(
            "SELECT 1
             FROM usuario_empresas
             WHERE id_usuario = :usuario
             AND id_empresa = :empresa
             LIMIT 1"
        );

        $stmt->execute([
            ':usuario' => $id_usuario,
            ':empresa' => $id_empresa
        ]);


        if (!$stmt->fetchColumn()) {

            errorEliminarEmpresa(
                'No tienes permiso para eliminar esta empresa.',
                403
            );
        }


        /* NO ELIMINAR SU ÚNICA EMPRESA */

        $stmt = $db->conn->prepare(
            "SELECT COUNT(*)
             FROM usuario_empresas
             WHERE id_usuario = :usuario"
        );

        $stmt->execute([
            ':usuario' => $id_usuario
        ]);


        if ((int)$stmt->fetchColumn() <= 1) {

            errorEliminarEmpresa(
                'No puedes eliminar tu única empresa.'
            );
        }


        /* =================================================
           NO BORRAR EMPRESA COMPARTIDA
           ================================================= */

        $stmt = $db->conn->prepare(
            "SELECT COUNT(DISTINCT id_usuario)
             FROM usuario_empresas
             WHERE id_empresa = :empresa
             AND id_usuario <> :usuario"
        );

        $stmt->execute([
            ':empresa' => $id_empresa,
            ':usuario' => $id_usuario
        ]);


        if ((int)$stmt->fetchColumn() > 0) {

            errorEliminarEmpresa(
                'Esta empresa también está asociada a otro propietario. Solo un administrador puede eliminarla.'
            );
        }


    /* PROPIETARIO DE UNA EMPRESA */
    } elseif ($id_empresa !== $id_empresa_sesion) {

        errorEliminarEmpresa(
            'No tienes permiso para eliminar esta empresa.',
            403
        );

    } else {

        errorEliminarEmpresa(
            'No puedes eliminar tu única empresa.'
        );
    }
}


/* =========================================================
   7. VALIDAR DEPENDENCIAS
   ========================================================= */

$dependencias = [];


/* OPERADORES */

$stmt = $db->conn->prepare(
    "SELECT COUNT(*)
     FROM operadores
     WHERE id_empresa = :empresa"
);

$stmt->execute([
    ':empresa' => $id_empresa
]);

$total_operadores = (int)$stmt->fetchColumn();


if ($total_operadores > 0) {
    $dependencias[] = "$total_operadores operador(es)";
}


/* USUARIOS */

$stmt = $db->conn->prepare(
    "SELECT COUNT(*)
     FROM usuarios
     WHERE id_empresa = :empresa"
);

$stmt->execute([
    ':empresa' => $id_empresa
]);

$total_usuarios = (int)$stmt->fetchColumn();


if ($total_usuarios > 0) {
    $dependencias[] = "$total_usuarios usuario(s)";
}


/* REPORTES DE BAJA */

$stmt = $db->conn->prepare(
    "SELECT COUNT(*)
     FROM reportes_baja
     WHERE id_empresa = :empresa"
);

$stmt->execute([
    ':empresa' => $id_empresa
]);

$total_reportes = (int)$stmt->fetchColumn();


if ($total_reportes > 0) {
    $dependencias[] = "$total_reportes reporte(s) de baja";
}


/* BLOQUEAR SI EXISTEN RELACIONES */

if ($dependencias) {

    errorEliminarEmpresa(
        'No se puede eliminar esta empresa porque tiene ' .
        implode(', ', $dependencias) .
        ' relacionados.'
    );
}


/* =========================================================
   8. PROPIETARIOS ASOCIADOS
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT DISTINCT id_usuario
     FROM usuario_empresas
     WHERE id_empresa = :empresa"
);

$stmt->execute([
    ':empresa' => $id_empresa
]);

$propietarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   9. ELIMINAR EN TRANSACCIÓN
   ========================================================= */

try {

    $db->conn->beginTransaction();


    /* =====================================================
       QUITAR RELACIONES DE LA EMPRESA A ELIMINAR
       ===================================================== */

    $stmt = $db->conn->prepare(
        "DELETE FROM usuario_empresas
         WHERE id_empresa = :empresa"
    );

    $stmt->execute([
        ':empresa' => $id_empresa
    ]);


    /* =====================================================
       ELIMINAR EMPRESA
       ===================================================== */

    $stmt = $db->conn->prepare(
        "DELETE FROM empresas
         WHERE id_empresa = :empresa"
    );

    $stmt->execute([
        ':empresa' => $id_empresa
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No fue posible eliminar la empresa.'
        );
    }


    /* =====================================================
       10. ACTUALIZAR PROPIETARIOS AFECTADOS
       ===================================================== */

    foreach ($propietarios as $propietario) {

        $id_propietario =
            (int)$propietario['id_usuario'];


        /* EMPRESAS QUE TODAVÍA LE QUEDAN */

        $stmt = $db->conn->prepare(
            "SELECT id_empresa
             FROM usuario_empresas
             WHERE id_usuario = :usuario
             ORDER BY id_empresa ASC"
        );

        $stmt->execute([
            ':usuario' => $id_propietario
        ]);

        $restantes =
            $stmt->fetchAll(PDO::FETCH_COLUMN);

        $cantidad =
            count($restantes);


        /* =================================================
           QUEDÓ CON UNA SOLA EMPRESA
           ================================================= */

        if ($cantidad === 1) {

            $empresa_restante =
                (int)$restantes[0];


            /*
               Convertir nuevamente al propietario
               en usuario de una sola empresa.
            */

            $stmt = $db->conn->prepare(
                "UPDATE usuarios
                 SET
                    multiempresa = 0,
                    id_empresa = :empresa
                 WHERE id_usuario = :usuario"
            );

            $stmt->execute([
                ':empresa' => $empresa_restante,
                ':usuario' => $id_propietario
            ]);


            /* =================================================
               NUEVO:
               LIMPIAR usuario_empresas AL VOLVER A UNA EMPRESA
               ================================================= */

            $stmt = $db->conn->prepare(
                "DELETE FROM usuario_empresas
                 WHERE id_usuario = :usuario"
            );

            $stmt->execute([
                ':usuario' => $id_propietario
            ]);


            /* =================================================
               ACTUALIZAR SESIÓN SI ES EL USUARIO CONECTADO
               ================================================= */

            if ($id_propietario === $id_usuario) {

                $_SESSION['multiempresa'] = 0;

                $_SESSION['id_empresa'] =
                    $empresa_restante;

                $multiempresa = 0;

                $id_empresa_sesion =
                    $empresa_restante;
            }


        /* =================================================
           TODAVÍA TIENE DOS O MÁS EMPRESAS
           ================================================= */

        } elseif ($cantidad >= 2) {

            /*
               Obtener empresa base almacenada actualmente
               en usuarios.
            */

            $stmt = $db->conn->prepare(
                "SELECT id_empresa
                 FROM usuarios
                 WHERE id_usuario = :usuario
                 LIMIT 1"
            );

            $stmt->execute([
                ':usuario' => $id_propietario
            ]);

            $empresaBase =
                (int)$stmt->fetchColumn();


            $restantesInt =
                array_map(
                    'intval',
                    $restantes
                );


            /*
               Si la empresa base fue eliminada,
               tomar una de las restantes.
            */

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


            /* SIGUE SIENDO MULTIEMPRESA */

            $stmt = $db->conn->prepare(
                "UPDATE usuarios
                 SET
                    multiempresa = 1,
                    id_empresa = :empresa
                 WHERE id_usuario = :usuario"
            );

            $stmt->execute([
                ':empresa' => $empresaBase,
                ':usuario' => $id_propietario
            ]);


            /* ACTUALIZAR SESIÓN */

            if ($id_propietario === $id_usuario) {

                $_SESSION['multiempresa'] = 1;

                $_SESSION['id_empresa'] =
                    $empresaBase;

                $multiempresa = 1;

                $id_empresa_sesion =
                    $empresaBase;
            }
        }
    }


    /* TODO CORRECTO */

    $db->conn->commit();


} catch (Throwable $e) {

    if ($db->conn->inTransaction()) {
        $db->conn->rollBack();
    }

    errorEliminarEmpresa(
        'No se pudo eliminar la empresa debido a una relación existente.',
        500
    );
}


/* =========================================================
   11. RECARGAR TABLA
   ========================================================= */

if ($rol === 'ADMIN') {

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
            ON ue.id_empresa = e.id_empresa
         WHERE ue.id_usuario = $id_usuario
         ORDER BY e.id_empresa DESC";


} elseif (
    $rol === 'PROPIETARIO' &&
    $id_empresa_sesion > 0
) {

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


include "../empresas/tabla.php";


$db->desconectar();
?>