<?php

/* =========================================================
   SESIÓN CENTRALIZADA
   ========================================================= */

require_once __DIR__ . "/../configuracion/sesion.php";

verificarSesion();


/* =========================================================
   SESIÓN Y SEGURIDAD
   ========================================================= */

$rolSesion =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if ($rolSesion === 'ADMINISTRADOR') {

    $rolSesion = 'ADMIN';
}


$esAdmin =
    $rolSesion === 'ADMIN';


$esPropietario =
    $rolSesion === 'PROPIETARIO';


/* =========================================================
   ROLES PERMITIDOS
   ========================================================= */

if (
    !$esAdmin &&
    !$esPropietario
) {

    http_response_code(403);

    exit(
        'Acceso no autorizado'
    );
}


/* =========================================================
   CONEXIÓN
   ========================================================= */

if (
    !isset($dbtransportistas) &&
    !isset($db)
) {

    require_once __DIR__ . "/../DB/db.php";


    $db =
        new db();


    $db->conectar();


    $conexionLocal =
        true;

} else {

    $db =
        $dbtransportistas
        ?? $db;


    $conexionLocal =
        false;
}


/* =========================================================
   PAGINACIÓN
   ========================================================= */

$paginaActual =
    max(
        1,
        (int)(
            $_GET['pagina']
            ?? 1
        )
    );


$porPagina =
    5;


/* =========================================================
   ADMINISTRADOR
   ========================================================= */

if ($esAdmin) {


    /* =====================================================
       FILTRO
       ===================================================== */

    $rolFiltro =
        strtoupper(
            trim(
                $_GET['rol']
                ?? 'TODOS'
            )
        );


    $filtrosValidos = [
        'TODOS',
        'PROPIETARIO',
        'ADMINISTRADOR',
        'RRHH'
    ];


    if (
        !in_array(
            $rolFiltro,
            $filtrosValidos,
            true
        )
    ) {

        $rolFiltro =
            'TODOS';
    }


    $where =
        '';


    /* =====================================================
       FILTRO POR ROL
       ===================================================== */

    if (
        $rolFiltro ===
        'PROPIETARIO'
    ) {

        $where =
            "WHERE UPPER(u.rol)='PROPIETARIO'";

    } elseif (
        $rolFiltro ===
        'RRHH'
    ) {

        $where =
            "WHERE UPPER(u.rol)='RRHH'";

    } elseif (
        $rolFiltro ===
        'ADMINISTRADOR'
    ) {

        $where = "

            WHERE UPPER(u.rol)
            IN (
                'ADMIN',
                'ADMINISTRADOR'
            )
        ";
    }


    /* =====================================================
       TOTAL DE REGISTROS
       ===================================================== */

    $res =
        $db->obtenerRegistros(

            "SELECT
                COUNT(*) total

             FROM usuarios u

             $where"
        );


    $totalRegistros =
        (int)(
            $res[0]['total']
            ?? 0
        );


    $totalPaginas =
        max(
            1,
            (int)ceil(
                $totalRegistros /
                $porPagina
            )
        );


    if (
        $paginaActual >
        $totalPaginas
    ) {

        $paginaActual =
            $totalPaginas;
    }


    $offset =
        ($paginaActual - 1) *
        $porPagina;


    /* =====================================================
       CONSULTAR USUARIOS
       ===================================================== */

    /*
     * Conservamos por ahora la consulta
     * original del ADMIN.
     *
     * La optimización de u.* se realizará
     * posteriormente durante la limpieza
     * final del proyecto.
     */

    $datos2 =
        $db->obtenerRegistros(

            "SELECT

                u.*,

                e.nombre_empresa,

                (
                    SELECT
                        COUNT(*)

                    FROM usuario_empresas ue

                    WHERE
                        ue.id_usuario =
                        u.id_usuario

                ) cantidad_empresas,

                (
                    SELECT
                        GROUP_CONCAT(

                            e2.nombre_empresa

                            ORDER BY
                                e2.nombre_empresa

                            SEPARATOR ' | '
                        )

                    FROM usuario_empresas ue2

                    INNER JOIN empresas e2
                        ON
                            e2.id_empresa =
                            ue2.id_empresa

                    WHERE
                        ue2.id_usuario =
                        u.id_usuario

                ) empresas_multi

             FROM usuarios u

             LEFT JOIN empresas e
                ON
                    e.id_empresa =
                    u.id_empresa

             $where

             ORDER BY
                u.id_usuario DESC

             LIMIT $porPagina

             OFFSET $offset"
        );
}


