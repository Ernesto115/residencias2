<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

include_once "../db/db.php";

$db = new db();
$db->conectar();

/* =========================================================
   DATOS RECIBIDOS
   ========================================================= */

$id_reporte = (int)($_POST['id_reporte'] ?? 0);
$calificacion = (int)($_POST['calificacion_cuantitativa'] ?? 0);

$rol = strtoupper($_SESSION['rol'] ?? '');
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   FUNCIÓN DE RESPUESTA
   ========================================================= */

function responder($ok, $mensaje)
{
    echo json_encode([
        'ok' => $ok,
        'mensaje' => $mensaje
    ]);

    exit;
}


/* =========================================================
   VALIDACIONES BÁSICAS
   ========================================================= */

if ($id_reporte <= 0) {
    responder(false, 'Reporte de baja no válido.');
}

if ($calificacion < 1 || $calificacion > 10) {
    responder(false, 'Selecciona una calificación del 1 al 10.');
}

if (
    $rol !== 'PROPIETARIO' &&
    $rol !== 'ADMIN' &&
    $rol !== 'ADMINISTRADOR'
) {
    responder(false, 'No tienes permiso para confirmar esta baja.');
}


/* =========================================================
   BUSCAR REPORTE
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
    responder(false, 'El reporte de baja no existe.');
}


/* =========================================================
   COMPROBAR QUE SIGA PENDIENTE
   ========================================================= */

if ($reporte['estatus_evaluacion'] !== 'PENDIENTE') {
    responder(false, 'Esta baja ya fue finalizada anteriormente.');
}


/* =========================================================
   COMPROBAR QUE EL OPERADOR SIGA ACTIVO
   ========================================================= */

if ((int)$reporte['estatus_operador'] !== 1) {
    responder(false, 'El operador ya se encuentra inactivo.');
}


$id_empresa_reporte = (int)$reporte['id_empresa'];
$id_operador = (int)$reporte['id_operador'];


/* =========================================================
   PERMISOS DEL PROPIETARIO
   ========================================================= */

if ($rol === 'PROPIETARIO') {

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
            responder(false, 'No tienes permiso para dar de baja operadores de esta empresa.');
        }

    } else {

        if ($id_empresa_sesion !== $id_empresa_reporte) {
            responder(false, 'No tienes permiso para dar de baja operadores de esta empresa.');
        }
    }
}


/* =========================================================
   FINALIZAR BAJA
   ========================================================= */

try {

    $db->conn->beginTransaction();


    /* COMPLETAR REPORTE */
    $sqlReporte = "
        UPDATE reportes_baja
        SET
            calificacion_cuantitativa = :calificacion,
            fecha_baja = CURDATE(),
            estatus_evaluacion = 'COMPLETADA'
        WHERE id_reporte = :id_reporte
        AND estatus_evaluacion = 'PENDIENTE'
    ";

    $stmtReporte = $db->conn->prepare($sqlReporte);

    $stmtReporte->execute([
        ':calificacion' => $calificacion,
        ':id_reporte' => $id_reporte
    ]);


    if ($stmtReporte->rowCount() !== 1) {
        throw new Exception('No fue posible completar el reporte.');
    }


    /* DAR DE BAJA AL OPERADOR */
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
        throw new Exception('No fue posible cambiar el estatus del operador.');
    }


    $db->conn->commit();


    responder(
        true,
        'Baja confirmada correctamente.'
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