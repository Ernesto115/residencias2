<?php
// 1. Iniciar o reanudar la sesión para tener acceso a ella
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Vaciar todas las variables almacenadas en $_SESSION
$_SESSION = array();

// 3. Eliminar la cookie de la sesión en el navegador por seguridad
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destruir los datos de la sesión en el servidor
session_destroy();

// 5. Redirigir al usuario AL FORMULARIO DE LOGIN (Saliendo de la carpeta autentificacion)
header("Location: ../login.php");
exit();
?>