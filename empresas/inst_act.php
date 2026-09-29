<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start();
}



include_once "../db/db.php";


$db = new db();

$db->conectar();



/* =========================================================
   ERROR CONTROLADO
   ========================================================= */

function errorEmpresa(
    $mensaje,
    $db,
    $cerrar = false
) {

    $msg = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    $cerrarJS = $cerrar
        ? "if(typeof cerrarModalEmpresa==='function') cerrarModalEmpresa();"
        : "";


    echo "<!-- Error MySQL -->

    <script>

        $cerrarJS

        if(typeof Swal !== 'undefined'){

            Swal.fire({
                icon:'error',
                title:'No se pudo guardar la empresa',
                text:$msg,
                confirmButtonText:'Entendido',
                confirmButtonColor:'#1e40af'
            });

        }else{

            alert('❌ ' + $msg);
        }

    </script>";


    $db->desconectar();


    exit;
}



/* =========================================================
   MENSAJE DE DETECCIÓN DEL PROPIETARIO

   TEMPORAL:
   Se utiliza solamente mientras probamos la detección
   de propietario nuevo / existente.

   En la siguiente fase este bloque se retirará para
   permitir que la empresa y el propietario se guarden.
   ========================================================= */

function mensajeDeteccionPropietario(
    $mensaje,
    $db
) {

    $msg = json_encode(
        $mensaje,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    echo "<!-- Validación Propietario -->

    <script>

        if(typeof Swal !== 'undefined'){

            Swal.fire({
                icon:'info',
                title:'Validación del propietario',
                text:$msg,
                confirmButtonText:'Entendido',
                confirmButtonColor:'#1e40af'
            });

        }else{

            alert('ℹ️ ' + $msg);
        }

    </script>";


    $db->desconectar();


    exit;
}



/* =========================================================
   SESIÓN
   ========================================================= */

$rol =
    strtoupper(
        trim($_SESSION['rol'] ?? '')
    );


if ($rol === 'ADMINISTRADOR') {

    $rol = 'ADMIN';
}


$id_usuario =
    (int)($_SESSION['id_usuario'] ?? 0);


$id_empresa_sesion =
    (int)($_SESSION['id_empresa'] ?? 0);


$multiempresa =
    (int)($_SESSION['multiempresa'] ?? 0);



/* =========================================================
   SOLO POST
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    errorEmpresa(
        'Método de solicitud no permitido.',
        $db
    );
}



/* =========================================================
   ROLES
   ========================================================= */

if (
    !in_array(
        $rol,
        ['ADMIN','PROPIETARIO'],
        true
    )
) {

    errorEmpresa(
        'No tienes permiso para administrar empresas.',
        $db,
        true
    );
}



/* =========================================================
   DATOS DE LA EMPRESA
   ========================================================= */

$id_empresa =
    (int)($_POST['id_empresa'] ?? 0);


$nombre_empresa =
    trim($_POST['nombre_empresa'] ?? '');


$razon_social =
    trim($_POST['razon_social'] ?? '');


$direccion_fiscal =
    trim($_POST['direccion_fiscal'] ?? '');


$responsable =
    trim($_POST['responsable'] ?? '');



/* =========================================================
   DATOS DEL PROPIETARIO
   ========================================================= */

/*
 * Estos campos solamente son utilizados cuando un ADMIN
 * registra una empresa nueva.
 *
 * Cuando se edita una empresa o cuando un PROPIETARIO
 * registra otra empresa, pueden llegar vacíos sin problema.
 */

$prop_nombres =
    trim(
        $_POST['prop_nombres'] ?? ''
    );


$prop_primer_apellido =
    trim(
        $_POST['prop_primer_apellido'] ?? ''
    );


$prop_segundo_apellido =
    trim(
        $_POST['prop_segundo_apellido'] ?? ''
    );


$prop_nombre_usuario =
    trim(
        $_POST['prop_nombre_usuario'] ?? ''
    );


$prop_correo_electronico =
    trim(
        $_POST['prop_correo_electronico'] ?? ''
    );



/* =========================================================
   ¿ESTA OPERACIÓN REQUIERE DATOS DEL PROPIETARIO?
   ========================================================= */

/*
 * Solamente los necesitamos cuando:
 *
 * - El usuario que realiza la operación es ADMIN.
 * - Se está creando una empresa NUEVA.
 *
 * id_empresa <= 0 significa que todavía no existe
 * una empresa que estemos editando.
 */

$requiereDatosPropietario =
    $rol === 'ADMIN' &&
    $id_empresa <= 0;



/* =========================================================
   CAMPOS OBLIGATORIOS DE LA EMPRESA
   ========================================================= */

if (
    $nombre_empresa === '' ||
    $razon_social === '' ||
    $direccion_fiscal === '' ||
    $responsable === ''
) {

    errorEmpresa(
        'Completa todos los campos obligatorios.',
        $db
    );
}



/* =========================================================
   CAMPOS OBLIGATORIOS DEL PROPIETARIO
   ========================================================= */

if ($requiereDatosPropietario) {

    if (
        $prop_nombres === '' ||
        $prop_primer_apellido === '' ||
        $prop_segundo_apellido === '' ||
        $prop_nombre_usuario === '' ||
        $prop_correo_electronico === ''
    ) {

        errorEmpresa(
            'Completa todos los datos del propietario.',
            $db
        );
    }
}



/* =========================================================
   LONGITUDES DE LA EMPRESA
   ========================================================= */

if (
    strlen($nombre_empresa) > 100 ||
    strlen($razon_social) > 150 ||
    strlen($direccion_fiscal) > 200 ||
    strlen($responsable) > 100
) {

    errorEmpresa(
        'Uno de los campos supera la longitud permitida.',
        $db
    );
}



/* =========================================================
   VALIDACIONES DEL PROPIETARIO
   ========================================================= */

if ($requiereDatosPropietario) {


    /* =====================================================
       LONGITUDES
       ===================================================== */

    if (
        strlen($prop_nombres) > 50 ||
        strlen($prop_primer_apellido) > 50 ||
        strlen($prop_segundo_apellido) > 50 ||
        strlen($prop_nombre_usuario) > 13 ||
        strlen($prop_correo_electronico) > 100
    ) {

        errorEmpresa(
            'Uno de los datos del propietario supera la longitud permitida.',
            $db
        );
    }


    /* =====================================================
       CORREO ELECTRÓNICO
       ===================================================== */

    if (
        !filter_var(
            $prop_correo_electronico,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        errorEmpresa(
            'El correo electrónico del propietario no tiene un formato válido.',
            $db
        );
    }

}



/* =========================================================
   EMPRESA DUPLICADA
   ========================================================= */

$stmt =
    $db->conn->prepare(
        "SELECT
            id_empresa,
            nombre_empresa,
            razon_social

         FROM empresas

         WHERE (
            nombre_empresa = :nombre
            OR razon_social = :razon
         )

         AND id_empresa <> :id

         LIMIT 1"
    );


$stmt->execute([
    ':nombre' => $nombre_empresa,
    ':razon' => $razon_social,
    ':id' => $id_empresa
]);


$duplicada =
    $stmt->fetch(PDO::FETCH_ASSOC);


if ($duplicada) {

    if (
        strcasecmp(
            trim($duplicada['nombre_empresa']),
            $nombre_empresa
        ) === 0
    ) {

        errorEmpresa(
            'Ya existe una empresa con ese nombre comercial.',
            $db
        );
    }


    errorEmpresa(
        'Ya existe una empresa con esa razón social.',
        $db
    );
}



/* =========================================================
   DETECTAR PROPIETARIO EXISTENTE

   SOLO:
   ADMIN + EMPRESA NUEVA
   ========================================================= */

$propietarioExistente = null;

$tipoPropietario = null;


if ($requiereDatosPropietario) {


    /*
     * Buscamos coincidencias tanto por:
     *
     * - RFC / nombre de usuario
     * - correo electrónico
     *
     * Puede regresar:
     *
     * 0 registros
     * 1 registro
     * 2 registros
     *
     * Dos registros sería posible si el RFC pertenece
     * a una persona y el correo a otra.
     */

    $stmt =
        $db->conn->prepare(
            "SELECT
                id_usuario,
                nombre_usuario,
                nombres,
                primer_apellido,
                segundo_apellido,
                correo_electronico,
                rol,
                id_empresa,
                multiempresa

             FROM usuarios

             WHERE nombre_usuario = :usuario
                OR correo_electronico = :correo

             ORDER BY id_usuario ASC"
        );


    $stmt->execute([
        ':usuario' =>
            $prop_nombre_usuario,

        ':correo' =>
            $prop_correo_electronico
    ]);


    $usuariosEncontrados =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
     * Guardaremos por separado:
     *
     * - usuario encontrado por RFC
     * - usuario encontrado por correo
     */

    $usuarioPorRFC = null;

    $usuarioPorCorreo = null;


    foreach (
        $usuariosEncontrados
        as $usuarioEncontrado
    ) {


        if (
            strcasecmp(
                trim(
                    $usuarioEncontrado[
                        'nombre_usuario'
                    ] ?? ''
                ),
                $prop_nombre_usuario
            ) === 0
        ) {

            $usuarioPorRFC =
                $usuarioEncontrado;
        }


        if (
            strcasecmp(
                trim(
                    $usuarioEncontrado[
                        'correo_electronico'
                    ] ?? ''
                ),
                $prop_correo_electronico
            ) === 0
        ) {

            $usuarioPorCorreo =
                $usuarioEncontrado;
        }
    }



    /* =====================================================
       CASO 1
       RFC Y CORREO EXISTEN
       ===================================================== */

    if (
        $usuarioPorRFC &&
        $usuarioPorCorreo
    ) {


        /*
         * Los dos datos existen, pero debemos comprobar
         * que pertenezcan al MISMO usuario.
         */

        if (
            (int)$usuarioPorRFC['id_usuario'] !==
            (int)$usuarioPorCorreo['id_usuario']
        ) {

            errorEmpresa(
                'El RFC y el correo electrónico pertenecen a usuarios diferentes.',
                $db
            );
        }


        /*
         * RFC y correo pertenecen al mismo usuario.
         *
         * Ahora verificamos que realmente sea
         * un PROPIETARIO.
         */

        $rolPropietarioExistente =
            strtoupper(
                trim(
                    $usuarioPorRFC['rol'] ?? ''
                )
            );


        if (
            $rolPropietarioExistente !==
            'PROPIETARIO'
        ) {

            errorEmpresa(
                'El RFC y correo ingresados ya pertenecen a un usuario que no tiene rol de PROPIETARIO.',
                $db
            );
        }


        /*
         * El usuario existe y sí es propietario.
         */

        $propietarioExistente =
            $usuarioPorRFC;


        $tipoPropietario =
            'EXISTENTE';



    /* =====================================================
       CASO 2
       SOLO EXISTE EL RFC
       ===================================================== */

    } elseif ($usuarioPorRFC) {

        errorEmpresa(
            'El RFC / nombre de usuario ya está registrado con un correo electrónico diferente.',
            $db
        );



    /* =====================================================
       CASO 3
       SOLO EXISTE EL CORREO
       ===================================================== */

    } elseif ($usuarioPorCorreo) {

        errorEmpresa(
            'El correo electrónico ya está registrado con un RFC / nombre de usuario diferente.',
            $db
        );



    /* =====================================================
       CASO 4
       NO EXISTE RFC NI CORREO
       ===================================================== */

    } else {

        $tipoPropietario =
            'NUEVO';
    }



    /* =====================================================
       DETENCIÓN TEMPORAL PARA PRUEBAS

       IMPORTANTE:

       En esta fase todavía NO queremos crear la empresa.

       Primero verificaremos que la detección de propietarios
       nuevos / existentes funcione correctamente.

       Este bloque se quitará en la siguiente fase.
       ===================================================== */

    if (
        $tipoPropietario ===
        'NUEVO'
    ) {

        mensajeDeteccionPropietario(
            'Validación correcta. El RFC y correo no existen en usuarios. Este propietario deberá crearse como una cuenta nueva.',
            $db
        );
    }


    if (
        $tipoPropietario ===
        'EXISTENTE'
    ) {

        mensajeDeteccionPropietario(
            'Validación correcta. El propietario ya existe y podrá vincularse con la nueva empresa sin crear otra cuenta.',
            $db
        );
    }

}



/* =========================================================
   GUARDAR
   ========================================================= */

try {


    /* =====================================================
       ACTUALIZAR EMPRESA
       ===================================================== */

    if ($id_empresa > 0) {


        /* ADMIN */
        if ($rol === 'ADMIN') {

            $stmt =
                $db->conn->prepare(
                    "SELECT id_empresa

                     FROM empresas

                     WHERE id_empresa = :empresa

                     LIMIT 1"
                );


            $stmt->execute([
                ':empresa' => $id_empresa
            ]);


        /* PROPIETARIO MULTIEMPRESA */
        } elseif ($multiempresa === 1) {

            $stmt =
                $db->conn->prepare(
                    "SELECT e.id_empresa

                     FROM empresas e

                     INNER JOIN usuario_empresas ue
                        ON ue.id_empresa = e.id_empresa

                     WHERE e.id_empresa = :empresa
                     AND ue.id_usuario = :usuario

                     LIMIT 1"
                );


            $stmt->execute([
                ':empresa' => $id_empresa,
                ':usuario' => $id_usuario
            ]);


        /* PROPIETARIO INDIVIDUAL */
        } else {

            $stmt =
                $db->conn->prepare(
                    "SELECT id_empresa

                     FROM empresas

                     WHERE id_empresa = :empresa
                     AND id_empresa = :empresa_sesion

                     LIMIT 1"
                );


            $stmt->execute([
                ':empresa' => $id_empresa,
                ':empresa_sesion' =>
                    $id_empresa_sesion
            ]);
        }


        if (!$stmt->fetchColumn()) {

            errorEmpresa(
                'No tienes permiso para editar esta empresa.',
                $db,
                true
            );
        }


        /* ACTUALIZAR */

        $stmt =
            $db->conn->prepare(
                "UPDATE empresas SET

                    nombre_empresa = :nombre,
                    razon_social = :razon,
                    direccion_fiscal = :direccion,
                    responsable = :responsable

                 WHERE id_empresa = :empresa"
            );


        $stmt->execute([
            ':nombre' => $nombre_empresa,
            ':razon' => $razon_social,
            ':direccion' => $direccion_fiscal,
            ':responsable' => $responsable,
            ':empresa' => $id_empresa
        ]);



    /* =====================================================
       NUEVA EMPRESA
       ===================================================== */

    } else {


        $db->conn->beginTransaction();


        /* CREAR EMPRESA */

        $stmt =
            $db->conn->prepare(
                "INSERT INTO empresas (

                    nombre_empresa,
                    razon_social,
                    direccion_fiscal,
                    responsable

                 ) VALUES (

                    :nombre,
                    :razon,
                    :direccion,
                    :responsable

                 )"
            );


        $stmt->execute([
            ':nombre' => $nombre_empresa,
            ':razon' => $razon_social,
            ':direccion' => $direccion_fiscal,
            ':responsable' => $responsable
        ]);


        if ($stmt->rowCount() !== 1) {

            throw new Exception(
                'No fue posible insertar la empresa.'
            );
        }


        $nueva_empresa =
            (int)$db->conn->lastInsertId();



        /* =================================================
           SI LA CREA UN PROPIETARIO
           ================================================= */

        if ($rol === 'PROPIETARIO') {


            /*
             * Debe tener una empresa original.
             */

            if ($id_empresa_sesion <= 0) {

                throw new Exception(
                    'El propietario no tiene una empresa original asignada.'
                );
            }


            $stmt =
                $db->conn->prepare(
                    "SELECT id_empresa

                     FROM empresas

                     WHERE id_empresa = :empresa

                     LIMIT 1"
                );


            $stmt->execute([
                ':empresa' =>
                    $id_empresa_sesion
            ]);


            if (!$stmt->fetchColumn()) {

                throw new Exception(
                    'La empresa original del propietario ya no existe.'
                );
            }


            /*
             * Registrar:
             *
             * - empresa original
             * - empresa nueva
             */

            $stmt =
                $db->conn->prepare(
                    "INSERT IGNORE INTO usuario_empresas
                    (
                        id_usuario,
                        id_empresa
                    )
                    VALUES
                    (
                        :usuario,
                        :empresa_original
                    ),
                    (
                        :usuario,
                        :empresa_nueva
                    )"
                );


            $stmt->execute([
                ':usuario' => $id_usuario,
                ':empresa_original' =>
                    $id_empresa_sesion,
                ':empresa_nueva' =>
                    $nueva_empresa
            ]);


            /*
             * Convertir usuario a multiempresa.
             */

            $stmt =
                $db->conn->prepare(
                    "UPDATE usuarios

                     SET multiempresa = 1

                     WHERE id_usuario = :usuario"
                );


            $stmt->execute([
                ':usuario' => $id_usuario
            ]);
        }


        /*
         * Solo si TODO salió bien.
         */

        $db->conn->commit();


        if ($rol === 'PROPIETARIO') {

            $_SESSION['multiempresa'] = 1;

            $multiempresa = 1;
        }
    }



} catch (Throwable $e) {


    if ($db->conn->inTransaction()) {

        $db->conn->rollBack();
    }


    $mensaje =
        'La base de datos rechazó la operación.';


    if (
        $e instanceof PDOException &&
        (string)$e->getCode() === '23000'
    ) {

        $mensaje =
            'La empresa no pudo guardarse porque existe información duplicada o relacionada de forma no válida.';
    }


    errorEmpresa(
        $mensaje,
        $db
    );
}



/* =========================================================
   RECARGAR TABLA
   ========================================================= */

if ($rol === 'ADMIN') {

    $sql =
        "SELECT *
         FROM empresas
         ORDER BY id_empresa DESC";


} elseif ($multiempresa === 1) {

    $sql =
        "SELECT e.*

         FROM empresas e

         INNER JOIN usuario_empresas ue
            ON ue.id_empresa = e.id_empresa

         WHERE ue.id_usuario = $id_usuario

         ORDER BY e.id_empresa DESC";


} elseif ($id_empresa_sesion > 0) {

    $sql =
        "SELECT *

         FROM empresas

         WHERE id_empresa = $id_empresa_sesion";


} else {

    $sql =
        "SELECT *

         FROM empresas

         WHERE 1 = 0";
}


$datos2 =
    $db->obtenerRegistros($sql);


include "../empresas/tabla.php";


$db->desconectar();

?>