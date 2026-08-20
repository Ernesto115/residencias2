<?php

/* =========================================================
   VALIDACIÓN DE USUARIO E INICIO DE SESIÓN
   ========================================================= */

ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

try {

    /* 1. CONEXIÓN */
    require_once "../DB/db.php";

    $dbtransportistas = new db();
    $dbtransportistas->conectar();


    /* 2. DATOS DEL LOGIN */
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        echo json_encode([
            "status" => "error",
            "message" => "Debes ingresar usuario y contraseña."
        ]);
        exit;
    }


    /* 3. BUSCAR USUARIO */
    $sql = "SELECT * FROM usuarios WHERE nombre_usuario = :usuario LIMIT 1";

    $stmt = $dbtransportistas->conn->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioDB || $clave !== $usuarioDB['contrasena']) {
        echo json_encode([
            "status" => "error",
            "message" => "Usuario o contraseña incorrectos."
        ]);
        exit;
    }


    /* 4. ROL */
    $rolNormalizado = strtoupper(trim($usuarioDB['rol']));

    if ($rolNormalizado === 'ADMINISTRADOR') {
        $rolNormalizado = 'ADMIN';
    }

    if (!in_array($rolNormalizado, ['ADMIN', 'PROPIETARIO', 'RRHH'], true)) {
        echo json_encode([
            "status" => "error",
            "message" => "Tu rol no tiene acceso asignado a este sistema."
        ]);
        exit;
    }


    /* 5. NOMBRE COMPLETO */
    $nombreCompleto = trim(
        ($usuarioDB['nombres'] ?? '') . ' ' .
        ($usuarioDB['primer_apellido'] ?? '') . ' ' .
        ($usuarioDB['segundo_apellido'] ?? '')
    );

    // Si todavía no tiene nombre registrado, mostrar el usuario.
    if ($nombreCompleto === '') {
        $nombreCompleto = $usuarioDB['nombre_usuario'];
    }


    /* 6. CREAR SESIÓN */
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int)$usuarioDB['id_usuario'];
    $_SESSION['nombre_usuario'] = $usuarioDB['nombre_usuario'];
    $_SESSION['nombre_completo'] = $nombreCompleto;
    $_SESSION['rol'] = $rolNormalizado;

    $_SESSION['id_empresa'] = !empty($usuarioDB['id_empresa'])
        ? (int)$usuarioDB['id_empresa']
        : null;

    $_SESSION['multiempresa'] = isset($usuarioDB['multiempresa'])
        ? (int)$usuarioDB['multiempresa']
        : 0;


    /* 7. RESPUESTA */
    echo json_encode([
        "status" => "success",
        "message" => "Inicio de sesión correcto.",
        "nombre_completo" => $_SESSION['nombre_completo'],
        "rol" => $_SESSION['rol'],
        "id_empresa" => $_SESSION['id_empresa'],
        "multiempresa" => $_SESSION['multiempresa']
    ]);

    exit;

} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "message" => "Error interno del servidor."
    ]);

    exit;
}
?>