/* =========================================================
   PROPIETARIO
   ========================================================= */

else {


    /* =====================================================
       ID DEL PROPIETARIO
       ===================================================== */

    $idPropietario =
        (int)(
            $_SESSION['id_usuario']
            ?? 0
        );


    /*
     * No confiamos únicamente en los datos
     * almacenados en sesión.
     *
     * Consultamos nuevamente MySQL para conocer
     * la empresa y condición multiempresa actual
     * del propietario.
     */

    $stmtPropietario =
        $db->conn->prepare(

            "SELECT

                id_usuario,

                id_empresa,

                multiempresa,

                rol

             FROM usuarios

             WHERE
                id_usuario =
                :id_usuario

             LIMIT 1"
        );


    $stmtPropietario->execute([

        ':id_usuario' =>
            $idPropietario

    ]);


    $propietario =
        $stmtPropietario->fetch(
            PDO::FETCH_ASSOC
        );


    /* =====================================================
       VALIDAR PROPIETARIO
       ===================================================== */

    if (
        !$propietario ||
        strtoupper(
            trim(
                $propietario['rol']
                ?? ''
            )
        ) !== 'PROPIETARIO'
    ) {

        if ($conexionLocal) {

            $db->desconectar();
        }


        http_response_code(403);

        exit(
            'Acceso no autorizado'
        );
    }


    $idEmpresaPropietario =
        (int)(
            $propietario[
                'id_empresa'
            ] ?? 0
        );


    $esMultiempresa =
        (int)(
            $propietario[
                'multiempresa'
            ] ?? 0
        ) === 1;


    /*
     * Para PROPIETARIO esta tabla siempre
     * representa exclusivamente RRHH.
     *
     * Cualquier ?rol= enviado por URL
     * queda ignorado.
     */

    $rolFiltro =
        'RRHH';


    /* =====================================================
       ALCANCE DEL PROPIETARIO
       ===================================================== */

    if ($esMultiempresa) {


        /*
         * PROPIETARIO MULTIEMPRESA
         *
         * Únicamente RRHH cuya empresa esté
         * vinculada mediante usuario_empresas.
         */

        $wherePropietario = "

            WHERE
                UPPER(u.rol) = 'RRHH'

            AND
                u.id_empresa IN (

                    SELECT
                        ue.id_empresa

                    FROM usuario_empresas ue

                    WHERE
                        ue.id_usuario =
                        :id_propietario
                )
        ";


        $parametrosPropietario = [

            ':id_propietario' =>
                $idPropietario
        ];


    } elseif (
        $idEmpresaPropietario > 0
    ) {


        /*
         * PROPIETARIO DE UNA EMPRESA
         *
         * Únicamente RRHH pertenecientes
         * a su propia empresa.
         */

        $wherePropietario = "

            WHERE
                UPPER(u.rol) = 'RRHH'

            AND
                u.id_empresa =
                :id_empresa
        ";


        $parametrosPropietario = [

            ':id_empresa' =>
                $idEmpresaPropietario
        ];


    } else {


        /*
         * PROPIETARIO SIN EMPRESA VÁLIDA
         *
         * Por seguridad no mostramos
         * ningún registro.
         */

        $wherePropietario = "

            WHERE
                1 = 0
        ";


        $parametrosPropietario =
            [];
    }


    /* =====================================================
       TOTAL DE RRHH AUTORIZADOS
       ===================================================== */

    $stmtTotal =
        $db->conn->prepare(

            "SELECT
                COUNT(*) AS total

             FROM usuarios u

             $wherePropietario"
        );


    $stmtTotal->execute(
        $parametrosPropietario
    );


    $totalRegistros =
        (int)(
            $stmtTotal->fetchColumn()
            ?: 0
        );


    $totalPaginas =
        max(
            1,
            (int)ceil(
                $totalRegistros /
                $porPagina
            )
        );


    if (
        $paginaActual >
        $totalPaginas
    ) {

        $paginaActual =
            $totalPaginas;
    }


    $offset =
        ($paginaActual - 1) *
        $porPagina;


    /* =====================================================
       CONSULTAR RRHH AUTORIZADOS
       ===================================================== */

    /*
     * No utilizamos u.* para PROPIETARIO.
     *
     * Solo recuperamos los campos necesarios
     * para presentar la información de RRHH.
     */

    $sqlRRHH = "

        SELECT

            u.id_usuario,

            u.nombre_usuario,

            u.nombres,

            u.primer_apellido,

            u.segundo_apellido,

            u.rol,

            u.correo_electronico,

            u.id_empresa,

            u.multiempresa,

            u.estatus,

            e.nombre_empresa,

            0 AS cantidad_empresas,

            NULL AS empresas_multi

        FROM usuarios u

        LEFT JOIN empresas e
            ON
                e.id_empresa =
                u.id_empresa

        $wherePropietario

        ORDER BY
            u.id_usuario DESC

        LIMIT $porPagina

        OFFSET $offset
    ";


    $stmtRRHH =
        $db->conn->prepare(
            $sqlRRHH
        );


    $stmtRRHH->execute(
        $parametrosPropietario
    );


    $datos2 =
        $stmtRRHH->fetchAll(
            PDO::FETCH_ASSOC
        );
}


