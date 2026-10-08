<?php

/* =========================================================
   SESIÓN SEGURA
   ========================================================= */

require_once "../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   DEPENDENCIAS
   ========================================================= */

require_once "../DB/db.php";
require_once "../vendor/autoload.php";


use Dompdf\Dompdf;
use Dompdf\Options;


/* =========================================================
   DATOS DE SESIÓN
   ========================================================= */

$rol = strtoupper(
    trim(
        $_SESSION['rol'] ?? ''
    )
);


if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}


$id_usuario =
    (int)(
        $_SESSION['id_usuario'] ?? 0
    );


$id_empresa_sesion =
    (int)(
        $_SESSION['id_empresa'] ?? 0
    );


$multiempresa =
    (int)(
        $_SESSION['multiempresa'] ?? 0
    );


$id_reporte =
    (int)(
        $_GET['id'] ?? 0
    );


/* =========================================================
   SOLO ADMIN / PROPIETARIO
   ========================================================= */

if (
    !in_array(
        $rol,
        ['ADMIN', 'PROPIETARIO'],
        true
    )
) {

    die(
        'No tienes permiso para consultar esta constancia.'
    );
}


if ($id_reporte <= 0) {

    die(
        'Reporte no válido.'
    );
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

$db = new db();

$db->conectar();


/* =========================================================
   OBTENER REPORTE HISTÓRICO
   ========================================================= */

$stmt =
    $db->conn->prepare(
        "SELECT

            rb.id_empresa,

            rb.fecha_ingreso,
            rb.fecha_baja,
            rb.fecha_expedicion_constancia,

            rb.estatus_evaluacion,


            rb.nombre_operador_historico,
            rb.rfc_operador_historico,


            rb.nombre_empresa_historico,
            rb.razon_social_historica,
            rb.direccion_fiscal_historica,
            rb.responsable_empresa_historico


         FROM reportes_baja rb


         WHERE rb.id_reporte = :id


         LIMIT 1"
    );


$stmt->execute([
    ':id' => $id_reporte
]);


$datos =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   REPORTE EXISTENTE
   ========================================================= */

if (!$datos) {

    die(
        'No se encontró el reporte.'
    );
}


/* =========================================================
   SOLO BAJAS COMPLETADAS
   ========================================================= */

if (
    strtoupper(
        $datos['estatus_evaluacion']
    ) !== 'COMPLETADA'
) {

    die(
        'La baja todavía no ha sido completada.'
    );
}


/* =========================================================
   PERMISO DEL PROPIETARIO
   ========================================================= */

if ($rol === 'PROPIETARIO') {

    $id_empresa =
        (int)$datos['id_empresa'];


    /* =====================================================
       PROPIETARIO MULTIEMPRESA
       ===================================================== */

    if ($multiempresa === 1) {

        $stmt =
            $db->conn->prepare(
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

            die(
                'No tienes permiso para consultar esta constancia.'
            );
        }


    /* =====================================================
       PROPIETARIO DE UNA EMPRESA
       ===================================================== */

    } elseif (
        $id_empresa_sesion <= 0 ||
        $id_empresa_sesion !== $id_empresa
    ) {

        die(
            'No tienes permiso para consultar esta constancia.'
        );
    }
}


/* =========================================================
   COMPROBAR SNAPSHOT HISTÓRICO
   ========================================================= */

$campos_historicos = [

    'nombre_operador_historico',

    'rfc_operador_historico',

    'nombre_empresa_historico',

    'razon_social_historica',

    'direccion_fiscal_historica',

    'responsable_empresa_historico'

];


foreach (
    $campos_historicos as $campo
) {

    if (
        !isset($datos[$campo]) ||
        trim(
            (string)$datos[$campo]
        ) === ''
    ) {

        die(
            'La información histórica de esta constancia está incompleta.'
        );
    }
}


/* =========================================================
   FIJAR FECHA DE EXPEDICIÓN
   ========================================================= */

/*
 * La fecha solamente se guarda si la constancia nunca
 * había sido expedida anteriormente.
 *
 * Una vez almacenada, futuras aperturas no la modifican.
 */

if (
    empty(
        $datos['fecha_expedicion_constancia']
    )
) {

    $stmt =
        $db->conn->prepare(
            "UPDATE reportes_baja

             SET fecha_expedicion_constancia = CURDATE()

             WHERE id_reporte = :id

             AND fecha_expedicion_constancia IS NULL"
        );


    $stmt->execute([
        ':id' => $id_reporte
    ]);


    /* =====================================================
       RECUPERAR FECHA GUARDADA
       ===================================================== */

    $stmt =
        $db->conn->prepare(
            "SELECT fecha_expedicion_constancia

             FROM reportes_baja

             WHERE id_reporte = :id

             LIMIT 1"
        );


    $stmt->execute([
        ':id' => $id_reporte
    ]);


    $fecha_expedicion_guardada =
        $stmt->fetchColumn();


    if (
        !$fecha_expedicion_guardada
    ) {

        die(
            'No fue posible registrar la fecha de expedición de la constancia.'
        );
    }


    $datos['fecha_expedicion_constancia'] =
        $fecha_expedicion_guardada;
}


/* =========================================================
   FUNCIONES
   ========================================================= */

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
        ? date(
            'd/m/Y',
            strtotime($fecha)
        )
        : 'No disponible';
}


