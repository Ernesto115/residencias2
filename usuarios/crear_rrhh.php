
<?php

/* =========================================================
   COSAS NETTO2 - PASO 2D-G.1C.1
   VALIDAR TOKEN CSRF RRHH SIN GUARDAR

   IMPORTANTE:
   - No crea usuarios todavía.
   - No edita ni elimina cuentas.
   - No modifica empresas ni contraseñas.
   - Mantiene el guardado bloqueado hasta los siguientes pasos.
   ========================================================= */

require_once __DIR__ . '/../configuracion/sesion.php';
verificarSesion();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

/* =========================================================
   RESPUESTAS CONTROLADAS
   ========================================================= */

function responderCrearRRHH($codigoHttp, $estado, $mensaje, $extra = [])
{
    http_response_code($codigoHttp);

    echo json_encode(
        array_merge(
            [
                'ok' => false,
                'estado' => $estado,
                'mensaje' => $mensaje
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/* =========================================================
   2D-E.1 - GENERADOR DE CONTRASEÑA TEMPORAL

   - Usa random_int() para seleccionar los caracteres.
   - Genera exactamente 12 caracteres.
   - Incluye mayúscula, minúscula, número y símbolo.
   - Esta función NO guarda ni muestra contraseñas.
   ========================================================= */

function generarContrasenaTemporalRRHH(): string
{
    $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minusculas = 'abcdefghijkmnopqrstuvwxyz';
    $numeros = '23456789';
    $simbolos = '!@#$%&*?';

    $elegir = static function (string $grupo): string {
        return $grupo[random_int(0, strlen($grupo) - 1)];
    };

    // Garantizamos por lo menos uno de cada tipo.
    $caracteres = [
        $elegir($mayusculas),
        $elegir($minusculas),
        $elegir($numeros),
        $elegir($simbolos)
    ];

    $todos = $mayusculas . $minusculas . $numeros . $simbolos;

    while (count($caracteres) < 12) {
        $caracteres[] = $elegir($todos);
    }

    // Mezcla Fisher-Yates con selecciones criptográficas.
    for ($i = count($caracteres) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $temporal = $caracteres[$i];
        $caracteres[$i] = $caracteres[$j];
        $caracteres[$j] = $temporal;
    }

    return implode('', $caracteres);
}


/* =========================================================
   1. SOLO SOLICITUDES POST
   ========================================================= */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderCrearRRHH(
        405,
        'METODO_NO_PERMITIDO',
        'Esta operación solo acepta solicitudes POST.'
    );
}

/* =========================================================
   2. SOLO PROPIETARIO
   ========================================================= */

$idSesion = (int)($_SESSION['id_usuario'] ?? 0);
$rolSesion = strtoupper(trim((string)($_SESSION['rol'] ?? '')));

if ($idSesion <= 0 || $rolSesion !== 'PROPIETARIO') {
    responderCrearRRHH(
        403,
        'ACCESO_DENEGADO',
        'Solo un propietario puede solicitar la creación de RRHH.'
    );
}

/* =========================================================
   3. PROHIBIR EDICIÓN DE USUARIOS

   Solo aceptamos identificador vacío o "0".
   ========================================================= */

$idSolicitado = $_POST['id_usuario'] ?? '';

if (
    !is_string($idSolicitado) ||
    !in_array($idSolicitado, ['', '0'], true)
) {
    responderCrearRRHH(
        403,
        'EDICION_PROHIBIDA',
        'Este archivo solo permite preparar cuentas nuevas de RRHH.'
    );
}

/* =========================================================
   3A. VALIDAR TOKEN CSRF DEL NUEVO RRHH

   - La sesion debe contener un token creado por frm.php.
   - El POST debe enviar el mismo token.
   - Comparamos mediante hash_equals().
   - Se comprueba ANTES de consultar MySQL.
   - El guardado sigue bloqueado.
   ========================================================= */

$tokenSesionRRHH = $_SESSION['csrf_crear_rrhh'] ?? null;
$tokenRecibidoRRHH = $_POST['csrf_token_rrhh'] ?? null;

if (
    !is_string($tokenSesionRRHH) ||
    $tokenSesionRRHH === '' ||
    !is_string($tokenRecibidoRRHH) ||
    $tokenRecibidoRRHH === '' ||
    !hash_equals($tokenSesionRRHH, $tokenRecibidoRRHH)
) {
    responderCrearRRHH(
        403,
        'CSRF_INVALIDO',
        'La solicitud no tiene un token de seguridad valido. Vuelve a abrir el formulario de RRHH.'
    );
}

/* =========================================================
   4. REVISAR PROPIETARIO ACTIVO EN MYSQL

   Consultamos rol, id_empresa y multiempresa en la BD.
   La sesión no determina qué empresa puede asignarse.
   ========================================================= */

require_once __DIR__ . '/../DB/db.php';

$dbRRHH = new db();
$propietario = null;
$empresaAutorizada = null;
$rechazoEmpresa = null;
$errorInterno = false;
$esMultiempresa = false;

try {
    $dbRRHH->conectar();

    if (!($dbRRHH->conn instanceof PDO)) {
        throw new RuntimeException('No fue posible conectar con MySQL.');
    }

    $stmt = $dbRRHH->conn->prepare(
        'SELECT rol, id_empresa, multiempresa
         FROM usuarios
         WHERE id_usuario = :id_usuario
           AND estatus = 1
         LIMIT 1'
    );

    $stmt->execute([
        ':id_usuario' => $idSesion
    ]);

    $propietario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        $propietario &&
        strtoupper(trim((string)$propietario['rol'])) === 'PROPIETARIO'
    ) {
        $esMultiempresa = (int)$propietario['multiempresa'] === 1;

        /* =================================================
           5. PROPIETARIO MULTIEMPRESA

           La empresa enviada debe estar relacionada con
           este propietario mediante usuario_empresas.
           ================================================= */

        if ($esMultiempresa) {
            $empresaRecibida = $_POST['id_empresa'] ?? null;

            if ($empresaRecibida === null || $empresaRecibida === '') {
                $rechazoEmpresa = [
                    422,
                    'EMPRESA_REQUERIDA',
                    'Debes seleccionar una de tus empresas autorizadas.'
                ];
            } else {
                $idEmpresaSolicitada =
                    is_string($empresaRecibida)
                        ? filter_var(
                            $empresaRecibida,
                            FILTER_VALIDATE_INT,
                            ['options' => ['min_range' => 1]]
                        )
                        : false;

                if ($idEmpresaSolicitada === false) {
                    $rechazoEmpresa = [
                        422,
                        'EMPRESA_INVALIDA',
                        'La empresa seleccionada no es válida.'
                    ];
                } else {
                    $stmt = $dbRRHH->conn->prepare(
                        'SELECT e.id_empresa, e.nombre_empresa
                         FROM usuario_empresas ue
                         INNER JOIN empresas e
                           ON e.id_empresa = ue.id_empresa
                         WHERE ue.id_usuario = :id_usuario
                           AND ue.id_empresa = :id_empresa
                         LIMIT 1'
                    );

                    $stmt->execute([
                        ':id_usuario' => $idSesion,
                        ':id_empresa' => $idEmpresaSolicitada
                    ]);

                    $empresaAutorizada = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$empresaAutorizada) {
                        $rechazoEmpresa = [
                            403,
                            'EMPRESA_NO_AUTORIZADA',
                            'No tienes autorización para asignar RRHH a esa empresa.'
                        ];
                    }
                }
            }

        /* =================================================
           6. PROPIETARIO DE UNA SOLA EMPRESA

           La empresa se obtiene de usuarios.id_empresa.
           No dependemos de un id_empresa enviado por POST.
           Si se envía uno diferente, se rechaza.
           ================================================= */

        } else {
            $idEmpresaAsignada = (int)($propietario['id_empresa'] ?? 0);
            $empresaRecibida = $_POST['id_empresa'] ?? null;

            if ($idEmpresaAsignada <= 0) {
                $rechazoEmpresa = [
                    403,
                    'EMPRESA_NO_DISPONIBLE',
                    'Tu cuenta no tiene una empresa válida asignada.'
                ];
            } elseif (
                $empresaRecibida !== null &&
                $empresaRecibida !== '' &&
                (
                    !is_string($empresaRecibida) ||
                    $empresaRecibida !== (string)$idEmpresaAsignada
                )
            ) {
                $rechazoEmpresa = [
                    403,
                    'EMPRESA_NO_AUTORIZADA',
                    'No puedes asignar RRHH a una empresa distinta de la tuya.'
                ];
            } else {
                $stmt = $dbRRHH->conn->prepare(
                    'SELECT id_empresa, nombre_empresa
                     FROM empresas
                     WHERE id_empresa = :id_empresa
                     LIMIT 1'
                );

                $stmt->execute([
                    ':id_empresa' => $idEmpresaAsignada
                ]);

                $empresaAutorizada = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$empresaAutorizada) {
                    $rechazoEmpresa = [
                        403,
                        'EMPRESA_NO_DISPONIBLE',
                        'La empresa asignada a tu cuenta no está disponible.'
                    ];
                }
            }
        }
    }

} catch (Throwable $e) {
    error_log('Error en crear_rrhh.php: ' . $e->getMessage());
    $errorInterno = true;
} finally {
    $dbRRHH->desconectar();
}

