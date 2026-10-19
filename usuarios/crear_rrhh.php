
<?php

/* =========================================================
   COSAS NETTO2 - PASO 2D-D.4
   VALIDAR EMPRESA AUTORIZADA PARA CREAR RRHH

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
   8. GUARDADO TODAVÍA BLOQUEADO

   Próximos pasos:
   - 2D-E: Contraseña temporal segura.
   - 2D-F: Cambio obligatorio de contraseña.
   - 2D-G: Mostrar contraseña una sola vez.
   - 2D-D.5: Integración y pruebas, antes de habilitar altas.

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
        'tipo_propietario' => $esMultiempresa ? 'MULTIEMPRESA' : 'UNICA'
    ]
);
