<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   ELIMINACIÓN DE OPERADORES DESHABILITADA
   ========================================================= */

/*
 * Los operadores forman parte del historial laboral
 * del sistema.
 *
 * No deben eliminarse físicamente de la base de datos.
 *
 * Cuando un operador deja una empresa debe utilizarse:
 *
 *      REPORTE DE BAJA
 *
 * Una vez completada la baja:
 *
 *      ACTIVO → INACTIVO
 *
 * Posteriormente podrá ser recontratado por otra empresa
 * conservando su historial anterior.
 */


/* =========================================================
   RESPUESTA
   ========================================================= */

http_response_code(403);

header(
    'Content-Type: text/plain; charset=UTF-8'
);

echo 'Los operadores no pueden eliminarse. Utiliza el proceso de baja para conservar su historial laboral.';

exit;

?>