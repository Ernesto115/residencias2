<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once "../db/db.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;


/* SESIÓN */
$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') $rol = 'ADMIN';

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$id_empresa_sesion = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$id_reporte = (int)($_GET['id'] ?? 0);


/* SOLO ADMIN / PROPIETARIO */
if (!in_array($rol, ['ADMIN','PROPIETARIO'], true)) {
    die('No tienes permiso para consultar esta constancia.');
}

if ($id_reporte <= 0) {
    die('Reporte no válido.');
}


$db = new db();
$db->conectar();


/* REPORTE */
$stmt = $db->conn->prepare(
    "SELECT
        rb.id_empresa,
        rb.fecha_ingreso,
        rb.fecha_baja,
        rb.estatus_evaluacion,
        o.nombres,
        o.primer_apellido,
        o.segundo_apellido,
        o.rfc,
        e.nombre_empresa,
        e.razon_social,
        e.direccion_fiscal,
        e.responsable
     FROM reportes_baja rb
     INNER JOIN operadores o
        ON o.id_operador=rb.id_operador
     INNER JOIN empresas e
        ON e.id_empresa=rb.id_empresa
     WHERE rb.id_reporte=:id
     LIMIT 1"
);

$stmt->execute([':id'=>$id_reporte]);

$datos = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$datos) {
    die('No se encontró el reporte.');
}

if (
    strtoupper($datos['estatus_evaluacion']) !== 'COMPLETADA'
) {
    die('La baja todavía no ha sido completada.');
}


/* PERMISO PROPIETARIO */
if ($rol === 'PROPIETARIO') {

    $id_empresa = (int)$datos['id_empresa'];

    if ($multiempresa === 1) {

        $stmt = $db->conn->prepare(
            "SELECT 1
             FROM usuario_empresas
             WHERE id_usuario=:usuario
             AND id_empresa=:empresa
             LIMIT 1"
        );

        $stmt->execute([
            ':usuario'=>$id_usuario,
            ':empresa'=>$id_empresa
        ]);

        if (!$stmt->fetchColumn()) {
            die(
                'No tienes permiso para consultar esta constancia.'
            );
        }

    } elseif (
        $id_empresa_sesion <= 0 ||
        $id_empresa_sesion !== $id_empresa
    ) {

        die(
            'No tienes permiso para consultar esta constancia.'
        );
    }
}


/* FUNCIONES */
function limpiar($texto)
{
    return htmlspecialchars(
        $texto ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

function fechaBonita($fecha)
{
    return $fecha
        ? date('d/m/Y',strtotime($fecha))
        : 'No disponible';
}


/* DATOS */
$operador = limpiar(trim(
    $datos['nombres'].' '.
    $datos['primer_apellido'].' '.
    $datos['segundo_apellido']
));

$empresa = limpiar($datos['nombre_empresa']);
$razon = limpiar($datos['razon_social']);
$direccion = limpiar($datos['direccion_fiscal']);
$responsable = limpiar($datos['responsable']);
$rfc = limpiar($datos['rfc']);

$fechaIngreso = fechaBonita($datos['fecha_ingreso']);
$fechaBaja = fechaBonita($datos['fecha_baja']);
$fechaHoy = date('d/m/Y');


/* CSS */
$rutaCSS = "../css/styles.css";

$css = file_exists($rutaCSS)
    ? file_get_contents($rutaCSS)
    : '';


/* PDF */
$html = "
<html>
<head>
<meta charset='UTF-8'>
<style>$css</style>
</head>

<body class='pdf-body'>

<div class='pdf-marco'>

    <div class='pdf-encabezado'>
        <div class='pdf-empresa'>$empresa</div>
        <div>$razon</div>
        <div>$direccion</div>
    </div>

    <h1 class='pdf-titulo'>
        CONSTANCIA LABORAL
    </h1>

    <div class='pdf-texto'>

        <p>
            Por medio de la presente se hace constar que
            <strong>$operador</strong>, con RFC
            <strong>$rfc</strong>, laboró en
            <strong>$empresa</strong> durante el periodo comprendido
            del <strong>$fechaIngreso</strong> al
            <strong>$fechaBaja</strong>.
        </p>

        <p>
            Se expide la presente constancia para los fines
            que al interesado convengan.
        </p>

    </div>

    <div class='pdf-fecha'>
        Fecha de expedición:
        <strong>$fechaHoy</strong>
    </div>

    <div class='pdf-firma'>
        <div class='pdf-linea'></div>
        <strong>$responsable</strong><br>
        $empresa
    </div>

    <div class='pdf-pie'>
        Documento generado por la Plataforma de Transportistas
    </div>

</div>

</body>
</html>";


$options = new Options();
$options->set('defaultFont','DejaVu Sans');

$pdf = new Dompdf($options);
$pdf->loadHtml($html,'UTF-8');
$pdf->setPaper('letter','portrait');
$pdf->render();

$pdf->stream(
    "Constancia_Laboral_$id_reporte.pdf",
    ["Attachment"=>false]
);

exit;
?>