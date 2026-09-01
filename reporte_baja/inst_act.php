<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   SESIÓN
   ========================================================= */

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (in_array($rol, ['RH', 'RECURSOS HUMANOS'], true)) {
    $rol = 'RRHH';
}

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   ERROR CONTROLADO
   ========================================================= */

function errorReporte($mensaje, $db)
{
    $mensajeJS = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    echo "<!-- Error MySQL -->
    <script>
        if(typeof Swal!=='undefined'){
            Swal.fire({
                icon:'warning',
                title:'No se pudo registrar la baja',
                text:$mensajeJS,
                confirmButtonText:'Entendido',
                confirmButtonColor:'#e11d48',
                background:'#1e293b',
                color:'#ffffff'
            });
        }else{
            alert('⚠️ '+$mensajeJS);
        }
    </script>";

    $db->desconectar();
    exit;
}


/* =========================================================
   SOLO POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    errorReporte(
        'Método de solicitud no permitido.',
        $db
    );
}


/* =========================================================
   ADMIN = SOLO CONSULTA
   ========================================================= */

if ($rol === 'ADMIN') {

    errorReporte(
        'El administrador solo puede consultar los reportes de baja. La solicitud debe realizarla la empresa responsable del operador.',
        $db
    );
}


/* =========================================================
   ROLES QUE PUEDEN SOLICITAR BAJA
   ========================================================= */

if (!in_array($rol, ['PROPIETARIO', 'RRHH'], true)) {

    errorReporte(
        'No tienes permiso para solicitar bajas de operadores.',
        $db
    );
}


/* =========================================================
   DATOS
   ========================================================= */

$id_reporte = (int)($_POST['id_reporte'] ?? 0);
$id_operador = (int)($_POST['id_operador'] ?? 0);
$id_empresa_form = (int)($_POST['id_empresa'] ?? 0);

$motivo_baja = strtoupper(
    trim($_POST['motivo_baja'] ?? '')
);

$calif_cualitativa = trim(
    $_POST['calif_cualitativa'] ?? ''
);


/* =========================================================
   NO EDITAR SOLICITUDES EXISTENTES
   ========================================================= */

if ($id_reporte > 0) {

    errorReporte(
        'Las solicitudes de baja existentes no pueden modificarse.',
        $db
    );
}


/* =========================================================
   VALIDAR OPERADOR
   ========================================================= */

if ($id_operador <= 0) {

    errorReporte(
        'Debes seleccionar un operador.',
        $db
    );
}


/* =========================================================
   MOTIVOS PERMITIDOS
   ========================================================= */

$motivosPermitidos = [
    'ROBO',
    'GASTO_COMBUSTIBLE',
    'CHOQUES',
    'MULTAS',
    'FALTAS',
    'RENUNCIA_VOLUNTARIA',
    'DESPIDO',
    'ABANDONO_TRABAJO',
    'INCUMPLIMIENTO',
    'OTRO'
];


if (!in_array($motivo_baja, $motivosPermitidos, true)) {

    errorReporte(
        'El motivo de baja seleccionado no es válido.',
        $db
    );
}


/* =========================================================
   MOTIVO OTRO
   ========================================================= */

if ($motivo_baja === 'OTRO') {

    if ($calif_cualitativa === '') {

        errorReporte(
            'Debes especificar el motivo cuando seleccionas "Otro".',
            $db
        );
    }


    if (
        mb_strlen(
            $calif_cualitativa,
            'UTF-8'
        ) > 500
    ) {

        errorReporte(
            'El comentario del motivo no puede superar 500 caracteres.',
            $db
        );
    }

} else {

    $calif_cualitativa = '';
}


/* =========================================================
   OBTENER OPERADOR
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT
        id_operador,
        id_empresa,
        estatus,
        fecha_ingreso
     FROM operadores
     WHERE id_operador = :id
     LIMIT 1"
);

$stmt->execute([
    ':id' => $id_operador
]);

$operador = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$operador) {

    errorReporte(
        'El operador seleccionado no existe.',
        $db
    );
}


$id_empresa_operador =
    (int)$operador['id_empresa'];


/* =========================================================
   OPERADOR DEBE ESTAR ACTIVO
   ========================================================= */

if ((int)$operador['estatus'] !== 1) {

    errorReporte(
        'Este operador ya se encuentra inactivo.',
        $db
    );
}


/* =========================================================
   EMPRESA DEL FORMULARIO DEBE COINCIDIR
   ========================================================= */

