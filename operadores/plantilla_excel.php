<?php

/* =========================================================
   PLANTILLA EXCEL - OPERADORES
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
        'No tienes permiso para descargar esta plantilla.'
    );
}


/* =========================================================
   CARGAR COMPOSER / PHPSPREADSHEET
   ========================================================= */

require_once dirname(__DIR__) . '/vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;


/* =========================================================
   CREAR EXCEL
   ========================================================= */

$spreadsheet = new Spreadsheet();

$hoja = $spreadsheet->getActiveSheet();

$hoja->setTitle('Operadores');


/* =========================================================
   ENCABEZADOS
   ========================================================= */

$hoja->setCellValue('A1', 'nombres');
$hoja->setCellValue('B1', 'primer_apellido');
$hoja->setCellValue('C1', 'segundo_apellido');
$hoja->setCellValue('D1', 'rfc');
$hoja->setCellValue('E1', 'fecha_ingreso');


/* =========================================================
   ESTILO DEL ENCABEZADO
   ========================================================= */

$hoja
    ->getStyle('A1:E1')
    ->getFont()
    ->setBold(true)
    ->getColor()
    ->setARGB('FFFFFFFF');


$hoja
    ->getStyle('A1:E1')
    ->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('FF1E3A5F');


$hoja
    ->getStyle('A1:E1')
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


/* =========================================================
   ANCHO DE COLUMNAS
   ========================================================= */

$hoja->getColumnDimension('A')->setWidth(25);
$hoja->getColumnDimension('B')->setWidth(25);
$hoja->getColumnDimension('C')->setWidth(25);
$hoja->getColumnDimension('D')->setWidth(20);
$hoja->getColumnDimension('E')->setWidth(18);


/* =========================================================
   FORMATO RFC COMO TEXTO
   ========================================================= */

$hoja
    ->getStyle('D2:D1000')
    ->getNumberFormat()
    ->setFormatCode('@');


/* =========================================================
   FORMATO FECHA
   ========================================================= */

$hoja
    ->getStyle('E2:E1000')
    ->getNumberFormat()
    ->setFormatCode('yyyy-mm-dd');


/* =========================================================
   CONGELAR ENCABEZADO
   ========================================================= */

$hoja->freezePane('A2');


/* =========================================================
   FILTRO
   ========================================================= */

$hoja->setAutoFilter('A1:E1');


/* =========================================================
   NOMBRE DEL ARCHIVO
   ========================================================= */

$nombreArchivo =
    'plantilla_operadores.xlsx';


/* =========================================================
   LIMPIAR SALIDA PREVIA

   IMPORTANTE:
   evita que warnings, espacios o HTML dañen el Excel
   ========================================================= */

while (ob_get_level() > 0) {
    ob_end_clean();
}


/* =========================================================
   HEADERS XLSX
   ========================================================= */

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

header(
    'Cache-Control: max-age=0'
);


/* =========================================================
   GENERAR ARCHIVO
   ========================================================= */

$writer =
    new Xlsx($spreadsheet);

$writer->save('php://output');


$spreadsheet->disconnectWorksheets();

unset($spreadsheet);

exit;