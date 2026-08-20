<?php

/* =========================================================
   VALIDACIÓN DE USUARIO E INICIO DE SESIÓN
   ========================================================= */

// Evita que errores PHP dañen la respuesta JSON.
ini_set('display_errors', 0);

// Iniciar sesión.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// La respuesta de este archivo siempre será JSON.
header('Content-Type: application/json; charset=utf-8');


try {

    /* =====================================================
       1. CONEXIÓN A BASE DE DATOS
       ===================================================== */

    require_once "../DB/db.php";

    $dbtransportistas = new db();
    $dbtransportistas->conectar();


    /* =====================================================
       2. RECIBIR DATOS DEL LOGIN
       ===================================================== */

    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';


    // Validar campos vacíos.
    if ($usuario === '' || $clave === '') {

        echo json_encode([
            "status"  => "error",
            "message" => "Debes ingresar usuario y contraseña."
        ]);

        exit;
    }


    /* =====================================================
       3. BUSCAR USUARIO
       ===================================================== */

    $sql = "
        SELECT *
        FROM usuarios
        WHERE nombre_usuario = :usuario
        LIMIT 1
    ";

    $stmt = $dbtransportistas->conn->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);


    // Usuario no encontrado.
    if (!$usuarioDB) {

        echo json_encode([
            "status"  => "error",
            "message" => "Usuario o contraseña incorrectos."
        ]);

        exit;
    }


    /* =====================================================
       4. VALIDAR CONTRASEÑA
       ===================================================== */

    if ($clave !== $usuarioDB['contrasena']) {

        echo json_encode([
            "status"  => "error",
            "message" => "Usuario o contraseña incorrectos."
        ]);

        exit;
    }


    /* =====================================================
       5. NORMALIZAR ROL
       ===================================================== */

    $rolNormalizado = strtoupper(
        trim($usuarioDB['rol'])
    );


    // Internamente ADMINISTRADOR se manejará como ADMIN.
    if ($rolNormalizado === 'ADMINISTRADOR') {
        $rolNormalizado = 'ADMIN';
    }


    // Roles permitidos.
    $rolesPermitidos = [
        'ADMIN',
        'PROPIETARIO',
        'RRHH'
    ];


    if (!in_array($rolNormalizado, $rolesPermitidos, true)) {

        echo json_encode([
            "status"  => "error",
            "message" => "Tu rol no tiene acceso asignado a este sistema."
        ]);

        exit;
    }


    /* =====================================================
       6. CREAR SESIÓN
       ===================================================== */

    // Regenerar ID de sesión.
    session_regenerate_id(true);


    $_SESSION['id_usuario'] =
        (int)$usuarioDB['id_usuario'];


    $_SESSION['nombre_usuario'] =
        $usuarioDB['nombre_usuario'];


    $_SESSION['rol'] =
        $rolNormalizado;


    // Empresa principal del usuario.
    $_SESSION['id_empresa'] =
        !empty($usuarioDB['id_empresa'])
            ? (int)$usuarioDB['id_empresa']
            : null;


    /*
       multiempresa se utilizará principalmente
       para usuarios PROPIETARIO.

       0 = una empresa
       1 = varias empresas
    */
    $_SESSION['multiempresa'] =
        isset($usuarioDB['multiempresa'])
            ? (int)$usuarioDB['multiempresa']
            : 0;


    /* =====================================================
       7. RESPUESTA AL JAVASCRIPT
       ===================================================== */

    echo json_encode([
        "status"       => "success",
        "message"      => "Inicio de sesión correcto.",
        "rol"          => $_SESSION['rol'],
        "id_empresa"   => $_SESSION['id_empresa'],
        "multiempresa" => $_SESSION['multiempresa']
    ]);

    exit;


} catch (Throwable $e) {

    echo json_encode([
        "status"  => "error",
        "message" => "Error interno del servidor."
    ]);

    exit;
}