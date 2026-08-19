<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Verificar que el usuario haya iniciado sesión
|--------------------------------------------------------------------------
*/
function verificarSesion()
{
    if (!isset($_SESSION['id_usuario'])) {
        // Redirige al login si no existe una sesión activa
        header("Location: /index.php"); // Cambia a tu ruta de login (ej. /login.php)
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| Verificar rol y permisos del módulo
|--------------------------------------------------------------------------
*/
function verificarRol($rolesPermitidos = [])
{
    // 1. Confirmar autenticación
    verificarSesion();

    // Permitir pasar un string individual ej: verificarRol('ADMIN');
    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }

    // Convertir todos los roles de la lista a mayúsculas
    $rolesPermitidosUpper = array_map('strtoupper', $rolesPermitidos);

    // Obtener el rol actual guardado en la sesión
    $rolUsuario = strtoupper($_SESSION['rol'] ?? '');

    // 2. Si se definieron roles y el usuario NO tiene uno de ellos, bloqueamos el acceso
    if (!empty($rolesPermitidosUpper) && !in_array($rolUsuario, $rolesPermitidosUpper)) {
        http_response_code(403);

        echo "
        <div style='
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        '>
            <h1 style='font-size: 60px; color: #dc3545; margin-bottom: 0;'>403</h1>
            <h2 style='color: #333;'>Acceso Denegado</h2>
            <p style='color: #666;'>No tienes permisos para acceder a este módulo.</p>
            <a href='javascript:history.back()' style='
                display: inline-block;
                margin-top: 15px;
                padding: 10px 20px;
                background-color: #0d6efd;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
            '>⬅️ Volver a la página anterior</a>
        </div>";

        exit();
    }
}