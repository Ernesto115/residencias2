<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   ELIMINACIÓN DE USUARIOS DESHABILITADA
   ========================================================= */

/*
 * Las cuentas de usuario forman parte de la administración
 * y trazabilidad del sistema.
 *
 * Ningún usuario puede eliminarse físicamente.
 *
 * Cuando una cuenta deje de utilizarse se deberá:
 *
 * - DESACTIVAR
 *
 * Y si posteriormente necesita volver a utilizarse:
 *
 * - REACTIVAR
 *
 * De esta manera conservamos:
 *
 * - La cuenta
 * - Su empresa
 * - Sus asociaciones multiempresa
 * - Su información
 */


/* =========================================================
   BLOQUEAR ELIMINACIÓN
   ========================================================= */

http_response_code(403);

header(
    'Content-Type: text/plain; charset=UTF-8'
);

echo
    'Los usuarios no pueden eliminarse. ' .
    'Utiliza la opción Desactivar para retirar su acceso al sistema.';

exit;

?>