/* =========================================================
   CERRAR CONEXIÓN LOCAL
   ========================================================= */

if ($conexionLocal) {

    $db->desconectar();
}


/* =========================================================
   ESCAPAR HTML
   ========================================================= */

$h =
    fn($v) =>
        htmlspecialchars(
            (string)$v,
            ENT_QUOTES,
            'UTF-8'
        );

?>


<!-- =========================================================
     TABLA DE USUARIOS / RRHH
     ========================================================= -->

<div class="table-container">


    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <?php if ($esAdmin): ?>


        <!-- =================================================
             FILTROS DEL ADMINISTRADOR
             ================================================= -->

        <div
            class="table-header-title"
            style="
                display:flex;
                flex-wrap:wrap;
                justify-content:flex-start;
                align-items:center;
                gap:12px;
                padding:15px;
            "
        >


            <div class="table-tabs">


                <button
                    type="button"
                    class="tab-btn <?= $rolFiltro === 'TODOS' ? 'active' : '' ?>"
                    onclick="filtrarUsuarios('TODOS',this)"
                >

                    Todos

                </button>


                <button
                    type="button"
                    class="tab-btn <?= $rolFiltro === 'PROPIETARIO' ? 'active' : '' ?>"
                    onclick="filtrarUsuarios('PROPIETARIO',this)"
                >

                    Propietarios

                </button>


                <button
                    type="button"
                    class="tab-btn <?= $rolFiltro === 'ADMINISTRADOR' ? 'active' : '' ?>"
                    onclick="filtrarUsuarios('ADMINISTRADOR',this)"
                >

                    Administradores

                </button>


                <button
                    type="button"
                    class="tab-btn <?= $rolFiltro === 'RRHH' ? 'active' : '' ?>"
                    onclick="filtrarUsuarios('RRHH',this)"
                >

                    RRHH

                </button>


            </div>


        </div>


    <?php else: ?>


        <!-- =================================================
             ENCABEZADO DEL PROPIETARIO
             ================================================= -->

        <div
            class="table-header-title"
            style="
                display:flex;
                flex-wrap:wrap;
                justify-content:space-between;
                align-items:center;
                gap:12px;
                padding:15px;
            "
        >


            <div>


                <strong
                    style="
                        color:var(--texto-principal);
                        font-size:.95rem;
                    "
                >

                    RRHH registrados

                </strong>


                <div
                    style="
                        color:var(--texto-secundario);
                        font-size:.80rem;
                        margin-top:3px;
                    "
                >

                    Cuentas de Recursos Humanos
                    asociadas a su empresa o empresas.

                </div>


            </div>


            <span
                class="badge-role role-rrhh"
            >

                <?= (int)$totalRegistros ?>

                <?= $totalRegistros === 1
                    ? 'cuenta'
                    : 'cuentas'
                ?>

            </span>


        </div>


    <?php endif; ?>


    <!-- =====================================================
         DATOS
         ===================================================== -->

    <div class="table-responsive">


        <table
            class="custom-table"
            id="tablaUsuarios"
        >


            <thead>


                <tr>


                    <th>
                        Usuario
                    </th>


                    <th>
                        Correo Electrónico
                    </th>


                    <?php if ($esAdmin): ?>

                        <th>
                            Rol
                        </th>

                    <?php endif; ?>


                    <th>
                        <?= $esAdmin
                            ? 'Cuenta / Empresa(s)'
                            : 'Empresa'
                        ?>
                    </th>


                    <th class="text-center">
                        Estatus
                    </th>


                    <?php if ($esAdmin): ?>

                        <th class="text-center">
                            Acciones
                        </th>

                    <?php endif; ?>


                </tr>


            </thead>


            <tbody>


            <?php if (!empty($datos2)): ?>


                <?php foreach ($datos2 as $u):


                    $id =
                        (int)(
                            $u['id_usuario']
                            ?? 0
                        );


                    $rolUsuario =
                        strtoupper(
                            trim(
                                $u['rol']
                                ?? ''
                            )
                        );


                    $rolMostrar =
                        $rolUsuario === 'ADMIN'
                            ? 'ADMINISTRADOR'
                            : $rolUsuario;


                    $multi =
                        (int)(
                            $u['multiempresa']
                            ?? 0
                        );


                    $estatusUsuario =
                        (int)(
                            $u['estatus']
                            ?? 1
                        );


                    $esActivo =
                        $estatusUsuario === 1;


                    $esAdministrador =
                        in_array(
                            $rolUsuario,
                            [
                                'ADMIN',
                                'ADMINISTRADOR'
                            ],
                            true
                        );


                    $nombreCompleto =
                        trim(
                            ($u['nombres'] ?? '') . ' ' .
                            ($u['primer_apellido'] ?? '') . ' ' .
                            ($u['segundo_apellido'] ?? '')
                        );


                    /* =========================================
                       BADGE DEL ROL
                       ========================================= */

                    $badge =
                        'role-default';


                    if ($esAdministrador) {

                        $badge =
                            'role-admin';

                    } elseif (
                        $rolUsuario ===
                        'PROPIETARIO'
                    ) {

                        $badge =
                            'role-propietario';

                    } elseif (
                        $rolUsuario ===
                        'RRHH'
                    ) {

                        $badge =
                            'role-rrhh';
                    }


                ?>


                <tr
                    data-rol="<?= $h($rolMostrar) ?>"
                    data-estatus="<?= $esActivo ? 'activo' : 'inactivo' ?>"
                >


                    <!-- =================================================
                         USUARIO
                         ================================================= -->

                    <td class="font-medium">


                        <?= $h(
                            $u['nombre_usuario']
                            ?? ''
                        ) ?>


                        <?php if ($nombreCompleto !== ''): ?>


                            <div
                                style="
                                    font-size:.82rem;
                                    opacity:.75;
                                    margin-top:3px;
                                "
                            >

                                <?= $h(
                                    $nombreCompleto
                                ) ?>

                            </div>


                        <?php endif; ?>


                    </td>


                    <!-- =================================================
                         CORREO
                         ================================================= -->

                    <td>


                        <?= $h(
                            $u[
                                'correo_electronico'
                            ] ?? ''
                        ) ?>


                    </td>


                    <!-- =================================================
                         ROL - SOLO ADMIN
                         ================================================= -->

                    <?php if ($esAdmin): ?>


                        <td>


                            <span
                                class="badge-role <?= $badge ?>"
                            >

                                <?= $h(
                                    $rolMostrar
                                ) ?>

                            </span>


                        </td>


                    <?php endif; ?>


                    <!-- =================================================
                         EMPRESA
                         ================================================= -->

                    <td>


                        <?php if ($esAdmin): ?>


                            <!-- ADMIN -->

                            <?php if ($esAdministrador): ?>


                                <strong>

                                    🌐 Acceso global

                                </strong>


                                <div
                                    style="
                                        font-size:.82rem;
                                        opacity:.75;
                                        margin-top:3px;
                                    "
                                >

                                    Todas las empresas

                                </div>


                            <!-- PROPIETARIO MULTIEMPRESA -->

                            <?php elseif (
                                $rolUsuario === 'PROPIETARIO' &&
                                $multi === 1
                            ): ?>


                                <strong>

                                    🏢 Multiempresa ·

                                    <?= (int)(
                                        $u[
                                            'cantidad_empresas'
                                        ] ?? 0
                                    ) ?>

                                    empresas

                                </strong>


                                <?php if (
                                    !empty(
                                        $u[
                                            'empresas_multi'
                                        ]
                                    )
                                ): ?>


                                    <div
                                        style="
                                            font-size:.82rem;
                                            opacity:.75;
                                            margin-top:4px;
                                        "
                                    >

                                        <?= $h(
                                            $u[
                                                'empresas_multi'
                                            ]
                                        ) ?>

                                    </div>


                                <?php endif; ?>


                            <!-- PROPIETARIO UNA EMPRESA -->

                            <?php elseif (
                                $rolUsuario === 'PROPIETARIO'
                            ): ?>


                                <strong>

                                    🏢 Una empresa

                                </strong>


                                <div
                                    style="
                                        font-size:.82rem;
                                        opacity:.75;
                                        margin-top:4px;
                                    "
                                >

                                    <?= $h(
                                        $u['nombre_empresa']
                                        ?: 'Sin empresa asignada'
                                    ) ?>

                                </div>


                            <!-- RRHH -->

                            <?php elseif (
                                $rolUsuario === 'RRHH'
                            ): ?>


                                <strong>

                                    🏢 Empresa asignada

                                </strong>


                                <div
                                    style="
                                        font-size:.82rem;
                                        opacity:.75;
                                        margin-top:4px;
                                    "
                                >

                                    <?= $h(
                                        $u['nombre_empresa']
                                        ?: 'Sin empresa asignada'
                                    ) ?>

                                </div>


                            <?php endif; ?>


                        <?php else: ?>


                            <!-- =========================================
                                 PROPIETARIO - SOLO RRHH
                                 ========================================= -->

                            <strong>

                                🏢

                                <?= $h(
                                    $u['nombre_empresa']
                                    ?: 'Sin empresa asignada'
                                ) ?>

                            </strong>


                        <?php endif; ?>


                    </td>


                    <!-- =================================================
                         ESTATUS
                         ================================================= -->

                    <td class="text-center">


                        <span
                            class="
                                badge-status
                                <?= $esActivo
                                    ? 'status-activo'
                                    : 'status-inactivo'
                                ?>
                            "
                        >

                            <?= $esActivo
                                ? 'Activo'
                                : 'Inactivo'
                            ?>

                        </span>


                    </td>


                    <!-- =================================================
                         CONTROL DE ACCESO - SOLO ADMIN
                         ================================================= -->

                    <?php if ($esAdmin): ?>


                        <td class="text-center">


                            <div class="acciones-operador">


                                <!-- =====================================
                                     ADMIN PROTEGIDO
                                     ===================================== -->

                                <?php if ($esAdministrador): ?>


                                    <button
                                        type="button"
                                        class="
                                            btn-action
                                            btn-op-disabled
                                        "
                                        disabled
                                        title="Las cuentas de administrador están protegidas"
                                    >

                                        🛡️ Protegido

                                    </button>


                                <!-- =====================================
                                     USUARIO ACTIVO
                                     ===================================== -->

                                <?php elseif ($esActivo): ?>


                                    <button
                                        type="button"
                                        class="
                                            btn-action
                                            btn-delete
                                        "
                                        onclick="cambiarEstatusUsuario(
                                            '<?= $id ?>',
                                            1,
                                            <?= $paginaActual ?>,
                                            '<?= $h($rolFiltro) ?>'
                                        )"
                                        title="Desactivar acceso al sistema"
                                    >

                                        🔒 Desactivar

                                    </button>


                                <!-- =====================================
                                     USUARIO INACTIVO
                                     ===================================== -->

                                <?php else: ?>


                                    <button
                                        type="button"
                                        class="
                                            btn-action
                                            btn-info
                                        "
                                        onclick="cambiarEstatusUsuario(
                                            '<?= $id ?>',
                                            0,
                                            <?= $paginaActual ?>,
                                            '<?= $h($rolFiltro) ?>'
                                        )"
                                        title="Reactivar acceso al sistema"
                                    >

                                        🔓 Reactivar

                                    </button>


                                <?php endif; ?>


                            </div>


                        </td>


                    <?php endif; ?>


                </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>


                    <td
                        colspan="<?= $esAdmin ? 6 : 4 ?>"
                        class="text-center"
                    >


                        <?php if ($esAdmin): ?>

                            No se encontraron usuarios.

                        <?php else: ?>

                            No se encontraron cuentas de RRHH
                            asociadas a su empresa o empresas.

                        <?php endif; ?>


                    </td>


                </tr>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


    <!-- =====================================================
         PAGINACIÓN
         ===================================================== -->

    <?php if ($totalPaginas > 1): ?>


        <div class="pagination-wrapper">


            <div class="pagination-info">


                Página


                <span>
                    <?= $paginaActual ?>
                </span>


                de


                <span>
                    <?= $totalPaginas ?>
                </span>


            </div>


            <div class="pagination-controls">


                <button
                    type="button"
                    <?= $paginaActual <= 1
                        ? 'disabled'
                        : ''
                    ?>
                    onclick="cambiarPaginaUsuarios(
                        <?= $paginaActual - 1 ?>,
                        '<?= $h($rolFiltro) ?>'
                    )"
                    class="
                        pagination-btn
                        <?= $paginaActual <= 1
                            ? 'disabled'
                            : ''
                        ?>
                    "
                >

                    ← Anterior

                </button>


                <div class="pagination-current">

                    Página
                    <?= $paginaActual ?>

                </div>


                <button
                    type="button"
                    <?= $paginaActual >= $totalPaginas
                        ? 'disabled'
                        : ''
                    ?>
                    onclick="cambiarPaginaUsuarios(
                        <?= $paginaActual + 1 ?>,
                        '<?= $h($rolFiltro) ?>'
                    )"
                    class="
                        pagination-btn
                        <?= $paginaActual >= $totalPaginas
                            ? 'disabled'
                            : ''
                        ?>
                    "
                >

                    Siguiente →

                </button>


            </div>


        </div>


    <?php endif; ?>


</div>