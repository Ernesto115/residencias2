<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

function responder($ok, $mensaje, $extra = [])
{
    echo json_encode(
        array_merge(
            [
                'ok' => $ok,
                'mensaje' => $mensaje
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    responder(
        false,
        'Método de solicitud no permitido.'
    );
}


/* =========================================================
   SESIÓN
   ========================================================= */

$rol = strtoupper(trim(
    $_SESSION['rol'] ?? ''
));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (in_array(
    $rol,
    ['RH','RECURSOS HUMANOS'],
    true
)) {
    $rol = 'RRHH';
}


$id_usuario = (int)(
    $_SESSION['id_usuario'] ?? 0
);

$id_empresa_sesion = (int)(
    $_SESSION['id_empresa'] ?? 0
);

$multiempresa = (int)(
    $_SESSION['multiempresa'] ?? 0
);


/* =========================================================
   SOLO PROPIETARIO PUEDE EVALUAR Y FINALIZAR
   ========================================================= */

if ($rol !== 'PROPIETARIO') {

    responder(
        false,
        'Solo el propietario puede revisar, evaluar y confirmar una baja.'
    );
}


/* =========================================================
   DATOS
   ========================================================= */

$id_reporte = (int)(
    $_POST['id_reporte'] ?? 0
);


$eval_distancia = (int)(
    $_POST['eval_distancia'] ?? 0
);

$eval_tiempo = (int)(
    $_POST['eval_tiempo'] ?? 0
);

$eval_ganancias = (int)(
    $_POST['eval_ganancias'] ?? 0
);


$eval_cuidado_vehiculo = (int)(
    $_POST['eval_cuidado_vehiculo'] ?? 0
);

$eval_productividad = (int)(
    $_POST['eval_productividad'] ?? 0
);

$eval_rendimiento = (int)(
    $_POST['eval_rendimiento'] ?? 0
);

$eval_cuidado_fisico = (int)(
    $_POST['eval_cuidado_fisico'] ?? 0
);


/* =========================================================
   REPORTE VÁLIDO
   ========================================================= */

if ($id_reporte <= 0) {

    responder(
        false,
        'Reporte de baja no válido.'
    );
}


/* =========================================================
   VALIDACIÓN ESCALA 1 - 5
   ========================================================= */

if (
    $eval_distancia < 1 ||
    $eval_distancia > 5 ||

    $eval_tiempo < 1 ||
    $eval_tiempo > 5 ||

    $eval_ganancias < 1 ||
    $eval_ganancias > 5
) {

    responder(
        false,
        'Completa las evaluaciones de servicio del 1 al 5.'
    );
}


/* =========================================================
   VALIDACIÓN ESCALA 1 - 10
   ========================================================= */

if (
    $eval_cuidado_vehiculo < 1 ||
    $eval_cuidado_vehiculo > 10 ||

    $eval_productividad < 1 ||
    $eval_productividad > 10 ||

    $eval_rendimiento < 1 ||
    $eval_rendimiento > 10 ||

    $eval_cuidado_fisico < 1 ||
    $eval_cuidado_fisico > 10
) {

    responder(
        false,
        'Completa las evaluaciones de desempeño del 1 al 10.'
    );
}


/* =========================================================
   BUSCAR REPORTE
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT
        rb.id_reporte,
        rb.id_operador,
        rb.id_empresa,
        rb.estatus_evaluacion,
        o.estatus AS estatus_operador,
        o.id_empresa AS id_empresa_operador
     FROM reportes_baja rb
     INNER JOIN operadores o
        ON o.id_operador = rb.id_operador
     WHERE rb.id_reporte = :reporte
     LIMIT 1"
);

$stmt->execute([
    ':reporte' => $id_reporte
]);

$reporte = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$reporte) {

    responder(
        false,
        'El reporte de baja no existe.'
    );
}


/* =========================================================
   SOLO REPORTES PENDIENTES
   ========================================================= */

if (
    strtoupper(
        $reporte['estatus_evaluacion']
    ) !== 'PENDIENTE'
) {

    responder(
        false,
        'Esta baja ya fue finalizada anteriormente.'
    );
}


/* =========================================================
   OPERADOR DEBE SEGUIR ACTIVO
   ========================================================= */

if (
    (int)$reporte['estatus_operador'] !== 1
) {

    responder(
        false,
        'El operador ya se encuentra inactivo.'
    );
}


$id_empresa_reporte = (int)(
    $reporte['id_empresa']
);

$id_empresa_operador = (int)(
    $reporte['id_empresa_operador']
);

$id_operador = (int)(
    $reporte['id_operador']
);


/* =========================================================
   OPERADOR NO DEBE HABER CAMBIADO DE EMPRESA
   ========================================================= */

if (
    $id_empresa_operador !==
    $id_empresa_reporte
) {

    responder(
        false,
        'El operador cambió de empresa después de solicitar la baja. Revisa su información antes de continuar.'
    );
}


/* =========================================================
   PERMISO DEL PROPIETARIO
   ========================================================= */

if ($multiempresa === 1) {

    /*
       PROPIETARIO MULTIEMPRESA

       El reporte debe pertenecer a una
       de las empresas asociadas al usuario.
    */

    $stmt = $db->conn->prepare(
        "SELECT 1
         FROM usuario_empresas
         WHERE id_usuario = :usuario
         AND id_empresa = :empresa
         LIMIT 1"
    );

    $stmt->execute([
        ':usuario' => $id_usuario,
        ':empresa' => $id_empresa_reporte
    ]);


    if (!$stmt->fetchColumn()) {

        responder(
            false,
            'No tienes permiso para dar de baja operadores de esta empresa.'
        );
    }

} else {

    /*
       PROPIETARIO DE UNA SOLA EMPRESA
    */

    if (
        $id_empresa_sesion <= 0 ||
        $id_empresa_sesion !==
        $id_empresa_reporte
    ) {

        responder(
            false,
            'No tienes permiso para dar de baja operadores de esta empresa.'
        );
    }
}


/* =========================================================
   CÁLCULOS DEL SERVIDOR
   ========================================================= */

/*
   PROMEDIO DE SERVICIO
   Escala de 1 a 5
*/

$promedio_servicio = round(
    (
        $eval_distancia +
        $eval_tiempo +
        $eval_ganancias
    ) / 3,
    2
);


/*
   CALIFICACIÓN GENERAL
*/

$calificacion_general = round(
    (
        ($eval_distancia * 2) +
        ($eval_tiempo * 2) +
        ($eval_ganancias * 2) +
        $eval_cuidado_vehiculo +
        $eval_productividad +
        $eval_rendimiento +
        $eval_cuidado_fisico
    ) / 7,
    2
);


/* =========================================================
   FINALIZAR BAJA
   ========================================================= */

try {

    $db->conn->beginTransaction();


    /* =====================================================
       GUARDAR EVALUACIÓN
       ===================================================== */

    $stmt = $db->conn->prepare(
        "UPDATE reportes_baja SET

            eval_distancia = :distancia,
            eval_tiempo = :tiempo,
            eval_ganancias = :ganancias,

            promedio_servicio = :promedio,

            eval_cuidado_vehiculo = :cuidado,
            eval_productividad = :productividad,
            eval_rendimiento = :rendimiento,
            eval_cuidado_fisico = :fisico,

            calificacion_cuantitativa = :general,

            fecha_baja = CURDATE(),

            estatus_evaluacion = 'COMPLETADA'

         WHERE id_reporte = :reporte
         AND estatus_evaluacion = 'PENDIENTE'"
    );


    $stmt->execute([

        ':distancia' =>
            $eval_distancia,

        ':tiempo' =>
            $eval_tiempo,

        ':ganancias' =>
            $eval_ganancias,

        ':promedio' =>
            $promedio_servicio,

        ':cuidado' =>
            $eval_cuidado_vehiculo,

        ':productividad' =>
            $eval_productividad,

        ':rendimiento' =>
            $eval_rendimiento,

        ':fisico' =>
            $eval_cuidado_fisico,

        ':general' =>
            $calificacion_general,

        ':reporte' =>
            $id_reporte

    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No fue posible completar el reporte.'
        );
    }


    /* =====================================================
       DESACTIVAR OPERADOR
       ===================================================== */

    $stmt = $db->conn->prepare(
        "UPDATE operadores
         SET estatus = 0
         WHERE id_operador = :operador
         AND id_empresa = :empresa
         AND estatus = 1"
    );


    $stmt->execute([

        ':operador' =>
            $id_operador,

        ':empresa' =>
            $id_empresa_reporte

    ]);


    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'No fue posible cambiar el estatus del operador.'
        );
    }


    /* =====================================================
       CONFIRMAR TRANSACCIÓN
       ===================================================== */

    $db->conn->commit();


    responder(
        true,
        'Baja y evaluación confirmadas correctamente.',
        [
            'calificacion_general' =>
                $calificacion_general,

            'promedio_servicio' =>
                $promedio_servicio
        ]
    );


} catch (Throwable $e) {

    if ($db->conn->inTransaction()) {

        $db->conn->rollBack();

    }


    responder(
        false,
        'No se pudo confirmar la baja.'
    );
}
?>