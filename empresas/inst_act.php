<?php
if (session_status() === PHP_SESSION_NONE) session_start();

include_once "../db/db.php";

$db = new db();
$db->conectar();

function respuestaEmpresa(array $datos): string {
    return '<!-- EMPRESA_RESPUESTA:' .
        json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) .
        ' -->';
}

function errorEmpresa($mensaje, $db, $cerrar = false, $codigo = 422) {
    http_response_code($codigo);

    echo respuestaEmpresa([
        'ok' => false,
        'mensaje' => $mensaje,
        'cerrar' => $cerrar
    ]);

    $db->desconectar();
    exit;
}

function generarContrasenaTemporal($longitud = 12) {
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minus = 'abcdefghijkmnopqrstuvwxyz';
    $nums = '23456789';
    $simb = '!@#$%&*?';

    $todos =
        $mayus .
        $minus .
        $nums .
        $simb;

    $chars = [
        $mayus[random_int(0, strlen($mayus) - 1)],
        $minus[random_int(0, strlen($minus) - 1)],
        $nums[random_int(0, strlen($nums) - 1)],
        $simb[random_int(0, strlen($simb) - 1)]
    ];

    while (count($chars) < $longitud) {
        $chars[] =
            $todos[
                random_int(
                    0,
                    strlen($todos) - 1
                )
            ];
    }

    for (
        $i = count($chars) - 1;
        $i > 0;
        $i--
    ) {
        $j =
            random_int(
                0,
                $i
            );

        [
            $chars[$i],
            $chars[$j]
        ] = [
            $chars[$j],
            $chars[$i]
        ];
    }

    return implode('', $chars);
}


/* =========================================================
   SESIÓN
   ========================================================= */

$rol =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}


/* =========================================================
   SOLO POST
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {
    errorEmpresa(
        'Método de solicitud no permitido.',
        $db,
        false,
        405
    );
}


/* =========================================================
   SOLO ADMIN MODIFICA EMPRESAS
   ========================================================= */

if ($rol !== 'ADMIN') {
    errorEmpresa(
        'Las empresas son de solo consulta para propietarios. Solo el administrador puede crear o modificar empresas.',
        $db,
        true,
        403
    );
}


/* =========================================================
   DATOS EMPRESA
   ========================================================= */

$id_empresa =
    (int)(
        $_POST['id_empresa'] ?? 0
    );

$nombre_empresa =
    trim(
        $_POST['nombre_empresa'] ?? ''
    );

$razon_social =
    trim(
        $_POST['razon_social'] ?? ''
    );

$direccion_fiscal =
    trim(
        $_POST['direccion_fiscal'] ?? ''
    );

$responsable =
    trim(
        $_POST['responsable'] ?? ''
    );


/* =========================================================
   DATOS PROPIETARIO
   ========================================================= */

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


$requiereDatosPropietario =
    $id_empresa <= 0;


/* =========================================================
   VALIDAR EMPRESA
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
   VALIDAR PROPIETARIO
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
    ':nombre' =>
        $nombre_empresa,

    ':razon' =>
        $razon_social,

    ':id' =>
        $id_empresa
]);


$duplicada =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if ($duplicada) {

    if (
        strcasecmp(
            trim(
                $duplicada[
                    'nombre_empresa'
                ]
            ),
            $nombre_empresa
        ) === 0
    ) {
        errorEmpresa(
            'Ya existe una empresa con ese nombre comercial.',
            $db,
            false,
            409
        );
    }


    errorEmpresa(
        'Ya existe una empresa con esa razón social.',
        $db,
        false,
        409
    );
}


/* =========================================================
   DETECTAR PROPIETARIO
   ========================================================= */

$propietarioExistente =
    null;

$tipoPropietario =
    null;


