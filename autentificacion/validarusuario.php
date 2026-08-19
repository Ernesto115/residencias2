<?php
// Desactivar impresión de errores en pantalla para que no arruinen la salida JSON
ini_set('display_errors', 0);

session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once "../DB/db.php";

    $dbtransportistas = new db();
    $dbtransportistas->conectar();

    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        echo json_encode([
            "status"  => "error",
            "message" => "Debes ingresar usuario y contraseña."
        ]);
        exit;
    }

    // Buscar usuario
    $sql = "SELECT * 
            FROM usuarios 
            WHERE nombre_usuario = :usuario 
            LIMIT 1";

    $stmt = $dbtransportistas->conn->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usuario no encontrado
    if (!$usuarioDB) {
        echo json_encode([
            "status"  => "error",
            "message" => "Usuario o contraseña incorrectos."
        ]);
        exit;
    }

    // Verificar contraseña
    if ($clave !== $usuarioDB['contrasena']) {
        echo json_encode([
            "status"  => "error",
            "message" => "Usuario o contraseña incorrectos."
        ]);
        exit;
    }

    // Normalizar el rol
    $rolNormalizado = strtoupper($usuarioDB['rol']);
    if ($rolNormalizado === 'ADMINISTRADOR') {
        $rolNormalizado = 'ADMIN';
    }

    // --- VALIDACIÓN DE ROLES PERMITIDOS EN EL SISTEMA ---
    $rolesPermitidos = ['ADMIN', 'PROPIETARIO', 'RRHH'];
    if (!in_array($rolNormalizado, $rolesPermitidos)) {
        echo json_encode([
            "status"  => "error",
            "message" => "Tu rol no tiene acceso asignado a este sistema."
        ]);
        exit;
    }

    // Regenerar sesión por seguridad
    session_regenerate_id(true);

    // Crear variables de sesión
    $_SESSION['id_usuario']     = $usuarioDB['id_usuario'];
    $_SESSION['nombre_usuario'] = $usuarioDB['nombre_usuario'];
    $_SESSION['rol']            = $rolNormalizado;
    $_SESSION['id_empresa']     = $usuarioDB['id_empresa'];

    // Respuesta al JavaScript
    echo json_encode([
        "status"  => "success",
        "message" => "Inicio de sesión correcto.",
        "rol"     => $_SESSION['rol']
    ]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Error interno del servidor."
    ]);
    exit;
}