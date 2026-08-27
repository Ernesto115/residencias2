<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";

$db = new db();
$db->conectar();


/* =========================================================
   ERROR CONTROLADO
   IMPORTANTE: HTTP 200 PARA QUE guardar() PUEDA LEER EL SCRIPT
   ========================================================= */

function errorUsuario($mensaje, $db, $detalle = '')
{
    $mensajeJS = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $detalleJS = json_encode(
        $detalle,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    echo "<!-- Error MySQL -->
    <script>
        console.error('Error módulo Usuarios:', $detalleJS);

        if(typeof Swal !== 'undefined'){
            Swal.fire({
                icon:'error',
                title:'No se pudo guardar el usuario',
                text:$mensajeJS,
                confirmButtonText:'Entendido',
                confirmButtonColor:'#0f766e',
                background:'#1e293b',
                color:'#ffffff'
            });
        }else{
            alert('❌ ' + $mensajeJS);
        }
    </script>";

    $db->desconectar();
    exit;
}


/* =========================================================
   SESIÓN
   ========================================================= */

$rolSesion = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rolSesion === 'ADMINISTRADOR') {
    $rolSesion = 'ADMIN';
}

if ($rolSesion !== 'ADMIN') {
    errorUsuario(
        'No tienes permiso para administrar usuarios.',
        $db
    );
}


/* SOLO POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorUsuario('Método de solicitud no permitido.', $db);
}


/* =========================================================
   DATOS
   ========================================================= */

$id_usuario = (int)($_POST['id_usuario'] ?? 0);

$nombres = trim($_POST['nombres'] ?? '');
$primer_apellido = trim($_POST['primer_apellido'] ?? '');
$segundo_apellido = trim($_POST['segundo_apellido'] ?? '');

$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$correo = trim($_POST['correo_electronico'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

$rol = strtoupper(trim($_POST['rol'] ?? ''));

if ($rol === 'ADMIN') {
    $rol = 'ADMINISTRADOR';
}

$id_empresa = !empty($_POST['id_empresa'])
    ? (int)$_POST['id_empresa']
    : null;


/* =========================================================
   VALIDACIONES
   ========================================================= */

if (
    $nombres === '' ||
    $primer_apellido === '' ||
    $segundo_apellido === '' ||
    $nombre_usuario === '' ||
    $correo === '' ||
    $rol === ''
) {
    errorUsuario(
        'Completa todos los campos obligatorios.',
        $db
    );
}


if (!in_array(
    $rol,
    ['ADMINISTRADOR','PROPIETARIO','RRHH'],
    true
)) {
    errorUsuario(
        'El rol seleccionado no es válido.',
        $db
    );
}


if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    errorUsuario(
        'El correo electrónico no tiene un formato válido.',
        $db
    );
}


if (strlen($nombre_usuario) > 13) {
    errorUsuario(
        'El nombre de usuario no puede superar 13 caracteres.',
        $db
    );
}


/* =========================================================
   USUARIO EXISTENTE AL EDITAR
   ========================================================= */

$actual = null;

if ($id_usuario > 0) {

    $stmt = $db->conn->prepare(
        "SELECT id_usuario,rol,id_empresa,multiempresa
         FROM usuarios
         WHERE id_usuario=:id
         LIMIT 1"
    );

    $stmt->execute([':id'=>$id_usuario]);

    $actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actual) {
        errorUsuario('El usuario que intentas editar no existe.', $db);
    }
}


/* =========================================================
   CONTRASEÑA
   ========================================================= */

if ($id_usuario <= 0 && $contrasena === '') {
    errorUsuario(
        'Debes ingresar una contraseña para el nuevo usuario.',
        $db
    );
}


if ($contrasena !== '') {

    if (
        strlen($contrasena) !== 12 ||
        !preg_match('/[A-Z]/', $contrasena) ||
        !preg_match('/[a-z]/', $contrasena) ||
        !preg_match('/[^A-Za-z0-9]/', $contrasena)
    ) {
        errorUsuario(
            'La contraseña debe tener exactamente 12 caracteres, incluyendo mayúscula, minúscula y símbolo.',
            $db
        );
    }
}


/* =========================================================
   EMPRESA
   ========================================================= */

/*
   Si ya es propietario multiempresa y solo estamos editando
   sus datos, conservamos sus empresas actuales.
*/
$preservarMultiempresa =
    $actual &&
    strtoupper($actual['rol'] ?? '') === 'PROPIETARIO' &&
    (int)($actual['multiempresa'] ?? 0) === 1 &&
    $rol === 'PROPIETARIO';


