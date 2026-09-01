<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   ELIMINACIÓN DE REPORTES DE BAJA DESHABILITADA
   ========================================================= */

/*
 * Los reportes de baja forman parte del historial laboral
 * permanente del operador.
 *
 * Ningún usuario del sistema puede eliminarlos:
 *
 * - ADMIN
 * - PROPIETARIO
 * - RRHH
 *
 * Una baja completada conserva:
 *
 * - Empresa
 * - Operador
 * - Motivo
 * - Fecha de ingreso
 * - Fecha de baja
 * - Evaluaciones
 * - Constancia laboral
 *
 * Por seguridad e integridad del historial,
 * estos registros no deben eliminarse físicamente.
 */


/* =========================================================
   RESPUESTA
   ========================================================= */

http_response_code(403);

header('Content-Type: text/plain; charset=UTF-8');

echo 'Los reportes de baja no pueden eliminarse. Deben conservarse como parte del historial laboral del operador.';

exit;

?>