if ($errorInterno) {
    responderCrearRRHH(
        500,
        'ERROR_INTERNO',
        'No fue posible verificar los permisos y la empresa.'
    );
}

if (
    !$propietario ||
    strtoupper(trim((string)$propietario['rol'])) !== 'PROPIETARIO'
) {
    responderCrearRRHH(
        403,
        'ACCESO_DENEGADO',
        'La cuenta ya no tiene autorización para crear RRHH.'
    );
}

if ($rechazoEmpresa !== null) {
    responderCrearRRHH(
        $rechazoEmpresa[0],
        $rechazoEmpresa[1],
        $rechazoEmpresa[2]
    );
}

if (!$empresaAutorizada) {
    responderCrearRRHH(
        403,
        'EMPRESA_NO_AUTORIZADA',
        'No fue posible validar una empresa autorizada.'
    );
}

/* =========================================================
   7. ROL Y EMPRESA VERIFICADOS POR EL SERVIDOR

   No confiamos en $_POST['rol'].
   La empresa autorizada procede de la consulta MySQL.
   ========================================================= */

$rolNuevoUsuario = 'RRHH';
$idEmpresaAutorizada = (int)$empresaAutorizada['id_empresa'];
$nombreEmpresaAutorizada = (string)$empresaAutorizada['nombre_empresa'];

