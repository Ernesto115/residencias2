<?php

/* =========================================================
   VALIDACIÓN DE USUARIO E INICIO DE SESIÓN
   ========================================================= */

ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);

try {

    /* =====================================================
       1. CONEXIÓN
       ===================================================== */

    require_once __DIR__ . "/../DB/db.php";

    $dbtransportistas = new db();
    $dbtransportistas->conectar();


    /* =====================================================
       2. DATOS DEL LOGIN
       ===================================================== */

    $usuario =
        trim(
            $_POST['usuario'] ?? ''
        );

    $clave =
        $_POST['clave'] ?? '';


    if (
        $usuario === '' ||
        $clave === ''
    ) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Debes ingresar usuario y contraseña.'
        ]);

        exit;
    }


    /* =====================================================
       3. BUSCAR USUARIO
       ===================================================== */

    $sql =
        "SELECT *
         FROM usuarios
         WHERE nombre_usuario = :usuario
         LIMIT 1";


    $stmt =
        $dbtransportistas
            ->conn
            ->prepare($sql);


    $stmt->execute([
        ':usuario' =>
            $usuario
    ]);


    $usuarioDB =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    /* =====================================================
       4. VALIDAR EXISTENCIA
       ===================================================== */

    if (!$usuarioDB) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Usuario o contraseña incorrectos.'
        ]);

        exit;
    }


    /* =====================================================
       5. VALIDAR CONTRASEÑA
       ===================================================== */

    $contrasenaGuardada =
        (string)(
            $usuarioDB['contrasena']
            ?? ''
        );


    $credencialesValidas =
        false;


    $informacionHash =
        password_get_info(
            $contrasenaGuardada
        );


    $esHash =
        (
            $informacionHash['algoName']
            ?? 'unknown'
        ) !== 'unknown';


    /*
     * Contraseña moderna con password_hash()
     */

    if ($esHash) {

        $credencialesValidas =
            password_verify(
                $clave,
                $contrasenaGuardada
            );

    /*
     * Compatibilidad temporal con
     * contraseñas antiguas en texto plano.
     */

    } else {

        $credencialesValidas =
            hash_equals(
                $contrasenaGuardada,
                $clave
            );
    }


    if (!$credencialesValidas) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Usuario o contraseña incorrectos.'
        ]);

        exit;
    }


    /* =====================================================
       6. VALIDAR ESTATUS
       ===================================================== */

    $estatusUsuario =
        isset(
            $usuarioDB['estatus']
        )
            ? (int)$usuarioDB['estatus']
            : 1;


    if ($estatusUsuario !== 1) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Tu cuenta se encuentra desactivada. Contacta al administrador del sistema.'
        ]);

        exit;
    }


    /* =====================================================
       7. ROL
       ===================================================== */

    $rolNormalizado =
        strtoupper(
            trim(
                $usuarioDB['rol']
                ?? ''
            )
        );


    if (
        $rolNormalizado ===
        'ADMINISTRADOR'
    ) {

        $rolNormalizado =
            'ADMIN';
    }


    if (
        !in_array(
            $rolNormalizado,
            [
                'ADMIN',
                'PROPIETARIO',
                'RRHH'
            ],
            true
        )
    ) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Tu rol no tiene acceso asignado a este sistema.'
        ]);

        exit;
    }


    /* =====================================================
       8. CAMBIO DE CONTRASEÑA REQUERIDO
       ===================================================== */

    $requiereCambioContrasena =
        isset(
            $usuarioDB[
                'requiere_cambio_contrasena'
            ]
        )
            ? (int)$usuarioDB[
                'requiere_cambio_contrasena'
            ]
            : 0;


    /*
     * Por ahora únicamente guardamos el valor.
     *
     * En el siguiente paso utilizaremos este dato
     * para enviar al usuario a la pantalla obligatoria
     * de cambio de contraseña.
     */


    /* =====================================================
       9. NOMBRE COMPLETO
       ===================================================== */

    $nombreCompleto =
        trim(
            ($usuarioDB['nombres'] ?? '') .
            ' ' .
            ($usuarioDB['primer_apellido'] ?? '') .
            ' ' .
            ($usuarioDB['segundo_apellido'] ?? '')
        );


    if ($nombreCompleto === '') {

        $nombreCompleto =
            $usuarioDB[
                'nombre_usuario'
            ];
    }


    /* =====================================================
       10. CREAR SESIÓN
       ===================================================== */

    session_regenerate_id(true);


    $_SESSION['id_usuario'] =
        (int)$usuarioDB[
            'id_usuario'
        ];


    $_SESSION['nombre_usuario'] =
        $usuarioDB[
            'nombre_usuario'
        ];


    $_SESSION['nombre_completo'] =
        $nombreCompleto;


    $_SESSION['rol'] =
        $rolNormalizado;


    $_SESSION['id_empresa'] =
        !empty(
            $usuarioDB['id_empresa']
        )
            ? (int)$usuarioDB[
                'id_empresa'
            ]
            : null;


    $_SESSION['multiempresa'] =
        isset(
            $usuarioDB[
                'multiempresa'
            ]
        )
            ? (int)$usuarioDB[
                'multiempresa'
            ]
            : 0;


    /* NUEVO */

    $_SESSION['requiere_cambio_contrasena'] =
        $requiereCambioContrasena;


    /* =====================================================
       11. RESPUESTA
       ===================================================== */

    echo json_encode([

        'status' =>
            'success',

        'message' =>
            'Inicio de sesión correcto.',

        'nombre_completo' =>
            $_SESSION[
                'nombre_completo'
            ],

        'rol' =>
            $_SESSION['rol'],

        'id_empresa' =>
            $_SESSION[
                'id_empresa'
            ],

        'multiempresa' =>
            $_SESSION[
                'multiempresa'
            ],

        /* NUEVO */

        'requiere_cambio_contrasena' =>
            $_SESSION[
                'requiere_cambio_contrasena'
            ]

    ]);


    exit;


} catch (Throwable $e) {

    echo json_encode([
        'status' =>
            'error',

        'message' =>
            'Error interno del servidor.'
    ]);

    exit;
}

?>