if ($requiereDatosPropietario) {

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

             WHERE
                nombre_usuario = :usuario
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


    $usuarioPorRFC =
        null;

    $usuarioPorCorreo =
        null;


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


    if (
        $usuarioPorRFC &&
        $usuarioPorCorreo
    ) {

        if (
            (int)$usuarioPorRFC[
                'id_usuario'
            ] !==
            (int)$usuarioPorCorreo[
                'id_usuario'
            ]
        ) {
            errorEmpresa(
                'El RFC y el correo electrónico pertenecen a usuarios diferentes.',
                $db,
                false,
                409
            );
        }


        if (
            strtoupper(
                trim(
                    $usuarioPorRFC[
                        'rol'
                    ] ?? ''
                )
            ) !==
            'PROPIETARIO'
        ) {
            errorEmpresa(
                'El RFC y correo ingresados ya pertenecen a un usuario que no tiene rol de PROPIETARIO.',
                $db,
                false,
                409
            );
        }


        $propietarioExistente =
            $usuarioPorRFC;

        $tipoPropietario =
            'EXISTENTE';


    } elseif (
        $usuarioPorRFC
    ) {

        errorEmpresa(
            'El RFC / nombre de usuario ya está registrado con un correo electrónico diferente.',
            $db,
            false,
            409
        );


    } elseif (
        $usuarioPorCorreo
    ) {

        errorEmpresa(
            'El correo electrónico ya está registrado con un RFC / nombre de usuario diferente.',
            $db,
            false,
            409
        );


    } else {

        $tipoPropietario =
            'NUEVO';
    }
}


$respuesta =
    null;


/* =========================================================
   GUARDAR
   ========================================================= */