/* =========================================================
   2D-G.1A - VALIDAR DATOS DEL NUEVO RRHH (SIN GUARDAR)

   Validamos los campos en PHP, aunque el navegador ya tenga
   validaciones HTML. No realizamos INSERT ni UPDATE.

   NOTA: La proteccion CSRF se incorporara y probara
   antes de habilitar cualquier creacion real.
   ========================================================= */

$camposNombreRRHH = [
    'nombres' => 'nombres',
    'primer_apellido' => 'primer_apellido',
    'segundo_apellido' => 'segundo_apellido'
];

$camposInvalidosRRHH = [];
$datosNuevoRRHH = [];

foreach ($camposNombreRRHH as $clave => $etiqueta) {
    $recibido = $_POST[$clave] ?? null;

    if (!is_string($recibido)) {
        $camposInvalidosRRHH[] = $etiqueta;
        continue;
    }

    $valor = trim($recibido);
    $longitud = preg_match_all('/./us', $valor);

    // Aceptamos letras con acentos, espacios, guiones y apostrofes.
    // Rechazamos digitos, etiquetas HTML y caracteres de control.
    if (
        $valor === '' ||
        $longitud === false ||
        $longitud > 50 ||
        preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\'\x{2019}-]*$/uD', $valor) !== 1
    ) {
        $camposInvalidosRRHH[] = $etiqueta;
        continue;
    }

    $datosNuevoRRHH[$clave] = $valor;
}

