<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   1. DATOS RECIBIDOS
   ========================================================= */

$id_reporte = (int)($_POST['id_reporte'] ?? 0);

/* ESCALA 1 - 5 */
$eval_distancia = (int)($_POST['eval_distancia'] ?? 0);
$eval_tiempo = (int)($_POST['eval_tiempo'] ?? 0);
$eval_ganancias = (int)($_POST['eval_ganancias'] ?? 0);

/* ESCALA 1 - 10 */
$eval_cuidado_vehiculo = (int)($_POST['eval_cuidado_vehiculo'] ?? 0);
$eval_productividad = (int)($_POST['eval_productividad'] ?? 0);
$eval_rendimiento = (int)($_POST['eval_rendimiento'] ?? 0);
$eval_cuidado_fisico = (int)($_POST['eval_cuidado_fisico'] ?? 0);

/* SESIÓN */
$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   2. FUNCIÓN DE RESPUESTA
   ========================================================= */

function responder($ok, $mensaje, $extra = [])
{
    echo json_encode(
        array_merge([
            'ok' => $ok,
            'mensaje' => $mensaje
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   3. VALIDACIONES BÁSICAS
   ========================================================= */

if ($id_reporte <= 0) {
    responder(false, 'Reporte de baja no válido.');
}


/* VALIDAR ESCALA 1 - 5 */
if (
    $eval_distancia < 1 || $eval_distancia > 5 ||
    $eval_tiempo < 1 || $eval_tiempo > 5 ||
    $eval_ganancias < 1 || $eval_ganancias > 5
) {
    responder(
        false,
        'Completa las evaluaciones de Distancia, Horas de servicio y Ganancias del 1 al 5.'
    );
}


/* VALIDAR ESCALA 1 - 10 */
if (
    $eval_cuidado_vehiculo < 1 || $eval_cuidado_vehiculo > 10 ||
    $eval_productividad < 1 || $eval_productividad > 10 ||
    $eval_rendimiento < 1 || $eval_rendimiento > 10 ||
    $eval_cuidado_fisico < 1 || $eval_cuidado_fisico > 10
) {
    responder(
        false,
        'Completa las evaluaciones de desempeño del 1 al 10.'
    );
}


/* VALIDAR ROL */
if (
    $rol !== 'PROPIETARIO' &&
    $rol !== 'ADMIN' &&
    $rol !== 'ADMINISTRADOR'
) {
    responder(
        false,
        'No tienes permiso para confirmar esta baja.'
    );
}


/* =========================================================
   4. CALCULAR EVALUACIONES
   ========================================================= */

/*
   PROMEDIO DE SERVICIO
   Distancia + Tiempo + Ganancias
   Resultado sobre 5
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

   Los criterios de 1 a 5 se convierten
   primero a escala de 10 multiplicándolos x2.

   Después se promedian los 7 criterios.
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
   5. BUSCAR REPORTE
   ========================================================= */

$sql = "
    SELECT
        rb.id_reporte,
        rb.id_operador,
        rb.id_empresa,
        rb.estatus_evaluacion,
        o.estatus AS estatus_operador
    FROM reportes_baja rb
    INNER JOIN operadores o
        ON o.id_operador = rb.id_operador
    WHERE rb.id_reporte = :id_reporte
    LIMIT 1
";

$stmt = $db->conn->prepare($sql);

$stmt->execute([
    ':id_reporte' => $id_reporte
]);

$reporte = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$reporte) {
    responder(
        false,
        'El reporte de baja no existe.'
    );
}


/* =========================================================
   6. COMPROBAR QUE SIGA PENDIENTE
   ========================================================= */

if (
    strtoupper($reporte['estatus_evaluacion']) !== 'PENDIENTE'
) {
    responder(
        false,
        'Esta baja ya fue finalizada anteriormente.'
    );
}


/* =========================================================
   7. COMPROBAR QUE EL OPERADOR SIGA ACTIVO
   ========================================================= */

if ((int)$reporte['estatus_operador'] !== 1) {
    responder(
        false,
        'El operador ya se encuentra inactivo.'
    );
}


$id_empresa_reporte = (int)$reporte['id_empresa'];
$id_operador = (int)$reporte['id_operador'];


/* =========================================================
   8. PERMISOS DEL PROPIETARIO
   ========================================================= */

if ($rol === 'PROPIETARIO') {

    /* PROPIETARIO MULTIEMPRESA */
    if ($multiempresa === 1) {

        $sqlPermiso = "
            SELECT 1
            FROM usuario_empresas
            WHERE id_usuario = :id_usuario
            AND id_empresa = :id_empresa
            LIMIT 1
        ";

        $stmtPermiso = $db->conn->prepare($sqlPermiso);

        $stmtPermiso->execute([
            ':id_usuario' => $id_usuario,
            ':id_empresa' => $id_empresa_reporte
        ]);

        if (!$stmtPermiso->fetchColumn()) {

            responder(
                false,
                'No tienes permiso para dar de baja operadores de esta empresa.'
            );
        }

    }

    /* PROPIETARIO DE UNA EMPRESA */
    else {

        if ($id_empresa_sesion !== $id_empresa_reporte) {

            responder(
                false,
                'No tienes permiso para dar de baja operadores de esta empresa.'
            );
        }
    }
}


/* =========================================================
   9. FINALIZAR BAJA
   ========================================================= */

try {

    $db->conn->beginTransaction();


    /* =====================================================
       GUARDAR EVALUACIÓN Y COMPLETAR REPORTE
       ===================================================== */

    $sqlReporte = "
        UPDATE reportes_baja
        SET
            eval_distancia = :eval_distancia,
            eval_tiempo = :eval_tiempo,
            eval_ganancias = :eval_ganancias,
            promedio_servicio = :promedio_servicio,

            eval_cuidado_vehiculo = :eval_cuidado_vehiculo,
            eval_productividad = :eval_productividad,
            eval_rendimiento = :eval_rendimiento,
            eval_cuidado_fisico = :eval_cuidado_fisico,

            calificacion_cuantitativa = :calificacion_general,

            fecha_baja = CURDATE(),
            estatus_evaluacion = 'COMPLETADA'

        WHERE id_reporte = :id_reporte
        AND estatus_evaluacion = 'PENDIENTE'
    ";


    $stmtReporte = $db->conn->prepare($sqlReporte);

    $stmtReporte->execute([

        ':eval_distancia' => $eval_distancia,
        ':eval_tiempo' => $eval_tiempo,
        ':eval_ganancias' => $eval_ganancias,

        ':promedio_servicio' => $promedio_servicio,

        ':eval_cuidado_vehiculo' => $eval_cuidado_vehiculo,
        ':eval_productividad' => $eval_productividad,
        ':eval_rendimiento' => $eval_rendimiento,
        ':eval_cuidado_fisico' => $eval_cuidado_fisico,

        ':calificacion_general' => $calificacion_general,

        ':id_reporte' => $id_reporte
    ]);


    if ($stmtReporte->rowCount() !== 1) {

        throw new Exception(
            'No fue posible completar el reporte.'
        );
    }


    /* =====================================================
       DAR DE BAJA AL OPERADOR
       ===================================================== */

    $sqlOperador = "
        UPDATE operadores
        SET estatus = 0
        WHERE id_operador = :id_operador
        AND id_empresa = :id_empresa
        AND estatus = 1
    ";


    $stmtOperador = $db->conn->prepare($sqlOperador);

    $stmtOperador->execute([
        ':id_operador' => $id_operador,
        ':id_empresa' => $id_empresa_reporte
    ]);


    if ($stmtOperador->rowCount() !== 1) {

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
            'calificacion_general' => $calificacion_general,
            'promedio_servicio' => $promedio_servicio
        ]
    );


} catch (Exception $e) {

    if ($db->conn->inTransaction()) {
        $db->conn->rollBack();
    }


    responder(
        false,
        'No se pudo confirmar la baja.'
    );
}
?>