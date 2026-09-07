<?php

/* =========================================================
   PLANTILLA CSV - OPERADORES
   ========================================================= */

require_once "../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   VALIDAR ROL
   SOLO PROPIETARIO Y RRHH PUEDEN DESCARGARLA
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
   NOMBRE DEL ARCHIVO
   ========================================================= */

$nombreArchivo =
    'plantilla_operadores.csv';


/* =========================================================
   ENCABEZADOS DE DESCARGA
   ========================================================= */

header(
    'Content-Type: text/csv; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);


/* =========================================================
   BOM UTF-8
   AYUDA A EXCEL CON Ñ Y ACENTOS
   ========================================================= */

echo "\xEF\xBB\xBF";


/* =========================================================
   CREAR CSV
   ========================================================= */

$salida =
    fopen(
        'php://output',
        'w'
    );


/* =========================================================
   COLUMNAS DE LA PLANTILLA
   ========================================================= */

fputcsv(
    $salida,
    [
        'nombres',
        'primer_apellido',
        'segundo_apellido',
        'rfc',
        'fecha_ingreso'
    ]
);


fclose($salida);

exit;

?>