$nombreUsuarioRecibido = $_POST['nombre_usuario'] ?? null;

if (!is_string($nombreUsuarioRecibido)) {
    $camposInvalidosRRHH[] = 'nombre_usuario';
} else {
    $nombreUsuarioRRHH = strtoupper(trim($nombreUsuarioRecibido));

    // Campo VARCHAR(13): RFC / ID sin espacios ni caracteres especiales.
    if (
        preg_match('/^[A-Z0-9][A-Z0-9._-]{0,12}$/D', $nombreUsuarioRRHH) !== 1
    ) {
        $camposInvalidosRRHH[] = 'nombre_usuario';
    } else {
        $datosNuevoRRHH['nombre_usuario'] = $nombreUsuarioRRHH;
    }
}

$correoRecibido = $_POST['correo_electronico'] ?? null;

if (!is_string($correoRecibido)) {
    $camposInvalidosRRHH[] = 'correo_electronico';
} else {
    $correoRRHH = strtolower(trim($correoRecibido));

    if (
        $correoRRHH === '' ||
        strlen($correoRRHH) > 100 ||
        filter_var($correoRRHH, FILTER_VALIDATE_EMAIL) === false
    ) {
        $camposInvalidosRRHH[] = 'correo_electronico';
    } else {
        $datosNuevoRRHH['correo_electronico'] = $correoRRHH;
    }
}

if (!empty($camposInvalidosRRHH)) {
    responderCrearRRHH(
        422,
        'DATOS_INVALIDOS',
        'Revisa los datos personales y de acceso del nuevo RRHH.',
        ['campos_invalidos' => $camposInvalidosRRHH]
    );
}

/* =========================================================
   2D-G.1B - VERIFICAR USUARIO Y CORREO DUPLICADOS

   - Solo consultas SELECT.
   - Incluye cuentas activas e inactivas.
   - No permite reutilizar nombres de usuario ni correos.
   - Comparamos sin diferenciar mayusculas/minusculas.
   - La BD tambien conserva sus restricciones UNIQUE.
   - NO se realiza INSERT, UPDATE ni DELETE.
   ========================================================= */

$camposDuplicadosRRHH = [];
$errorConsultaDuplicadosRRHH = false;
$dbDuplicadosRRHH = new db();

try {
    $dbDuplicadosRRHH->conectar();

    if (!($dbDuplicadosRRHH->conn instanceof PDO)) {
        throw new RuntimeException('No fue posible conectar con MySQL.');
    }

    // Buscamos en todas las cuentas, sin filtrar estatus ni empresa.
    $stmtUsuarioDuplicado = $dbDuplicadosRRHH->conn->prepare(
        'SELECT 1
         FROM usuarios
         WHERE UPPER(nombre_usuario) = :nombre_usuario
         LIMIT 1'
    );

    $stmtUsuarioDuplicado->execute([
        ':nombre_usuario' => $datosNuevoRRHH['nombre_usuario']
    ]);

    if ($stmtUsuarioDuplicado->fetchColumn() !== false) {
        $camposDuplicadosRRHH[] = 'nombre_usuario';
    }

    $stmtCorreoDuplicado = $dbDuplicadosRRHH->conn->prepare(
        'SELECT 1
         FROM usuarios
         WHERE LOWER(correo_electronico) = :correo_electronico
         LIMIT 1'
    );

    $stmtCorreoDuplicado->execute([
        ':correo_electronico' => $datosNuevoRRHH['correo_electronico']
    ]);

    if ($stmtCorreoDuplicado->fetchColumn() !== false) {
        $camposDuplicadosRRHH[] = 'correo_electronico';
    }

} catch (Throwable $e) {
    // No incluir nombres de usuario, correos ni datos privados en el log.
    error_log('Error al comprobar duplicados en crear_rrhh.php.');
    $errorConsultaDuplicadosRRHH = true;
} finally {
    $dbDuplicadosRRHH->desconectar();
}

