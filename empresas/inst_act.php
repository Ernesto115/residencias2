<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once "../db/db.php";
$db = new db();
$db->conectar();
function errorEmpresa($mensaje, $db, $cerrar = false)
{
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
function generarContrasenaTemporal($longitud = 12)
{
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minus = 'abcdefghijkmnopqrstuvwxyz';
    $nums  = '23456789';
    $simb  = '!@#$%&*?';
    $todos =
        $mayus .
        $minus .
        $nums .
        $simb;
    $chars = [
        $mayus[
            random_int(
                0,
                strlen($mayus) - 1
            )
        ],
        $minus[
            random_int(
                0,
                strlen($minus) - 1
            )
        ],
        $nums[
            random_int(
                0,
                strlen($nums) - 1
            )
        ],
        $simb[
            random_int(
                0,
                strlen($simb) - 1
            )
        ]
    ];
    while (
        count($chars) <
        $longitud
    ) {
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
    return implode(
        '',
        $chars
    );
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
$id_usuario =
    (int)(
        $_SESSION['id_usuario'] ?? 0
    );
$id_empresa_sesion =
    (int)(
        $_SESSION['id_empresa'] ?? 0
    );
$multiempresa =
    (int)(
        $_SESSION['multiempresa'] ?? 0
    );
if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {
    errorEmpresa(
        'Método de solicitud no permitido.',
        $db
    );
}
if (
    !in_array(
        $rol,
        [
            'ADMIN',
            'PROPIETARIO'
        ],
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
    $rol === 'ADMIN' &&
    $id_empresa <= 0;
/* =========================================================
   VALIDACIONES EMPRESA
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
   VALIDACIONES PROPIETARIO
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
            $db
        );
    }
    errorEmpresa(
        'Ya existe una empresa con esa razón social.',
        $db
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
                $db
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
                $db
            );
        }
        $propietarioExistente =
            $usuarioPorRFC;
        $tipoPropietario =
            'EXISTENTE';
    } elseif ($usuarioPorRFC) {
        errorEmpresa(
            'El RFC / nombre de usuario ya está registrado con un correo electrónico diferente.',
            $db
        );
    } elseif ($usuarioPorCorreo) {
        errorEmpresa(
            'El correo electrónico ya está registrado con un RFC / nombre de usuario diferente.',
            $db
        );
    } else {
        $tipoPropietario =
            'NUEVO';
    }
}
$contrasenaTemporalGenerada =
    null;
/* =========================================================
   GUARDAR
   ========================================================= */
try {
    /* =====================================================
       EDITAR EMPRESA
       ===================================================== */
    if ($id_empresa > 0) {
        if ($rol === 'ADMIN') {
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
        } elseif (
            $multiempresa === 1
        ) {
            $stmt =
                $db->conn->prepare(
                    "SELECT e.id_empresa
                     FROM empresas e
                     INNER JOIN usuario_empresas ue
                        ON ue.id_empresa =
                           e.id_empresa
                     WHERE e.id_empresa =
                           :empresa
                     AND ue.id_usuario =
                         :usuario
                     LIMIT 1"
                );
            $stmt->execute([
                ':empresa' =>
                    $id_empresa,
                ':usuario' =>
                    $id_usuario
            ]);
        } else {
            $stmt =
                $db->conn->prepare(
                    "SELECT id_empresa
                     FROM empresas
                     WHERE id_empresa =
                           :empresa
                     AND id_empresa =
                         :empresa_sesion
                     LIMIT 1"
                );
            $stmt->execute([
                ':empresa' =>
                    $id_empresa,
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
        $stmt =
            $db->conn->prepare(
                "UPDATE empresas SET
                    nombre_empresa =
                        :nombre,
                    razon_social =
                        :razon,
                    direccion_fiscal =
                        :direccion,
                    responsable =
                        :responsable
                 WHERE id_empresa =
                       :empresa"
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
           ADMIN: CREAR O VINCULAR PROPIETARIO
           ================================================= */
        if ($rol === 'ADMIN') {
            /* PROPIETARIO NUEVO */
            if (
                $tipoPropietario ===
                'NUEVO'
            ) {
                $contrasenaTemporalGenerada =
                    generarContrasenaTemporal(
                        12
                    );
                $hashContrasena =
                    password_hash(
                        $contrasenaTemporalGenerada,
                        PASSWORD_DEFAULT
                    );
                if (
                    $hashContrasena ===
                    false
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
                            multiempresa
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
                            0
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
                        $hashContrasena,
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
            /* PROPIETARIO EXISTENTE */
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
                             WHERE id_empresa =
                                   :empresa
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
                            "INSERT IGNORE INTO
                             usuario_empresas
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
                        ':usuario' =>
                            $idPropietario,
                        ':empresa_original' =>
                            $empresaOriginal,
                        ':empresa_nueva' =>
                            $nueva_empresa
                    ]);
                } else {
                    $stmt =
                        $db->conn->prepare(
                            "INSERT IGNORE INTO
                             usuario_empresas
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
                         WHERE id_usuario =
                               :usuario"
                    );
                $stmt->execute([
                    ':usuario' =>
                        $idPropietario
                ]);
            } else {
                throw new Exception(
                    'No fue posible determinar el tipo de propietario.'
                );
            }
        }
        /* =================================================
           PROPIETARIO CREA OTRA EMPRESA
           ================================================= */
        if (
            $rol ===
            'PROPIETARIO'
        ) {
            if (
                $id_empresa_sesion <= 0
            ) {
                throw new Exception(
                    'El propietario no tiene una empresa original asignada.'
                );
            }
            $stmt =
                $db->conn->prepare(
                    "SELECT id_empresa
                     FROM empresas
                     WHERE id_empresa =
                           :empresa
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
            $stmt =
                $db->conn->prepare(
                    "INSERT IGNORE INTO
                     usuario_empresas
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
                ':usuario' =>
                    $id_usuario,
                ':empresa_original' =>
                    $id_empresa_sesion,
                ':empresa_nueva' =>
                    $nueva_empresa
            ]);
            $stmt =
                $db->conn->prepare(
                    "UPDATE usuarios
                     SET multiempresa = 1
                     WHERE id_usuario =
                           :usuario"
                );
            $stmt->execute([
                ':usuario' =>
                    $id_usuario
            ]);
        }
        /* TODO SALIÓ BIEN */
        $db->conn->commit();
        if (
            $rol ===
            'PROPIETARIO'
        ) {
            $_SESSION[
                'multiempresa'
            ] = 1;
            $multiempresa = 1;
        }
        /* =========================================================
           MOSTRAR CONTRASEÑA TEMPORAL
           ========================================================= */
        if (
            $rol === 'ADMIN' &&
            $tipoPropietario === 'NUEVO' &&
            $contrasenaTemporalGenerada !== null
        ) {
            $usuarioSeguro = htmlspecialchars(
                $prop_nombre_usuario,
                ENT_QUOTES,
                'UTF-8'
            );
            $contrasenaSegura = htmlspecialchars(
                $contrasenaTemporalGenerada,
                ENT_QUOTES,
                'UTF-8'
            );
            $htmlMensaje =
                '<div style="text-align:left;font-size:16px;line-height:1.6;">' .
                    '<p style="margin:0 0 18px 0;text-align:center;">' .
                        'La empresa y el propietario fueron creados correctamente.' .
                    '</p>' .
                    '<div style="margin-bottom:14px;padding:12px 15px;border-radius:10px;background:#f3f4f6;">' .
                        '<div style="font-size:13px;color:#6b7280;margin-bottom:4px;font-weight:600;">USUARIO</div>' .
                        '<div style="font-size:18px;font-weight:800;color:#111827;word-break:break-all;">' .
                            $usuarioSeguro .
                        '</div>' .
                    '</div>' .
                    '<div style="margin-bottom:16px;padding:12px 15px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;">' .
                        '<div style="font-size:13px;color:#1d4ed8;margin-bottom:4px;font-weight:600;">CONTRASEÑA TEMPORAL</div>' .
                        '<div style="font-size:20px;font-weight:800;color:#1e40af;font-family:monospace;letter-spacing:1px;word-break:break-all;">' .
                            $contrasenaSegura .
                        '</div>' .
                    '</div>' .
                    '<p style="margin:0;font-size:14px;text-align:center;color:#6b7280;">' .
                        'Guarda esta contraseña para realizar el primer inicio de sesión.' .
                    '</p>' .
                '</div>';
            $mensaje = json_encode(
                $htmlMensaje,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            echo "<script>
                if(typeof Swal !== 'undefined'){
                    Swal.fire({
                        icon:'success',
                        title:'Propietario creado',
                        html:$mensaje,
                        confirmButtonText:'Entendido',
                        confirmButtonColor:'#1e40af',
                        width:600
                    });
                }
            </script>";
        }
    }
} catch (Throwable $e) {
    if (
        $db->conn
            ->inTransaction()
    ) {
        $db->conn
            ->rollBack();
    }
    $mensaje =
        'La base de datos rechazó la operación.';
    if (
        $e instanceof
            PDOException &&
        (string)$e->getCode() ===
            '23000'
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
} elseif (
    $multiempresa === 1
) {
    $sql =
        "SELECT e.*
         FROM empresas e
         INNER JOIN usuario_empresas ue
            ON ue.id_empresa =
               e.id_empresa
         WHERE ue.id_usuario =
               $id_usuario
         ORDER BY e.id_empresa DESC";
} elseif (
    $id_empresa_sesion > 0
) {
    $sql =
        "SELECT *
         FROM empresas
         WHERE id_empresa =
               $id_empresa_sesion";
} else {
    $sql =
        "SELECT *
         FROM empresas
         WHERE 1 = 0";
}
$datos2 =
    $db->obtenerRegistros(
        $sql
    );
include "../empresas/tabla.php";
$db->desconectar();
?>