/* =========================================================
   DATOS DE LA CONSTANCIA
   ========================================================= */

$operador =
    limpiar(
        $datos[
            'nombre_operador_historico'
        ]
    );


$rfc =
    limpiar(
        $datos[
            'rfc_operador_historico'
        ]
    );


$empresa =
    limpiar(
        $datos[
            'nombre_empresa_historico'
        ]
    );


$razon =
    limpiar(
        $datos[
            'razon_social_historica'
        ]
    );


$direccion =
    limpiar(
        $datos[
            'direccion_fiscal_historica'
        ]
    );


$responsable =
    limpiar(
        $datos[
            'responsable_empresa_historico'
        ]
    );


$fechaIngreso =
    fechaBonita(
        $datos['fecha_ingreso']
    );


$fechaBaja =
    fechaBonita(
        $datos['fecha_baja']
    );


/* =========================================================
   FECHA FIJA DE EXPEDICIÓN
   ========================================================= */

$fechaExpedicion =
    fechaBonita(
        $datos['fecha_expedicion_constancia']
    );


/* =========================================================
   CSS
   ========================================================= */

$rutaCSS =
    "../css/constancia_pdf.css";


$css =
    file_exists($rutaCSS)
        ? file_get_contents($rutaCSS)
        : '';


/* =========================================================
   CONTENIDO PDF
   ========================================================= */

$html = "
<html>

<head>

<meta charset='UTF-8'>

<style>
$css
</style>

</head>


<body class='pdf-body'>


<div class='pdf-marco'>


    <!-- ================================================
         ENCABEZADO
         ================================================ -->

    <div class='pdf-encabezado'>


        <div class='pdf-linea-titulo'></div>


        <div class='pdf-titulo'>
            CONSTANCIA LABORAL
        </div>


        <div class='pdf-linea-titulo'></div>


        <div class='pdf-datos-empresa'>


            <div class='pdf-empresa'>
                $empresa
            </div>


            <div class='pdf-razon-social'>
                $razon
            </div>


            <div class='pdf-direccion'>
                $direccion
            </div>


        </div>


    </div>


    <!-- ================================================
         INFORMACIÓN LABORAL
         ================================================ -->

    <div class='pdf-texto'>


        <p class='pdf-introduccion'>

            Por medio de la presente, se hace constar que:

        </p>


        <div class='pdf-datos-laborales'>


            <div class='pdf-nombre-operador'>
                $operador
            </div>


            <div class='pdf-rfc-operador'>

                RFC:

                <strong>
                    $rfc
                </strong>

            </div>


            <div class='pdf-separador-datos'></div>


            <div class='pdf-dato'>

                <span>
                    Empresa:
                </span>

                <strong>
                    $empresa
                </strong>

            </div>


            <div class='pdf-dato'>

                <span>
                    Periodo laboral:
                </span>

                <strong>
                    $fechaIngreso al $fechaBaja
                </strong>

            </div>


        </div>


        <p class='pdf-cierre'>

            Se expide la presente constancia para los fines
            que al interesado convengan.

        </p>


    </div>


    <!-- ================================================
         FECHA DE EXPEDICIÓN
         ================================================ -->

    <div class='pdf-fecha'>

        Fecha de expedición:

        <strong>
            $fechaExpedicion
        </strong>

    </div>


    <!-- ================================================
         FIRMA
         ================================================ -->

    <div class='pdf-firma'>

        <div class='pdf-linea'></div>

        <strong>
            $responsable
        </strong>

        <br>

        <span class='pdf-firma-empresa'>
            $empresa
        </span>

    </div>


    <!-- ================================================
         PIE
         ================================================ -->

    <div class='pdf-pie'>

        Documento generado por el Sistema Integral del Consejo Binacional
                            de Transportistas

    </div>


</div>


</body>

</html>
";


/* =========================================================
   GENERAR PDF
   ========================================================= */

$options =
    new Options();


$options->set(
    'defaultFont',
    'DejaVu Sans'
);


$pdf =
    new Dompdf(
        $options
    );


$pdf->loadHtml(
    $html,
    'UTF-8'
);


$pdf->setPaper(
    'letter',
    'portrait'
);


$pdf->render();


$pdf->stream(
    "Constancia_Laboral_$id_reporte.pdf",
    [
        "Attachment" => false
    ]
);


exit;

?>