if ($rol === 'ADMINISTRADOR') {

    $id_empresa = null;

} elseif (!$preservarMultiempresa) {

    if (!$id_empresa) {
        errorUsuario(
            'Debes seleccionar una empresa para el propietario o RRHH.',
            $db
        );
    }

    $stmt = $db->conn->prepare(
        "SELECT id_empresa
         FROM empresas
         WHERE id_empresa=:empresa
         LIMIT 1"
    );

    $stmt->execute([
        ':empresa'=>$id_empresa
    ]);

    if (!$stmt->fetchColumn()) {
        errorUsuario(
            'La empresa seleccionada no existe.',
            $db
        );
    }
}


/* =========================================================
   DUPLICADOS
   ========================================================= */

$stmt = $db->conn->prepare(
    "SELECT id_usuario
     FROM usuarios
     WHERE nombre_usuario=:usuario
     AND id_usuario<>:id
     LIMIT 1"
);

$stmt->execute([
    ':usuario'=>$nombre_usuario,
    ':id'=>$id_usuario
]);

if ($stmt->fetchColumn()) {
    errorUsuario(
        'El nombre de usuario ya se encuentra registrado.',
        $db
    );
}


$stmt = $db->conn->prepare(
    "SELECT id_usuario
     FROM usuarios
     WHERE correo_electronico=:correo
     AND id_usuario<>:id
     LIMIT 1"
);

$stmt->execute([
    ':correo'=>$correo,
    ':id'=>$id_usuario
]);

if ($stmt->fetchColumn()) {
    errorUsuario(
        'El correo electrónico ya se encuentra registrado.',
        $db
    );
}


/* =========================================================
   GUARDAR
   ========================================================= */

try {

    $db->conn->beginTransaction();


    /* EDITAR */
    if ($id_usuario > 0) {

        $multiempresaFinal =
            $preservarMultiempresa ? 1 : 0;

        $empresaFinal =
            $preservarMultiempresa
            ? null
            : $id_empresa;


        $sql = "
            UPDATE usuarios SET
                nombre_usuario=:usuario,
                nombres=:nombres,
                primer_apellido=:apellido1,
                segundo_apellido=:apellido2,
                rol=:rol,
                correo_electronico=:correo,
                id_empresa=:empresa,
                multiempresa=:multi
        ";


        if ($contrasena !== '') {
            $sql .= ", contrasena=:contrasena";
        }


        $sql .= " WHERE id_usuario=:id";


        $stmt = $db->conn->prepare($sql);

        $params = [
            ':usuario'=>$nombre_usuario,
            ':nombres'=>$nombres,
            ':apellido1'=>$primer_apellido,
            ':apellido2'=>$segundo_apellido,
            ':rol'=>$rol,
            ':correo'=>$correo,
            ':empresa'=>$empresaFinal,
            ':multi'=>$multiempresaFinal,
            ':id'=>$id_usuario
        ];


        if ($contrasena !== '') {
            $params[':contrasena'] = $contrasena;
        }


        $stmt->execute($params);


        /*
           Si deja de ser propietario multiempresa,
           quitar relaciones anteriores.
        */
        if (!$preservarMultiempresa) {

            $stmt = $db->conn->prepare(
                "DELETE FROM usuario_empresas
                 WHERE id_usuario=:usuario"
            );

            $stmt->execute([
                ':usuario'=>$id_usuario
            ]);
        }

    }


    /* NUEVO */
    else {

        $stmt = $db->conn->prepare(
            "INSERT INTO usuarios (
                nombre_usuario,
                nombres,
                primer_apellido,
                segundo_apellido,
                contrasena,
                rol,
                correo_electronico,
                id_empresa,
                multiempresa
            ) VALUES (
                :usuario,
                :nombres,
                :apellido1,
                :apellido2,
                :contrasena,
                :rol,
                :correo,
                :empresa,
                0
            )"
        );

        $stmt->execute([
            ':usuario'=>$nombre_usuario,
            ':nombres'=>$nombres,
            ':apellido1'=>$primer_apellido,
            ':apellido2'=>$segundo_apellido,
            ':contrasena'=>$contrasena,
            ':rol'=>$rol,
            ':correo'=>$correo,
            ':empresa'=>$id_empresa
        ]);


        if ($stmt->rowCount() !== 1) {
            throw new Exception(
                'El registro no fue insertado.'
            );
        }
    }


    $db->conn->commit();

} catch (Throwable $e) {

    if ($db->conn->inTransaction()) {
        $db->conn->rollBack();
    }


    $mensaje = 'La base de datos rechazó el registro.';


    if (
        $e instanceof PDOException &&
        (string)$e->getCode() === '23000'
    ) {
        $mensaje =
            'Existe información duplicada o una relación de empresa no válida.';
    }


    errorUsuario(
        $mensaje,
        $db,
        $e->getMessage()
    );
}


/* =========================================================
   DEVOLVER TABLA ACTUALIZADA
   ========================================================= */

include "../usuarios/tabla.php";

$db->desconectar();
?>