if (
    $id_empresa_form > 0 &&
    $id_empresa_form !== $id_empresa_operador
) {

    errorReporte(
        'La empresa seleccionada no corresponde al operador.',
        $db
    );
}


/* =========================================================
   VALIDAR PERMISOS DE LA EMPRESA
   ========================================================= */

$permitido = false;


/* PROPIETARIO MULTIEMPRESA */

if (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $stmt = $db->conn->prepare(
        "SELECT 1
         FROM usuario_empresas
         WHERE id_usuario = :usuario
         AND id_empresa = :empresa
         LIMIT 1"
    );

    $stmt->execute([
        ':usuario' => $id_usuario,
        ':empresa' => $id_empresa_operador
    ]);

    $permitido =
        (bool)$stmt->fetchColumn();


/* PROPIETARIO NORMAL / RRHH */

} elseif (
    $rol === 'PROPIETARIO' ||
    $rol === 'RRHH'
) {

    $permitido =
        $id_empresa_sesion > 0 &&
        $id_empresa_operador === $id_empresa_sesion;
}


if (!$permitido) {

    errorReporte(
        'No tienes permiso para reportar la baja de este operador.',
        $db
    );
}


/* =========================================================
   EVITAR DOS BAJAS PENDIENTES
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT 1
     FROM reportes_baja
     WHERE id_operador = :operador
     AND estatus_evaluacion = 'PENDIENTE'
     LIMIT 1"
);

$stmt->execute([
    ':operador' => $id_operador
]);


if ($stmt->fetchColumn()) {

    errorReporte(
        'Este operador ya tiene una solicitud de baja pendiente.',
        $db
    );
}


/* =========================================================
   INSERTAR SOLICITUD
   ========================================================= */

$stmt = $db->conn->prepare(
    "INSERT INTO reportes_baja (
        id_operador,
        id_empresa,
        motivo_baja,
        calificacion_cuantitativa,
        calif_cualitativa,
        fecha_ingreso,
        fecha_baja,
        estatus_evaluacion
    )
    VALUES (
        :operador,
        :empresa,
        :motivo,
        NULL,
        :comentario,
        :fecha_ingreso,
        NULL,
        'PENDIENTE'
    )"
);


$guardado = $stmt->execute([

    ':operador' =>
        $id_operador,

    ':empresa' =>
        $id_empresa_operador,

    ':motivo' =>
        $motivo_baja,

    ':comentario' =>
        $calif_cualitativa,

    ':fecha_ingreso' =>
        $operador['fecha_ingreso']
            ?: null
]);


if (!$guardado) {

    errorReporte(
        'No se pudo registrar la solicitud de baja.',
        $db
    );
}


/* =========================================================
   RECARGAR TABLA
   ========================================================= */

$busqueda = '';
$estatus_filtro = 'todos';
$pagina_actual = 1;
$registros_por_pagina = 5;

$condiciones = [];


/* PROPIETARIO MULTIEMPRESA */

if (
    $rol === 'PROPIETARIO' &&
    $multiempresa === 1
) {

    $condiciones[] = "
        rb.id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $id_usuario
        )
    ";


/* PROPIETARIO NORMAL / RRHH */

} elseif (
    $rol === 'PROPIETARIO' ||
    $rol === 'RRHH'
) {

    $condiciones[] =
        $id_empresa_sesion > 0
        ? "rb.id_empresa = $id_empresa_sesion"
        : "1 = 0";
}


$where = $condiciones
    ? "WHERE " . implode(
        " AND ",
        $condiciones
    )
    : "";


/* TOTAL */

$res = $db->obtenerRegistros(
    "SELECT COUNT(*) AS total
     FROM reportes_baja rb
     $where"
);

$total_registros =
    (int)($res[0]['total'] ?? 0);

$total_paginas = max(
    1,
    (int)ceil(
        $total_registros /
        $registros_por_pagina
    )
);


/* REPORTES */

$datos2 = $db->obtenerRegistros(
    "SELECT
        rb.*,
        CONCAT(
            o.nombres,' ',
            o.primer_apellido,' ',
            o.segundo_apellido
        ) AS nombre_operador,
        e.nombre_empresa

     FROM reportes_baja rb

     INNER JOIN operadores o
        ON o.id_operador = rb.id_operador

     INNER JOIN empresas e
        ON e.id_empresa = rb.id_empresa

     $where

     ORDER BY rb.id_reporte DESC

     LIMIT 5"
);


/* =========================================================
   TABLA
   ========================================================= */

include "../reporte_baja/tabla.php";

$db->desconectar();

?>