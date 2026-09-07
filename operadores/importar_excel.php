<?php

/* =========================================================
   IMPORTAR EXCEL - OPERADORES
   PRIMERA ETAPA: SOLO LEER Y MOSTRAR
   NO GUARDA NADA EN BASE DE DATOS
   ========================================================= */

require_once "../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   VALIDAR ROL
   SOLO PROPIETARIO Y RRHH
   ========================================================= */

$rol = strtoupper(
    trim($_SESSION['rol'] ?? '')
);

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (
    in_array(
        $rol,
        ['RH', 'RECURSOS HUMANOS'],
        true
    )
) {
    $rol = 'RRHH';
}

if (
    !in_array(
        $rol,
        ['PROPIETARIO', 'RRHH'],
        true
    )
) {

    http_response_code(403);

    exit(
        'No tienes permiso para importar operadores.'
    );
}


/* =========================================================
   CARGAR PHPSPREADSHEET
   ========================================================= */

require_once dirname(__DIR__) . '/vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;


/* =========================================================
   ESCAPAR HTML
   ========================================================= */

function hExcel($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   VARIABLES
   ========================================================= */

$error = '';

$filasExcel = [];


/* =========================================================
   SI RECIBIMOS UN ARCHIVO
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =====================================================
       COMPROBAR ARCHIVO
       ===================================================== */

    if (
        !isset($_FILES['archivo_excel']) ||
        $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK
    ) {

        $error =
            'No se recibió correctamente el archivo Excel.';

    } else {


        $archivo =
            $_FILES['archivo_excel'];


        $nombreOriginal =
            $archivo['name'] ?? '';


        $extension =
            strtolower(
                pathinfo(
                    $nombreOriginal,
                    PATHINFO_EXTENSION
                )
            );


        /* =================================================
           SOLO XLSX
           ================================================= */

        if ($extension !== 'xlsx') {

            $error =
                'El archivo debe estar en formato .xlsx';

        } elseif (
            ($archivo['size'] ?? 0) >
            5 * 1024 * 1024
        ) {

            $error =
                'El archivo no puede superar los 5 MB.';

        } else {


            try {


                /* =========================================
                   LEER EXCEL
                   ========================================= */

                $lector =
                    IOFactory::createReaderForFile(
                        $archivo['tmp_name']
                    );


                $lector->setReadDataOnly(true);


                $excel =
                    $lector->load(
                        $archivo['tmp_name']
                    );


                $hoja =
                    $excel->getActiveSheet();


                $ultimaFila =
                    $hoja->getHighestDataRow();


                /* =========================================
                   EVITAR ARCHIVOS DEMASIADO GRANDES
                   ========================================= */

                if ($ultimaFila > 1001) {

                    throw new Exception(
                        'La plantilla admite un máximo de 1000 operadores por archivo.'
                    );
                }


                /* =========================================
                   VALIDAR ENCABEZADOS
                   ========================================= */

                $encabezadosEsperados = [

                    'nombres',

                    'primer_apellido',

                    'segundo_apellido',

                    'rfc',

                    'fecha_ingreso'

                ];


                $encabezadosRecibidos = [];


                for ($columna = 1; $columna <= 5; $columna++) {

                    $valor =
                        $hoja
                            ->getCell([
                                $columna,
                                1
                            ])
                            ->getValue();


                    $encabezadosRecibidos[] =
                        strtolower(
                            trim(
                                (string)$valor
                            )
                        );
                }


                if (
                    $encabezadosRecibidos !==
                    $encabezadosEsperados
                ) {

                    throw new Exception(
                        'La estructura del Excel no corresponde con la plantilla oficial de operadores.'
                    );
                }


                /* =========================================
                   LEER FILAS
                   EMPIEZA EN FILA 2
                   ========================================= */

                for (
                    $fila = 2;
                    $fila <= $ultimaFila;
                    $fila++
                ) {


                    $nombres =
                        trim(
                            (string)$hoja
                                ->getCell("A$fila")
                                ->getValue()
                        );


                    $primerApellido =
                        trim(
                            (string)$hoja
                                ->getCell("B$fila")
                                ->getValue()
                        );


                    $segundoApellido =
                        trim(
                            (string)$hoja
                                ->getCell("C$fila")
                                ->getValue()
                        );


                    $rfc =
                        strtoupper(
                            trim(
                                (string)$hoja
                                    ->getCell("D$fila")
                                    ->getValue()
                            )
                        );


                    /* =====================================
                       FECHA
                       ===================================== */

                    $celdaFecha =
                        $hoja->getCell("E$fila");


                    $valorFecha =
                        $celdaFecha->getValue();


                    $fechaIngreso = '';


                    if (
                        $valorFecha !== null &&
                        $valorFecha !== ''
                    ) {


                        if (
                            is_numeric(
                                $valorFecha
                            )
                        ) {

                            $fechaIngreso =
                                Date::excelToDateTimeObject(
                                    $valorFecha
                                )->format(
                                    'Y-m-d'
                                );

                        } else {

                            $fechaIngreso =
                                trim(
                                    (string)$valorFecha
                                );
                        }
                    }


                    /* =====================================
                       IGNORAR FILAS COMPLETAMENTE VACÍAS
                       ===================================== */

                    if (
                        $nombres === '' &&
                        $primerApellido === '' &&
                        $segundoApellido === '' &&
                        $rfc === '' &&
                        $fechaIngreso === ''
                    ) {

                        continue;
                    }


                    /* =====================================
                       GUARDAR SOLO EN MEMORIA
                       NO EN MYSQL
                       ===================================== */

                    $filasExcel[] = [

                        'fila' =>
                            $fila,

                        'nombres' =>
                            $nombres,

                        'primer_apellido' =>
                            $primerApellido,

                        'segundo_apellido' =>
                            $segundoApellido,

                        'rfc' =>
                            $rfc,

                        'fecha_ingreso' =>
                            $fechaIngreso

                    ];
                }


                $excel->disconnectWorksheets();

                unset($excel);


            } catch (Throwable $e) {

                $error =
                    'No fue posible leer el archivo: ' .
                    $e->getMessage();
            }
        }
    }
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Importar Operadores
    </title>

    <style>

        body {
            margin:0;
            padding:25px;
            font-family:Arial, sans-serif;
            background:#0f172a;
            color:#ffffff;
        }

        .contenedor-importacion {
            max-width:1100px;
            margin:auto;
        }

        .tarjeta-importacion {
            background:#1e293b;
            border:1px solid #334155;
            border-radius:14px;
            padding:22px;
            margin-bottom:20px;
        }

        h2 {
            margin-top:0;
        }

        .descripcion {
            color:#a8b3c7;
            line-height:1.5;
        }

        input[type="file"] {
            display:block;
            width:100%;
            box-sizing:border-box;
            margin:18px 0;
            padding:14px;
            border:1px solid #475569;
            border-radius:9px;
            background:#0f172a;
            color:#ffffff;
        }

        .btn-importar {
            border:none;
            border-radius:9px;
            padding:12px 20px;
            cursor:pointer;
            font-weight:bold;
            background:#2563eb;
            color:#ffffff;
        }

        .btn-volver {
            display:inline-block;
            margin-left:8px;
            padding:12px 20px;
            border-radius:9px;
            background:#334155;
            color:#ffffff;
            text-decoration:none;
            font-weight:bold;
        }

        .mensaje-error {
            padding:14px;
            margin-bottom:18px;
            border-radius:9px;
            background:rgba(239,68,68,.15);
            border:1px solid rgba(239,68,68,.45);
            color:#fca5a5;
        }

        .mensaje-correcto {
            padding:14px;
            margin-bottom:18px;
            border-radius:9px;
            background:rgba(16,185,129,.15);
            border:1px solid rgba(16,185,129,.45);
            color:#6ee7b7;
        }

        .tabla-responsive {
            overflow-x:auto;
        }

        table {
            width:100%;
            border-collapse:collapse;
            min-width:850px;
        }

        th,
        td {
            padding:12px;
            border-bottom:1px solid #334155;
            text-align:left;
        }

        th {
            background:#334155;
        }

        .numero-fila {
            color:#94a3b8;
            font-weight:bold;
        }

    </style>

</head>


<body>


<div class="contenedor-importacion">


    <!-- =====================================================
         SELECCIONAR ARCHIVO
         ===================================================== -->

    <div class="tarjeta-importacion">


        <h2>
            📊 Importar Operadores desde Excel
        </h2>


        <p class="descripcion">

            Selecciona la plantilla oficial
            <strong>plantilla_operadores.xlsx</strong>.

            Por ahora el sistema únicamente leerá y mostrará
            los datos.

            <strong>
                Ningún operador será registrado todavía.
            </strong>

        </p>


        <?php if ($error !== ''): ?>


            <div class="mensaje-error">

                ❌ <?= hExcel($error) ?>

            </div>


        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <input
                type="file"
                name="archivo_excel"
                accept=".xlsx"
                required
            >


            <button
                type="submit"
                class="btn-importar"
            >

                👁️ Leer archivo

            </button>


            <a
                href="/index.php"
                class="btn-volver"
            >

                ← Volver al sistema

            </a>


        </form>


    </div>


    <!-- =====================================================
         VISTA PREVIA
         ===================================================== -->

    <?php if (
        $error === '' &&
        !empty($filasExcel)
    ): ?>


        <div class="tarjeta-importacion">


            <div class="mensaje-correcto">

                ✅ Archivo leído correctamente.

                Se encontraron

                <strong>
                    <?= count($filasExcel) ?>
                </strong>

                operador(es).

            </div>


            <h2>
                Vista previa
            </h2>


            <div class="tabla-responsive">


                <table>


                    <thead>


                        <tr>

                            <th>
                                Fila
                            </th>

                            <th>
                                Nombres
                            </th>

                            <th>
                                Primer apellido
                            </th>

                            <th>
                                Segundo apellido
                            </th>

                            <th>
                                RFC
                            </th>

                            <th>
                                Fecha ingreso
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                    <?php foreach (
                        $filasExcel as $dato
                    ): ?>


                        <tr>


                            <td class="numero-fila">

                                <?= (int)$dato['fila'] ?>

                            </td>


                            <td>

                                <?= hExcel(
                                    $dato['nombres']
                                ) ?>

                            </td>


                            <td>

                                <?= hExcel(
                                    $dato[
                                        'primer_apellido'
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= hExcel(
                                    $dato[
                                        'segundo_apellido'
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= hExcel(
                                    $dato['rfc']
                                ) ?>

                            </td>


                            <td>

                                <?= hExcel(
                                    $dato[
                                        'fecha_ingreso'
                                    ]
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


            <p class="descripcion">

                ⚠️ Esta es únicamente una vista previa.

                No existe ningún INSERT ni UPDATE
                en esta etapa.

            </p>


        </div>


    <?php endif; ?>


</div>


</body>

</html>