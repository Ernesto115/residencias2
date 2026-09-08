<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;


/* =========================================================
   IMPORTAR EXCEL - OPERADORES
   VALIDAR + CONFIRMAR IMPORTACIÓN
   ========================================================= */

require_once "../configuracion/sesion.php";
require_once dirname(__DIR__) . '/vendor/autoload.php';

verificarSesion();


/* =========================================================
   ROL Y EMPRESA DE SESIÓN
   ========================================================= */

$rol = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

if (in_array($rol, ['RH', 'RECURSOS HUMANOS'], true)) {
    $rol = 'RRHH';
}

if (!in_array($rol, ['PROPIETARIO', 'RRHH'], true)) {
    http_response_code(403);
    exit('No tienes permiso para importar operadores.');
}

$idEmpresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);


/* =========================================================
   TOKEN DE SEGURIDAD
   ========================================================= */

if (empty($_SESSION['csrf_import_operadores'])) {
    $_SESSION['csrf_import_operadores'] =
        bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_import_operadores'];


/* =========================================================
   FUNCIONES
   ========================================================= */

function hExcel($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function largoExcel($valor)
{
    return function_exists('mb_strlen')
        ? mb_strlen((string)$valor, 'UTF-8')
        : strlen((string)$valor);
}


function fechaValidaExcel($fecha)
{
    $f = DateTime::createFromFormat('Y-m-d', $fecha);

    return $f &&
           $f->format('Y-m-d') === $fecha;
}


/* =========================================================
   VARIABLES
   ========================================================= */

$error = '';
$mensajeExito = '';

$filasExcel = [];

$procesado = false;

$totalValidas = 0;
$totalErrores = 0;
$totalAvisos = 0;

$insertados = 0;
$omitidos = 0;


/* =========================================================
   PROCESAR POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfPost = $_POST['csrf'] ?? '';

    if (!hash_equals($csrf, $csrfPost)) {

        $error =
            'La solicitud no es válida. Recarga la página e inténtalo nuevamente.';

    } else {

        $accion =
            $_POST['accion'] ?? 'validar';


        /* =====================================================
           CONFIRMAR IMPORTACIÓN
           ===================================================== */

        if ($accion === 'confirmar') {

            $pendientes =
                $_SESSION[
                    'importacion_operadores_validos'
                ] ?? [];

            $empresaGuardada =
                (int)(
                    $_SESSION[
                        'importacion_operadores_empresa'
                    ] ?? 0
                );


            if ($multiempresa === 1) {

                $error =
                    'La importación para propietario multiempresa se habilitará en el siguiente paso.';

            } elseif ($idEmpresa <= 0) {

                $error =
                    'Tu usuario no tiene una empresa asignada.';

            } elseif (
                $empresaGuardada !== $idEmpresa
            ) {

                $error =
                    'La empresa de la importación ya no coincide con tu sesión.';

            } elseif (empty($pendientes)) {

                $error =
                    'No hay operadores pendientes para importar.';

            } else {

                include_once "../db/db.php";

                $dbImportacion = new db();

                try {

                    $dbImportacion->conectar();

                    $pdo =
                        $dbImportacion->conn;

                    $pdo->beginTransaction();


                    /* =========================================
                       REVISAR RFC NUEVAMENTE
                       ========================================= */

                    $buscar = $pdo->prepare(
                        "SELECT id_operador
                         FROM operadores
                         WHERE UPPER(rfc) = ?
                         LIMIT 1"
                    );


                    /* =========================================
                       INSERT
                       ========================================= */

                    $insertar = $pdo->prepare(
                        "INSERT INTO operadores (
                            estatus,
                            id_empresa,
                            fecha_ingreso,
                            rfc,
                            nombres,
                            primer_apellido,
                            segundo_apellido
                        )
                        VALUES (
                            1,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )"
                    );


                    foreach ($pendientes as $dato) {

                        /*
                         * Segunda comprobación justo
                         * antes de insertar.
                         */
                        $buscar->execute([
                            $dato['rfc']
                        ]);


                        if ($buscar->fetch()) {

                            $omitidos++;

                            continue;
                        }


                        $insertar->execute([

                            $idEmpresa,

                            $dato[
                                'fecha_ingreso'
                            ],

                            $dato[
                                'rfc'
                            ],

                            $dato[
                                'nombres'
                            ],

                            $dato[
                                'primer_apellido'
                            ],

                            $dato[
                                'segundo_apellido'
                            ]

                        ]);


                        $insertados++;
                    }


                    $pdo->commit();


                    /* =========================================
                       LIMPIAR IMPORTACIÓN PENDIENTE
                       ========================================= */

                    unset(
                        $_SESSION[
                            'importacion_operadores_validos'
                        ],
                        $_SESSION[
                            'importacion_operadores_empresa'
                        ]
                    );


                    $mensajeExito =
                        "Importación completada. " .
                        "$insertados operador(es) registrado(s).";


                    if ($omitidos > 0) {

                        $mensajeExito .=
                            " $omitidos operador(es) fueron omitidos porque su RFC ya existía.";
                    }


                } catch (Throwable $e) {

                    if (
                        isset($pdo) &&
                        $pdo->inTransaction()
                    ) {

                        $pdo->rollBack();
                    }


                    error_log(
                        'Importación operadores: ' .
                        $e->getMessage()
                    );


                    $error =
                        'No fue posible completar la importación. No se guardaron cambios.';

                } finally {

                    if (isset($dbImportacion)) {
                        $dbImportacion->desconectar();
                    }
                }
            }


        /* =====================================================
           VALIDAR EXCEL
           ===================================================== */

        } else {


            /*
             * Una nueva validación reemplaza
             * cualquier importación anterior.
             */

            unset(
                $_SESSION[
                    'importacion_operadores_validos'
                ],
                $_SESSION[
                    'importacion_operadores_empresa'
                ]
            );


            if (
                !isset($_FILES['archivo_excel']) ||
                $_FILES['archivo_excel']['error']
                    !== UPLOAD_ERR_OK
            ) {

                $error =
                    'No se recibió correctamente el archivo Excel.';

            } else {

                $archivo =
                    $_FILES['archivo_excel'];


                $extension =
                    strtolower(
                        pathinfo(
                            $archivo['name'] ?? '',
                            PATHINFO_EXTENSION
                        )
                    );


                if ($extension !== 'xlsx') {

                    $error =
                        'El archivo debe estar en formato .xlsx.';

                } elseif (
                    ($archivo['size'] ?? 0)
                    > 5 * 1024 * 1024
                ) {

                    $error =
                        'El archivo no puede superar los 5 MB.';

                } else {

                    try {


                        /* =====================================
                           LEER EXCEL
                           ===================================== */

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


                        if ($ultimaFila > 1001) {

                            throw new Exception(
                                'Máximo 1000 operadores por archivo.'
                            );
                        }


                        /* =====================================
                           ENCABEZADOS
                           ===================================== */

                        $esperados = [
                            'nombres',
                            'primer_apellido',
                            'segundo_apellido',
                            'rfc',
                            'fecha_ingreso'
                        ];


                        $encabezados =
                            $hoja->rangeToArray(
                                'A1:E1',
                                null,
                                true,
                                false,
                                false
                            )[0];


                        $encabezados =
                            array_map(
                                fn($v) =>
                                    strtolower(
                                        trim((string)$v)
                                    ),
                                $encabezados
                            );


                        if (
                            $encabezados !==
                            $esperados
                        ) {

                            throw new Exception(
                                'El archivo no corresponde con la plantilla oficial.'
                            );
                        }


                        /* =====================================
                           LEER FILAS
                           ===================================== */

                        for (
                            $fila = 2;
                            $fila <= $ultimaFila;
                            $fila++
                        ) {

                            $datos =
                                $hoja->rangeToArray(
                                    "A$fila:E$fila",
                                    null,
                                    true,
                                    false,
                                    false
                                )[0];


                            $nombres =
                                trim(
                                    (string)$datos[0]
                                );

                            $primerApellido =
                                trim(
                                    (string)$datos[1]
                                );

                            $segundoApellido =
                                trim(
                                    (string)$datos[2]
                                );

                            $rfc =
                                strtoupper(
                                    trim(
                                        (string)$datos[3]
                                    )
                                );


                            $valorFecha =
                                $datos[4];

                            $fechaIngreso = '';


                            if (
                                $valorFecha !== null &&
                                $valorFecha !== ''
                            ) {

                                $fechaIngreso =
                                    is_numeric(
                                        $valorFecha
                                    )
                                    ? Date::
                                        excelToDateTimeObject(
                                            $valorFecha
                                        )->format('Y-m-d')
                                    : trim(
                                        (string)$valorFecha
                                    );
                            }


                            /*
                             * Ignorar fila vacía.
                             */

                            if (
                                $nombres === '' &&
                                $primerApellido === '' &&
                                $segundoApellido === '' &&
                                $rfc === '' &&
                                $fechaIngreso === ''
                            ) {

                                continue;
                            }


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
                                    $fechaIngreso,

                                'estado' =>
                                    '',

                                'mensajes' =>
                                    []

                            ];
                        }


                        $excel
                            ->disconnectWorksheets();

                        unset($excel);


                        /* =====================================
                           RFC REPETIDOS EN EXCEL
                           ===================================== */

                        $rfcs =
                            array_filter(
                                array_column(
                                    $filasExcel,
                                    'rfc'
                                )
                            );


                        $conteoRfc =
                            array_count_values(
                                $rfcs
                            );


                        /* =====================================
                           RFC EXISTENTES EN MYSQL
                           ===================================== */

                        $existentes = [];

                        $rfcsUnicos =
                            array_values(
                                array_unique(
                                    $rfcs
                                )
                            );


                        if (!empty($rfcsUnicos)) {

                            include_once "../db/db.php";

                            $dbExcel =
                                new db();

                            $dbExcel->conectar();


                            $signos =
                                implode(
                                    ',',
                                    array_fill(
                                        0,
                                        count(
                                            $rfcsUnicos
                                        ),
                                        '?'
                                    )
                                );


                            $stmt =
                                $dbExcel
                                    ->conn
                                    ->prepare(
                                        "SELECT
                                            rfc,
                                            estatus
                                         FROM operadores
                                         WHERE UPPER(rfc)
                                         IN ($signos)"
                                    );


                            $stmt->execute(
                                $rfcsUnicos
                            );


                            foreach (
                                $stmt->fetchAll(
                                    PDO::FETCH_ASSOC
                                )
                                as $registro
                            ) {

                                $existentes[
                                    strtoupper(
                                        $registro['rfc']
                                    )
                                ] =
                                    (int)$registro[
                                        'estatus'
                                    ];
                            }


                            $dbExcel
                                ->desconectar();
                        }


                        /* =====================================
                           VALIDAR FILAS
                           ===================================== */

                        foreach (
                            $filasExcel
                            as &$dato
                        ) {

                            $errores = [];
                            $avisos = [];


                            /* OBLIGATORIOS */

                            if (
                                $dato['nombres']
                                === ''
                            ) {
                                $errores[] =
                                    'Nombres obligatorio.';
                            }

                            if (
                                $dato[
                                    'primer_apellido'
                                ] === ''
                            ) {
                                $errores[] =
                                    'Primer apellido obligatorio.';
                            }

                            if (
                                $dato[
                                    'segundo_apellido'
                                ] === ''
                            ) {
                                $errores[] =
                                    'Segundo apellido obligatorio.';
                            }

                            if (
                                $dato['rfc']
                                === ''
                            ) {
                                $errores[] =
                                    'RFC obligatorio.';
                            }

                            if (
                                $dato[
                                    'fecha_ingreso'
                                ] === ''
                            ) {
                                $errores[] =
                                    'Fecha de ingreso obligatoria.';
                            }


                            /* LONGITUD */

                            if (
                                largoExcel(
                                    $dato[
                                        'nombres'
                                    ]
                                ) > 30
                            ) {
                                $errores[] =
                                    'Nombres supera 30 caracteres.';
                            }

                            if (
                                largoExcel(
                                    $dato[
                                        'primer_apellido'
                                    ]
                                ) > 30
                            ) {
                                $errores[] =
                                    'Primer apellido supera 30 caracteres.';
                            }

                            if (
                                largoExcel(
                                    $dato[
                                        'segundo_apellido'
                                    ]
                                ) > 30
                            ) {
                                $errores[] =
                                    'Segundo apellido supera 30 caracteres.';
                            }

                            if (
                                largoExcel(
                                    $dato['rfc']
                                ) > 13
                            ) {
                                $errores[] =
                                    'RFC supera 13 caracteres.';
                            }


                            /* RFC DUPLICADO */

                            if (
                                $dato['rfc'] !== '' &&
                                (
                                    $conteoRfc[
                                        $dato['rfc']
                                    ] ?? 0
                                ) > 1
                            ) {

                                $errores[] =
                                    'RFC repetido dentro del Excel.';
                            }


                            /* RFC EXISTENTE */

                            if (
                                $dato['rfc'] !== '' &&
                                isset(
                                    $existentes[
                                        $dato['rfc']
                                    ]
                                )
                            ) {

                                if (
                                    $existentes[
                                        $dato['rfc']
                                    ] === 1
                                ) {

                                    $errores[] =
                                        'RFC ya pertenece a un operador activo.';

                                } else {

                                    $avisos[] =
                                        'Operador inactivo: debe utilizar recontratación.';
                                }
                            }


                            /* FECHA */

                            if (
                                $dato[
                                    'fecha_ingreso'
                                ] !== '' &&
                                !fechaValidaExcel(
                                    $dato[
                                        'fecha_ingreso'
                                    ]
                                )
                            ) {

                                $errores[] =
                                    'Fecha inválida. Usa AAAA-MM-DD.';
                            }


                            /* RESULTADO */

                            if (!empty($errores)) {

                                $dato[
                                    'estado'
                                ] = 'error';

                                $dato[
                                    'mensajes'
                                ] =
                                    array_merge(
                                        $errores,
                                        $avisos
                                    );

                                $totalErrores++;

                            } elseif (
                                !empty($avisos)
                            ) {

                                $dato[
                                    'estado'
                                ] = 'aviso';

                                $dato[
                                    'mensajes'
                                ] = $avisos;

                                $totalAvisos++;

                            } else {

                                $dato[
                                    'estado'
                                ] = 'ok';

                                $dato[
                                    'mensajes'
                                ] = [
                                    'Lista para importar.'
                                ];

                                $totalValidas++;
                            }
                        }


                        unset($dato);

                        $procesado = true;


                        /* =====================================
                           GUARDAR SOLO FILAS VÁLIDAS
                           EN SESIÓN
                           ===================================== */

                        if (
                            $totalValidas > 0 &&
                            $multiempresa !== 1 &&
                            $idEmpresa > 0
                        ) {

                            $_SESSION[
                                'importacion_operadores_validos'
                            ] =
                                array_values(
                                    array_filter(
                                        $filasExcel,
                                        fn($dato) =>
                                            $dato[
                                                'estado'
                                            ] === 'ok'
                                    )
                                );


                            $_SESSION[
                                'importacion_operadores_empresa'
                            ] =
                                $idEmpresa;
                        }


                    } catch (Throwable $e) {

                        error_log(
                            'Excel operadores: ' .
                            $e->getMessage()
                        );

                        $error =
                            'No fue posible procesar el archivo: ' .
                            $e->getMessage();
                    }
                }
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

    <link
        rel="stylesheet"
        href="../CSS/styles.css"
    >

</head>


<body class="importar-excel-page">


<div class="importar-excel-contenedor">


    <!-- =====================================================
         ARCHIVO
         ===================================================== -->

    <div class="importar-excel-card">


        <h2>
            📊 Importar Operadores desde Excel
        </h2>


        <p class="importar-excel-descripcion">

            Selecciona la plantilla oficial.

            El sistema validará los operadores
            antes de registrarlos.

        </p>


        <?php if ($error !== ''): ?>

            <div class="importar-excel-error">

                ❌ <?= hExcel($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($mensajeExito !== ''): ?>

            <div class="importar-excel-aviso">

                ✅ <?= hExcel($mensajeExito) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="csrf"
                value="<?= hExcel($csrf) ?>"
            >


            <input
                type="hidden"
                name="accion"
                value="validar"
            >


            <input
                type="file"
                name="archivo_excel"
                accept=".xlsx"
                required
            >


            <div class="importar-excel-botones">


                <button
                    type="submit"
                    class="btn-action btn-info"
                >
                    🔍 Validar archivo
                </button>


                <a
                    href="/index.php"
                    class="btn-action importar-excel-volver"
                >
                    ← Volver
                </a>


            </div>


        </form>


    </div>


    <!-- =====================================================
         RESULTADOS
         ===================================================== -->

    <?php if (
        $error === '' &&
        $procesado
    ): ?>


        <div class="importar-excel-card">


            <h2>
                Resultado de la validación
            </h2>


            <div class="importar-excel-resumen">


                <div>
                    ✅ <?= $totalValidas ?> listos
                </div>


                <div>
                    ❌ <?= $totalErrores ?> con errores
                </div>


                <div>
                    ⚠️ <?= $totalAvisos ?> requieren revisión
                </div>


            </div>


            <?php if (!empty($filasExcel)): ?>


                <div class="importar-excel-tabla">


                    <table>


                        <thead>

                            <tr>

                                <th>Fila</th>
                                <th>Nombres</th>
                                <th>Primer apellido</th>
                                <th>Segundo apellido</th>
                                <th>RFC</th>
                                <th>Fecha ingreso</th>
                                <th>Resultado</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $filasExcel
                            as $dato
                        ): ?>


                            <tr>


                                <td>
                                    <?= (int)$dato['fila'] ?>
                                </td>


                                <td>
                                    <?= hExcel($dato['nombres']) ?>
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
                                    <?= hExcel($dato['rfc']) ?>
                                </td>


                                <td>
                                    <?= hExcel(
                                        $dato[
                                            'fecha_ingreso'
                                        ]
                                    ) ?>
                                </td>


                                <td>


                                    <span
                                        class="estado-<?= hExcel(
                                            $dato['estado']
                                        ) ?>"
                                    >


                                        <?php if (
                                            $dato['estado']
                                            === 'ok'
                                        ): ?>

                                            ✅ LISTO

                                        <?php elseif (
                                            $dato['estado']
                                            === 'aviso'
                                        ): ?>

                                            ⚠️ REVISAR

                                        <?php else: ?>

                                            ❌ ERROR

                                        <?php endif; ?>


                                    </span>


                                    <?php foreach (
                                        $dato[
                                            'mensajes'
                                        ]
                                        as $mensaje
                                    ): ?>

                                        <div
                                            class="importar-excel-mensaje"
                                        >

                                            <?= hExcel(
                                                $mensaje
                                            ) ?>

                                        </div>

                                    <?php endforeach; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


            <!-- =================================================
                 CONFIRMAR IMPORTACIÓN
                 ================================================= -->

            <?php if (
                $totalValidas > 0 &&
                $multiempresa !== 1 &&
                $idEmpresa > 0
            ): ?>


                <form
                    method="POST"
                    style="margin-top:20px;"
                >


                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= hExcel($csrf) ?>"
                    >


                    <input
                        type="hidden"
                        name="accion"
                        value="confirmar"
                    >


                    <button
                        type="submit"
                        class="btn-action btn-info"
                    >

                        ✅ Confirmar importación
                        (<?= $totalValidas ?>)

                    </button>


                </form>


            <?php elseif (
                $multiempresa === 1
            ): ?>


                <div
                    class="importar-excel-aviso"
                    style="margin-top:20px;"
                >

                    ℹ️ Este propietario administra varias
                    empresas. La selección de empresa destino
                    será el siguiente paso.

                </div>


            <?php endif; ?>


            <div
                class="importar-excel-aviso"
                style="margin-top:20px;"
            >

                ℹ️ Solo los registros marcados como
                <strong>LISTO</strong>
                serán importados.

            </div>


        </div>


    <?php endif; ?>


</div>


</body>

</html>