if ($errorConsultaDuplicadosRRHH) {
    responderCrearRRHH(
        500,
        'ERROR_CONSULTA_DUPLICADOS',
        'No fue posible comprobar la disponibilidad de los datos del RRHH.'
    );
}

if (!empty($camposDuplicadosRRHH)) {
    responderCrearRRHH(
        409,
        'DATOS_DUPLICADOS',
        'El nombre de usuario o el correo ya se encuentra registrado.',
        ['campos_duplicados' => $camposDuplicadosRRHH]
    );
}

/* =========================================================
   8. GENERAR CONTRASEÑA Y COMPROBAR SU HASH

   2D-E.2:
   - Generar contraseña aleatoria.
   - Validar los cuatro tipos de caracteres.
   - Calcular hash con PASSWORD_DEFAULT.
   - Comprobar coincidencia con password_verify().
   - No devolver ni almacenar contraseña ni hash.
   ========================================================= */

$errorCredenciales = false;

try {
    $contrasenaTemporal = generarContrasenaTemporalRRHH();

    $generacionValida =
        strlen($contrasenaTemporal) === 12 &&
        preg_match('/[A-Z]/', $contrasenaTemporal) === 1 &&
        preg_match('/[a-z]/', $contrasenaTemporal) === 1 &&
        preg_match('/[0-9]/', $contrasenaTemporal) === 1 &&
        preg_match('/[^A-Za-z0-9]/', $contrasenaTemporal) === 1;

    if (!$generacionValida) {
        throw new RuntimeException('La contraseña temporal no cumple los requisitos.');
    }

    $hashTemporal = password_hash(
        $contrasenaTemporal,
        PASSWORD_DEFAULT
    );

    if (
        !is_string($hashTemporal) ||
        $hashTemporal === '' ||
        !password_verify($contrasenaTemporal, $hashTemporal)
    ) {
        throw new RuntimeException('No se pudo comprobar el hash temporal.');
    }

} catch (Throwable $e) {
    // Nunca registrar aquí la contraseña ni su hash.
    error_log('Error al generar o verificar el hash temporal de RRHH.');
    $errorCredenciales = true;

} finally {
    // Liberamos las variables de esta prueba. No se guardan ni devuelven.
    unset($contrasenaTemporal, $hashTemporal);
}

if ($errorCredenciales) {
    responderCrearRRHH(
        500,
        'ERROR_CREDENCIALES_TEMPORALES',
        'No fue posible preparar las credenciales temporales.'
    );
}

/* =========================================================
   9. GUARDADO TODAVÍA BLOQUEADO

   Proximos pasos:
   - Crear el token CSRF en frm.php y probarlo.
   - 2D-G: Mostrar contraseña una sola vez al crear RRHH.
   - 2D-D.5: Integracion del guardado seguro.

   NO se ejecuta INSERT, UPDATE ni DELETE.
   ========================================================= */

responderCrearRRHH(
    403,
    'CREACION_PENDIENTE',
    'El rol y la empresa están autorizados. La creación de RRHH continúa bloqueada hasta terminar la seguridad.',
    [
        'rol_servidor' => $rolNuevoUsuario,
        'id_empresa_autorizada' => $idEmpresaAutorizada,
        'nombre_empresa_autorizada' => $nombreEmpresaAutorizada,
        'tipo_propietario' => $esMultiempresa ? 'MULTIEMPRESA' : 'UNICA',
        'datos_rrhh' => 'VALIDOS_SIN_GUARDAR',
        'duplicados_rrhh' => 'NO_DETECTADOS_SIN_GUARDAR',
        'generador_temporal' => 'VALIDADO_SIN_GUARDAR',
        'hash_temporal' => 'VERIFICADO_SIN_GUARDAR'
    ]
);