try {


    /* =====================================================
       EDITAR EMPRESA
       ===================================================== */

    if ($id_empresa > 0) {

        $stmt =
            $db->conn->prepare(

                "SELECT id_empresa

                 FROM empresas

                 WHERE id_empresa = :empresa

                 LIMIT 1"
            );


        $stmt->execute([
            ':empresa' =>
                $id_empresa
        ]);


        if (
            !$stmt->fetchColumn()
        ) {
            errorEmpresa(
                'La empresa seleccionada no existe.',
                $db,
                true,
                404
            );
        }


        $stmt =
            $db->conn->prepare(

                "UPDATE empresas

                 SET
                    nombre_empresa = :nombre,
                    razon_social = :razon,
                    direccion_fiscal = :direccion,
                    responsable = :responsable

                 WHERE id_empresa = :empresa"
            );


        $stmt->execute([
            ':nombre' =>
                $nombre_empresa,

            ':razon' =>
                $razon_social,

            ':direccion' =>
                $direccion_fiscal,

            ':responsable' =>
                $responsable,

            ':empresa' =>
                $id_empresa
        ]);


        $respuesta = [
            'ok' => true,
            'accion' =>
                'EDITADA',
            'mensaje' =>
                'Empresa actualizada correctamente.'
        ];


    /* =====================================================
       NUEVA EMPRESA
       ===================================================== */

    } else {


        $db->conn
            ->beginTransaction();


        $stmt =
            $db->conn->prepare(

                "INSERT INTO empresas
                (
                    nombre_empresa,
                    razon_social,
                    direccion_fiscal,
                    responsable
                )

                VALUES
                (
                    :nombre,
                    :razon,
                    :direccion,
                    :responsable
                )"
            );


        $stmt->execute([
            ':nombre' =>
                $nombre_empresa,

            ':razon' =>
                $razon_social,

            ':direccion' =>
                $direccion_fiscal,

            ':responsable' =>
                $responsable
        ]);


        if (
            $stmt->rowCount() !== 1
        ) {
            throw new Exception(
                'No fue posible insertar la empresa.'
            );
        }


        $nueva_empresa =
            (int)$db->conn
                ->lastInsertId();


        /* =================================================
           PROPIETARIO NUEVO
           ================================================= */

        if (
            $tipoPropietario ===
            'NUEVO'
        ) {

            $temporal =
                generarContrasenaTemporal(
                    12
                );


            $hash =
                password_hash(
                    $temporal,
                    PASSWORD_DEFAULT
                );


            if (
                $hash === false
            ) {
                throw new Exception(
                    'No fue posible proteger la contraseña temporal del propietario.'
                );
            }


            $stmt =
                $db->conn->prepare(

                    "INSERT INTO usuarios
                    (
                        nombre_usuario,
                        nombres,
                        primer_apellido,
                        segundo_apellido,
                        contrasena,
                        rol,
                        correo_electronico,
                        id_empresa,
                        multiempresa,
                        requiere_cambio_contrasena
                    )

                    VALUES
                    (
                        :usuario,
                        :nombres,
                        :apellido1,
                        :apellido2,
                        :contrasena,
                        'PROPIETARIO',
                        :correo,
                        :empresa,
                        0,
                        1
                    )"
                );


            $stmt->execute([
                ':usuario' =>
                    $prop_nombre_usuario,

                ':nombres' =>
                    $prop_nombres,

                ':apellido1' =>
                    $prop_primer_apellido,

                ':apellido2' =>
                    $prop_segundo_apellido,

                ':contrasena' =>
                    $hash,

                ':correo' =>
                    $prop_correo_electronico,

                ':empresa' =>
                    $nueva_empresa
            ]);


            if (
                $stmt->rowCount() !== 1
            ) {
                throw new Exception(
                    'No fue posible crear la cuenta del propietario.'
                );
            }


            $respuesta = [
                'ok' =>
                    true,

                'accion' =>
                    'PROPIETARIO_NUEVO',

                'usuario' =>
                    $prop_nombre_usuario,

                'contrasena_temporal' =>
                    $temporal,

                'empresa' =>
                    $nombre_empresa
            ];


        /* =================================================
           PROPIETARIO EXISTENTE
           ================================================= */

        } elseif (
            $tipoPropietario ===
            'EXISTENTE'
        ) {

            $idPropietario =
                (int)(
                    $propietarioExistente[
                        'id_usuario'
                    ] ?? 0
                );


            $esMultiempresa =
                (int)(
                    $propietarioExistente[
                        'multiempresa'
                    ] ?? 0
                );


            $empresaOriginal =
                (int)(
                    $propietarioExistente[
                        'id_empresa'
                    ] ?? 0
                );


            if (
                $idPropietario <= 0
            ) {
                throw new Exception(
                    'No fue posible identificar al propietario existente.'
                );
            }


            if (
                $esMultiempresa !== 1
            ) {

                if (
                    $empresaOriginal <= 0
                ) {
                    throw new Exception(
                        'El propietario existente no tiene una empresa original asignada.'
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
                        $empresaOriginal
                ]);


                if (
                    !$stmt->fetchColumn()
                ) {
                    throw new Exception(
                        'La empresa original del propietario existente ya no existe.'
                    );
                }


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
                            :original
                        ),
                        (
                            :usuario,
                            :nueva
                        )"
                    );


                $stmt->execute([
                    ':usuario' =>
                        $idPropietario,

                    ':original' =>
                        $empresaOriginal,

                    ':nueva' =>
                        $nueva_empresa
                ]);


            } else {


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
                            :empresa
                        )"
                    );


                $stmt->execute([
                    ':usuario' =>
                        $idPropietario,

                    ':empresa' =>
                        $nueva_empresa
                ]);
            }


            $stmt =
                $db->conn->prepare(

                    "UPDATE usuarios

                     SET multiempresa = 1

                     WHERE id_usuario = :usuario"
                );


            $stmt->execute([
                ':usuario' =>
                    $idPropietario
            ]);


            $respuesta = [
                'ok' =>
                    true,

                'accion' =>
                    'PROPIETARIO_EXISTENTE',

                'usuario' =>
                    $prop_nombre_usuario,

                'empresa' =>
                    $nombre_empresa
            ];


        } else {

            throw new Exception(
                'No fue posible determinar el tipo de propietario.'
            );
        }


        $db->conn
            ->commit();
    }


} catch (Throwable $e) {


    if (
        $db->conn
            ->inTransaction()
    ) {
        $db->conn
            ->rollBack();
    }


    if (
        $e instanceof PDOException &&
        (string)$e->getCode() ===
        '23000'
    ) {

        errorEmpresa(
            'La empresa no pudo guardarse porque existe información duplicada o relacionada de forma no válida.',
            $db,
            false,
            409
        );
    }


    errorEmpresa(
        'La base de datos rechazó la operación.',
        $db,
        false,
        500
    );
}


/* =========================================================
   RESPUESTA PARA FUNCIONES.JS
   ========================================================= */

echo respuestaEmpresa(

    $respuesta ?? [
        'ok' =>
            true,

        'accion' =>
            'GUARDADA',

        'mensaje' =>
            'Registro guardado correctamente.'
    ]
);


/* =========================================================
   RECARGAR TABLA
   ========================================================= */

$datos2 =
    $db->obtenerRegistros(

        "SELECT *

         FROM empresas

         ORDER BY id_empresa DESC"
    );


include "../empresas/tabla.php";


$db->desconectar();

?>