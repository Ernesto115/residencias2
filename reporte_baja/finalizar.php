<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   RESPUESTA JSON CON CÓDIGO HTTP
   ========================================================= */

function responder(
    $ok,
    $mensaje,
    $extra = [],
    $codigoHttp = 200
) {
    http_response_code($codigoHttp);

    echo json_encode(
        array_merge(
            [
                'ok' => $ok,
                'mensaje' => $mensaje
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Allow: POST');

    responder(
        false,
        'Método de solicitud no permitido.',
        [],
        405
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
    ['RH', 'RECURSOS HUMANOS'],
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
   SESIÓN VÁLIDA
   ========================================================= */

if (
    $id_usuario <= 0 ||
    $rol === ''
) {

    responder(
        false,
        'Tu sesión no es válida o ha finalizado.',
        [],
        401
    );
}


/* =========================================================
   SOLO PROPIETARIO PUEDE EVALUAR Y FINALIZAR
   ========================================================= */

if ($rol !== 'PROPIETARIO') {

    responder(
        false,
        'Solo el propietario puede revisar, evaluar y confirmar una baja.',
        [],
        403
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
        'Reporte de baja no válido.',
        [],
        400
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
        'Completa las evaluaciones de servicio del 1 al 5.',
        [],
        400
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
        'Completa las evaluaciones de desempeño del 1 al 10.',
        [],
        400
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
        'El reporte de baja no existe.',
        [],
        404
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
   PERMISO DEL PROPIETARIO
   SE VALIDA ANTES DE REVELAR EL ESTADO DEL REPORTE
   ========================================================= */

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
        ':empresa' => $id_empresa_reporte
    ]);


    if (!$stmt->fetchColumn()) {

        responder(
            false,
            'No tienes permiso para dar de baja operadores de esta empresa.',
            [],
            403
        );
    }

} else {

    if (
        $id_empresa_sesion <= 0 ||
        $id_empresa_sesion !== $id_empresa_reporte
    ) {

        responder(
            false,
            'No tienes permiso para dar de baja operadores de esta empresa.',
            [],
            403
        );
    }
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
        'Esta baja ya fue finalizada anteriormente.',
        [],
        409
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
        'El operador ya se encuentra inactivo.',
        [],
        409
    );
}


/* =========================================================
   OPERADOR NO DEBE HABER CAMBIADO DE EMPRESA
   ========================================================= */

if (
    $id_empresa_operador !==
    $id_empresa_reporte
) {

    responder(
        false,
        'El operador cambió de empresa después de solicitar la baja. Revisa su información antes de continuar.',
        [],
        409
    );
}


/* =========================================================
   CÁLCULOS DEL SERVIDOR
   ========================================================= */

$promedio_servicio = round(
    (
        $eval_distancia +
        $eval_tiempo +
        $eval_ganancias
    ) / 3,
    2
);


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
        ':distancia' => $eval_distancia,
        ':tiempo' => $eval_tiempo,
        ':ganancias' => $eval_ganancias,
        ':promedio' => $promedio_servicio,
        ':cuidado' => $eval_cuidado_vehiculo,
        ':productividad' => $eval_productividad,
        ':rendimiento' => $eval_rendimiento,
        ':fisico' => $eval_cuidado_fisico,
        ':general' => $calificacion_general,
        ':reporte' => $id_reporte
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new RuntimeException(
            'El reporte cambió de estado antes de poder finalizarlo.',
            409
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
        ':operador' => $id_operador,
        ':empresa' => $id_empresa_reporte
    ]);


    if ($stmt->rowCount() !== 1) {

        throw new RuntimeException(
            'El operador cambió de estado antes de poder completar la baja.',
            409
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
        ],
        200
    );


} catch (Throwable $e) {

    if ($db->conn->inTransaction()) {
        $db->conn->rollBack();
    }


    if ((int)$e->getCode() === 409) {

        responder(
            false,
            $e->getMessage(),
            [],
            409
        );
    }


    responder(
        false,
        'No se pudo confirmar la baja.',
        [],
        500
    );
}

?>
