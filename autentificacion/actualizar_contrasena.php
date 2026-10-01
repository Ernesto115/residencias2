<?php

ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);


/* =========================================================
   RESPUESTA JSON
   ========================================================= */

function responderCambioContrasena(
    $status,
    $message,
    $codigo = 200
) {

    http_response_code($codigo);

    echo json_encode(
        [
            'status' => $status,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

$db = null;


try {

    /* =====================================================
       1. SOLO POST
       ===================================================== */

    if (
        $_SERVER['REQUEST_METHOD'] !==
        'POST'
    ) {

        responderCambioContrasena(
            'error',
            'Método de solicitud no permitido.',
            405
        );
    }


    /* =====================================================
       2. VALIDAR SESIÓN
       ===================================================== */

    $idUsuario =
        (int)(
            $_SESSION['id_usuario']
            ?? 0
        );


    if ($idUsuario <= 0) {

        responderCambioContrasena(
            'error',
            'Tu sesión no es válida. Inicia sesión nuevamente.',
            401
        );
    }


    /* =====================================================
       3. VALIDAR CAMBIO OBLIGATORIO EN SESIÓN
       ===================================================== */

    $requiereCambio =
        (int)(
            $_SESSION[
                'requiere_cambio_contrasena'
            ]
            ?? 0
        );


    if ($requiereCambio !== 1) {

        responderCambioContrasena(
            'error',
            'Tu cuenta no requiere un cambio obligatorio de contraseña.',
            403
        );
    }


    /* =====================================================
       4. RECIBIR CONTRASEÑAS
       ===================================================== */

    $nuevaContrasena =
        $_POST[
            'nueva_contrasena'
        ]
        ?? '';


    $confirmarContrasena =
        $_POST[
            'confirmar_contrasena'
        ]
        ?? '';


    if (
        $nuevaContrasena === '' ||
        $confirmarContrasena === ''
    ) {

        responderCambioContrasena(
            'error',
            'Debes completar ambos campos de contraseña.',
            422
        );
    }


    /* =====================================================
       5. VALIDAR COINCIDENCIA
       ===================================================== */

    if (
        !hash_equals(
            $nuevaContrasena,
            $confirmarContrasena
        )
    ) {

        responderCambioContrasena(
            'error',
            'Las contraseñas no coinciden.',
            422
        );
    }


    /* =====================================================
       6. VALIDAR REQUISITOS
       ===================================================== */

    if (
        mb_strlen(
            $nuevaContrasena,
            'UTF-8'
        ) !== 12
    ) {

        responderCambioContrasena(
            'error',
            'La contraseña debe contener exactamente 12 caracteres.',
            422
        );
    }


    if (
        !preg_match(
            '/[A-ZÁÉÍÓÚÜÑ]/u',
            $nuevaContrasena
        )
    ) {

        responderCambioContrasena(
            'error',
            'La contraseña debe contener al menos una letra mayúscula.',
            422
        );
    }


    if (
        !preg_match(
            '/[a-záéíóúüñ]/u',
            $nuevaContrasena
        )
    ) {

        responderCambioContrasena(
            'error',
            'La contraseña debe contener al menos una letra minúscula.',
            422
        );
    }


    if (
        !preg_match(
            '/[0-9]/',
            $nuevaContrasena
        )
    ) {

        responderCambioContrasena(
            'error',
            'La contraseña debe contener al menos un número.',
            422
        );
    }


    if (
        !preg_match(
            '/[!@#$%&*?]/',
            $nuevaContrasena
        )
    ) {

        responderCambioContrasena(
            'error',
            'La contraseña debe contener al menos un carácter especial.',
            422
        );
    }


    /* =====================================================
       7. CONECTAR A MYSQL
       ===================================================== */

    require_once __DIR__ . "/../DB/db.php";


    $db =
        new db();


    $db->conectar();


    /* =====================================================
       8. CONSULTAR USUARIO ACTUAL
       ===================================================== */

    $stmt =
        $db->conn->prepare(

            "SELECT
                contrasena,
                requiere_cambio_contrasena,
                estatus

             FROM usuarios

             WHERE id_usuario = :id

             LIMIT 1"
        );


    $stmt->execute([
        ':id' =>
            $idUsuario
    ]);


    $usuario =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$usuario) {

        $db->desconectar();

        responderCambioContrasena(
            'error',
            'El usuario ya no existe.',
            404
        );
    }


    /* =====================================================
       9. VALIDAR CUENTA ACTIVA
       ===================================================== */

    if (
        isset($usuario['estatus']) &&
        (int)$usuario['estatus'] !== 1
    ) {

        $db->desconectar();

        responderCambioContrasena(
            'error',
            'La cuenta se encuentra desactivada.',
            403
        );
    }


    /* =====================================================
       10. CONFIRMAR BANDERA EN MYSQL
       ===================================================== */

    if (
        (int)$usuario[
            'requiere_cambio_contrasena'
        ] !== 1
    ) {

        $_SESSION[
            'requiere_cambio_contrasena'
        ] = 0;


        $db->desconectar();


        responderCambioContrasena(
            'error',
            'La cuenta ya no requiere cambiar la contraseña.',
            403
        );
    }


    /* =====================================================
       11. NO REUTILIZAR CONTRASEÑA TEMPORAL
       ===================================================== */

    $contrasenaActual =
        (string)(
            $usuario['contrasena']
            ?? ''
        );


    $infoHash =
        password_get_info(
            $contrasenaActual
        );


    $esHash =
        (
            $infoHash['algoName']
            ?? 'unknown'
        ) !== 'unknown';


    if ($esHash) {

        $esLaMisma =
            password_verify(
                $nuevaContrasena,
                $contrasenaActual
            );

    } else {

        $esLaMisma =
            hash_equals(
                $contrasenaActual,
                $nuevaContrasena
            );
    }


    if ($esLaMisma) {

        $db->desconectar();

        responderCambioContrasena(
            'error',
            'La nueva contraseña debe ser diferente a la contraseña temporal.',
            422
        );
    }


    /* =====================================================
       12. GENERAR NUEVO HASH
       ===================================================== */

    $nuevoHash =
        password_hash(
            $nuevaContrasena,
            PASSWORD_DEFAULT
        );


    if ($nuevoHash === false) {

        $db->desconectar();

        responderCambioContrasena(
            'error',
            'No fue posible proteger la nueva contraseña.',
            500
        );
    }


    /* =====================================================
       13. ACTUALIZAR CONTRASEÑA
       ===================================================== */

    $stmt =
        $db->conn->prepare(

            "UPDATE usuarios

             SET
                contrasena = :contrasena,
                requiere_cambio_contrasena = 0

             WHERE
                id_usuario = :id
                AND requiere_cambio_contrasena = 1
                AND estatus = 1"
        );


    $stmt->execute([
        ':contrasena' =>
            $nuevoHash,

        ':id' =>
            $idUsuario
    ]);


    if (
        $stmt->rowCount() !== 1
    ) {

        $db->desconectar();

        responderCambioContrasena(
            'error',
            'No fue posible actualizar la contraseña. Intenta nuevamente.',
            409
        );
    }


    /* =====================================================
       14. ACTUALIZAR SESIÓN
       ===================================================== */

    $_SESSION[
        'requiere_cambio_contrasena'
    ] = 0;


    $db->desconectar();


    /* =====================================================
       15. RESPUESTA FINAL
       ===================================================== */

    responderCambioContrasena(
        'success',
        'Tu contraseña fue actualizada correctamente.'
    );


} catch (Throwable $e) {

    if (
        $db !== null &&
        isset($db->conn) &&
        $db->conn
    ) {

        try {
            $db->desconectar();
        } catch (Throwable $ignorar) {
        }
    }


    responderCambioContrasena(
        'error',
        'Ocurrió un error interno al actualizar la contraseña.',
        500
    );
}

?>