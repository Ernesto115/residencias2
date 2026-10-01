<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CERRAR SESIÓN INVÁLIDA
   ========================================================= */

function cerrarSesionInvalida($motivo = '')
{
    /* Limpiar variables */
    $_SESSION = [];


    /* Eliminar cookie de sesión */
    if (ini_get('session.use_cookies')) {

        $parametros =
            session_get_cookie_params();


        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parametros['path'],
            $parametros['domain'],
            $parametros['secure'],
            $parametros['httponly']
        );
    }


    /* Destruir sesión */
    session_destroy();


    /* =====================================================
       REDIRECCIÓN
       ===================================================== */

    if (
        $motivo ===
        'cuenta_desactivada'
    ) {

        header(
            "Location: /login.php?motivo=cuenta_desactivada"
        );

        exit();
    }


    header(
        "Location: /login.php"
    );

    exit();
}


/* =========================================================
   VERIFICAR SESIÓN
   ========================================================= */

function verificarSesion(
    $permitirCambioContrasena = false
)
{
    /* =====================================================
       1. NO EXISTE SESIÓN
       ===================================================== */

    if (
        !isset(
            $_SESSION['id_usuario']
        ) ||
        (int)$_SESSION['id_usuario'] <= 0
    ) {

        cerrarSesionInvalida();
    }


    /* =====================================================
       2. VALIDAR CUENTA EN BASE DE DATOS
       ===================================================== */

    require_once __DIR__ . "/../DB/db.php";


    $dbSesion =
        new db();


    try {

        $dbSesion->conectar();


        $stmt =
            $dbSesion->conn->prepare(

                "SELECT
                    id_usuario,
                    estatus,
                    requiere_cambio_contrasena

                 FROM usuarios

                 WHERE id_usuario = :id

                 LIMIT 1"
            );


        $stmt->execute([
            ':id' =>
                (int)$_SESSION[
                    'id_usuario'
                ]
        ]);


        $usuarioSesion =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /* =================================================
           3. USUARIO YA NO EXISTE
           ================================================= */

        if (!$usuarioSesion) {

            $dbSesion->desconectar();

            cerrarSesionInvalida();
        }


        /* =================================================
           4. CUENTA DESACTIVADA
           ================================================= */

        if (
            (int)$usuarioSesion[
                'estatus'
            ] !== 1
        ) {

            $dbSesion->desconectar();


            cerrarSesionInvalida(
                'cuenta_desactivada'
            );
        }


        /* =================================================
           5. SINCRONIZAR CAMBIO DE CONTRASEÑA CON MYSQL
           ================================================= */

        $requiereCambioContrasena =
            (int)$usuarioSesion[
                'requiere_cambio_contrasena'
            ];


        $_SESSION[
            'requiere_cambio_contrasena'
        ] =
            $requiereCambioContrasena;


        /* =================================================
           6. FORZAR CAMBIO DE CONTRASEÑA
           ================================================= */

        if (
            $requiereCambioContrasena === 1 &&
            !$permitirCambioContrasena
        ) {

            $dbSesion->desconectar();


            header(
                "Location: /autentificacion/cambiar_contrasena.php"
            );


            exit();
        }


        /* =================================================
           7. CUENTA CORRECTA
           ================================================= */

        $dbSesion->desconectar();


    } catch (Throwable $e) {

        if (
            isset($dbSesion->conn) &&
            $dbSesion->conn
        ) {

            try {

                $dbSesion->desconectar();

            } catch (Throwable $ignorar) {
            }
        }


        cerrarSesionInvalida();
    }
}


/* =========================================================
   VERIFICAR ROL
   ========================================================= */

function verificarRol(
    $rolesPermitidos = []
)
{
    /*
     * Al no enviar TRUE, cualquier usuario
     * con cambio obligatorio pendiente será
     * redirigido antes de validar su rol.
     */
    verificarSesion();


    if (
        !is_array(
            $rolesPermitidos
        )
    ) {

        $rolesPermitidos = [
            $rolesPermitidos
        ];
    }


    $rolesPermitidosUpper =
        array_map(
            'strtoupper',
            $rolesPermitidos
        );


    $rolUsuario =
        strtoupper(
            $_SESSION['rol']
            ?? ''
        );


    if (
        !empty(
            $rolesPermitidosUpper
        ) &&
        !in_array(
            $rolUsuario,
            $rolesPermitidosUpper,
            true
        )
    ) {

        http_response_code(403);


        echo "
        <div style='
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        '>

            <h1 style='
                font-size: 60px;
                color: #dc3545;
                margin-bottom: 0;
            '>
                403
            </h1>

            <h2 style='color: #333;'>
                Acceso Denegado
            </h2>

            <p style='color: #666;'>
                No tienes permisos para acceder a este módulo.
            </p>

            <a
                href='javascript:history.back()'
                style='
                    display: inline-block;
                    margin-top: 15px;
                    padding: 10px 20px;
                    background-color: #0d6efd;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                '
            >
                ⬅️ Volver a la página anterior
            </a>

        </div>";

        exit();
    }
}

?>