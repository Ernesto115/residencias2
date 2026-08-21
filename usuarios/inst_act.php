<?php

/* =========================================================
   1. SESIÓN
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   2. CONEXIÓN
   ========================================================= */

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   3. SEGURIDAD
   SOLO ADMIN PUEDE CREAR / EDITAR USUARIOS
   ========================================================= */

$rolSesion = strtoupper($_SESSION['rol'] ?? '');

if (
    $rolSesion !== 'ADMIN' &&
    $rolSesion !== 'ADMINISTRADOR'
) {

    http_response_code(403);

    exit(
        "No tienes permiso para administrar usuarios."
    );
}


/* =========================================================
   4. DATOS DEL FORMULARIO
   ========================================================= */

$id_usuario =
    isset($_REQUEST['id_usuario'])
    ? (int)$_REQUEST['id_usuario']
    : 0;


/* DATOS PERSONALES */

$nombres =
    addslashes(
        trim($_REQUEST['nombres'] ?? '')
    );

$primer_apellido =
    addslashes(
        trim($_REQUEST['primer_apellido'] ?? '')
    );

$segundo_apellido =
    addslashes(
        trim($_REQUEST['segundo_apellido'] ?? '')
    );


/* DATOS DE ACCESO */

$nombre_usuario =
    addslashes(
        trim($_REQUEST['nombre_usuario'] ?? '')
    );

$contrasena =
    addslashes(
        $_REQUEST['contrasena'] ?? ''
    );

$rol =
    strtoupper(
        trim($_REQUEST['rol'] ?? '')
    );

$correo_electronico =
    addslashes(
        trim($_REQUEST['correo_electronico'] ?? '')
    );


/* =========================================================
   5. EMPRESA
   ========================================================= */

$id_empresa_val =
    !empty($_REQUEST['id_empresa'])
    ? (int)$_REQUEST['id_empresa']
    : "NULL";


/* ADMINISTRADOR NO NECESITA EMPRESA */
if ($rol === 'ADMINISTRADOR') {

    $id_empresa_val = "NULL";
}


/* =========================================================
   6. VALIDACIONES BÁSICAS
   ========================================================= */

if (
    $nombres === '' ||
    $primer_apellido === '' ||
    $segundo_apellido === '' ||
    $nombre_usuario === '' ||
    $correo_electronico === '' ||
    $rol === ''
) {

    http_response_code(400);

    exit(
        "Completa todos los campos obligatorios."
    );
}


/* =========================================================
   7. ACTUALIZAR USUARIO
   ========================================================= */

if ($id_usuario > 0) {

    $sql = "
        UPDATE usuarios SET

            nombre_usuario = '$nombre_usuario',

            nombres = '$nombres',

            primer_apellido = '$primer_apellido',

            segundo_apellido = '$segundo_apellido',

            rol = '$rol',

            correo_electronico = '$correo_electronico',

            id_empresa = $id_empresa_val
    ";


    /* Solo cambiar contraseña si escribió una nueva */
    if ($contrasena !== '') {

        $sql .= ",
            contrasena = '$contrasena'
        ";
    }


    $sql .= "
        WHERE id_usuario = $id_usuario
    ";


    $db->actualizar($sql);


/* =========================================================
   8. INSERTAR NUEVO USUARIO
   ========================================================= */

} else {

    $sql = "
        INSERT INTO usuarios (

            nombre_usuario,

            nombres,

            primer_apellido,

            segundo_apellido,

            contrasena,

            rol,

            correo_electronico,

            id_empresa

        ) VALUES (

            '$nombre_usuario',

            '$nombres',

            '$primer_apellido',

            '$segundo_apellido',

            '$contrasena',

            '$rol',

            '$correo_electronico',

            $id_empresa_val

        )
    ";


    $db->insertar($sql);
}


/* =========================================================
   IMPORTANTE:
   NO TOCAMOS multiempresa
   NO TOCAMOS usuario_empresas
   ========================================================= */


/* =========================================================
   9. RECARGAR TABLA
   ========================================================= */

$sql = "
    SELECT
        u.*,
        e.nombre_empresa,
        e.razon_social

    FROM usuarios u

    LEFT JOIN empresas e
        ON u.id_empresa = e.id_empresa

    ORDER BY u.id_usuario DESC
";

$datos2 =
    $db->obtenerRegistros($sql);


$db->desconectar();


/* =========================================================
   10. DEVOLVER TABLA
   ========================================================= */

include_once "../usuarios/tabla.php";

?>