<?php

/* =========================================================
   SESIÓN
   ========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {

    session_start();
}


/* =========================================================
   ROL
   ========================================================= */

$rolTabla =
    strtoupper(
        trim(
            $_SESSION['rol'] ?? ''
        )
    );


if (
    $rolTabla ===
    'ADMINISTRADOR'
) {

    $rolTabla =
        'ADMIN';
}


/* =========================================================
   PERMISOS
   ========================================================= */

if (
    !in_array(
        $rolTabla,
        [
            'ADMIN',
            'PROPIETARIO'
        ],
        true
    )
) {

    http_response_code(
        403
    );


    exit(
        '<div class="alert alert-danger">
            No tienes permiso para consultar empresas.
        </div>'
    );
}


/* =========================================================
   VARIABLES
   ========================================================= */

$datos2 =
    $datos2 ?? [];


$pagina_actual =
    max(
        1,
        (int)(
            $pagina_actual ?? 1
        )
    );


$registros_por_pagina =
    max(
        1,
        (int)(
            $registros_por_pagina ?? 5
        )
    );


$total_registros =
    max(
        0,
        (int)(
            $total_registros ??
            count(
                $datos2
            )
        )
    );


$total_paginas =
    max(
        1,
        (int)(
            $total_paginas ?? 1
        )
    );


$busqueda =
    trim(
        (string)(
            $busqueda ?? ''
        )
    );


$esAdminTabla =
    $rolTabla ===
    'ADMIN';


$colspanTabla =
    $esAdminTabla
        ? 4
        : 3;


/* =========================================================
   ESCAPAR HTML
   ========================================================= */

if (
    !function_exists(
        'eEmpresa'
    )
) {

    function eEmpresa(
        $valor
    ): string {

        return htmlspecialchars(
            (string)$valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

?>


<!-- =========================================================
     TABLA DE EMPRESAS
     MISMO ESTÁNDAR DE USUARIOS / OPERADORES / REPORTES
     ========================================================= -->

<div
    class="
        table-container
        empresa-tabla-card
    "
    data-pagina="<?= $pagina_actual ?>"
>


    <!-- =====================================================
         HERRAMIENTAS
         ===================================================== -->

    <div
        class="
            table-header-title
            empresa-table-tools
        "
    >


        <!-- =================================================
             RESUMEN
             ================================================= -->

        <div class="empresa-header-summary">


            <span class="empresa-contador">

                <?= $total_registros ?>

                <?=
                    $total_registros === 1
                        ? 'empresa'
                        : 'empresas'
                ?>

            </span>


            <?php if (
                !$esAdminTabla
            ): ?>


                <span class="modo-consulta-empresa">

                    👁 Solo consulta

                </span>


            <?php endif; ?>


        </div>


        <!-- =================================================
             BUSCADOR
             ================================================= -->

        <div class="empresa-buscador">


            <span
                class="empresa-buscador-icono"
            >

                🔍

            </span>


            <input
                type="text"
                id="inputBuscadorEmpresa"
                class="form-control"
                value="<?= eEmpresa($busqueda) ?>"
                placeholder="Buscar empresa, responsable o dirección..."
                autocomplete="off"
                oninput="programarBusquedaEmpresas()"
            >


            <button
                type="button"
                id="btnLimpiarEmpresa"
                class="empresa-limpiar"
                onclick="limpiarBuscadorEmpresa()"
                title="Limpiar búsqueda"
                aria-label="Limpiar búsqueda"
                style="
                    display:
                    <?= $busqueda !== ''
                        ? 'flex'
                        : 'none'
                    ?>;
                "
            >

                &times;

            </button>


        </div>


    </div>


    <!-- =====================================================
         TABLA
         ===================================================== -->

    <div class="table-responsive">


        <table
            class="
                custom-table
                empresa-tabla
            "
            id="tablaEmpresas"
        >


            <thead>


                <tr>


                    <th>

                        Empresa

                    </th>


                    <th>

                        Dirección fiscal

                    </th>


                    <th>

                        Responsable

                    </th>


                    <?php if (
                        $esAdminTabla
                    ): ?>


                        <th class="text-center">

                            Acciones

                        </th>


                    <?php endif; ?>


                </tr>


            </thead>


            <tbody>


            <?php if (
                !empty(
                    $datos2
                )
            ): ?>


                <?php foreach (
                    $datos2
                    as $dato
                ):


                    $id =
                        (int)(
                            $dato[
                                'id_empresa'
                            ] ?? 0
                        );


                    $nombre =
                        trim(
                            $dato[
                                'nombre_empresa'
                            ] ?? ''
                        );


                    $razon =
                        trim(
                            $dato[
                                'razon_social'
                            ] ?? ''
                        );


                    $direccion =
                        trim(
                            $dato[
                                'direccion_fiscal'
                            ] ?? ''
                        );


                    $responsable =
                        trim(
                            $dato[
                                'responsable'
                            ] ?? ''
                        );

                ?>


                    <tr class="fila-empresa">


                        <!-- =================================
                             EMPRESA
                             ================================= -->

                        <td class="font-medium">


                            <div class="empresa-nombre">

                                <?= eEmpresa(
                                    $nombre
                                ) ?>

                            </div>


                            <?php if (
                                $razon !== ''
                            ): ?>


                                <div class="empresa-razon">

                                    <?= eEmpresa(
                                        $razon
                                    ) ?>

                                </div>


                            <?php endif; ?>


                        </td>


                        <!-- =================================
                             DIRECCIÓN
                             ================================= -->

                        <td>


                            <div class="empresa-direccion">

                                <?=
                                    $direccion !== ''

                                        ? eEmpresa(
                                            $direccion
                                        )

                                        : 'Sin dirección registrada'
                                ?>

                            </div>


                        </td>


                        <!-- =================================
                             RESPONSABLE
                             ================================= -->

                        <td>


                            <div class="empresa-responsable">

                                <?=
                                    $responsable !== ''

                                        ? eEmpresa(
                                            $responsable
                                        )

                                        : 'Sin responsable'
                                ?>

                            </div>


                        </td>


                        <!-- =================================
                             ACCIONES
                             ================================= -->

                        <?php if (
                            $esAdminTabla
                        ): ?>


                            <td class="text-center">


                                <div
                                    class="
                                        acciones-operador
                                        empresa-acciones
                                    "
                                >


                                    <button
                                        type="button"
                                        class="
                                            btn-action
                                            btn-edit
                                        "
                                        onclick="
                                            editar(
                                                '<?= $id ?>',
                                                'empresas',
                                                'frm'
                                            )
                                        "
                                        title="Editar empresa"
                                    >

                                        ✏️ Editar

                                    </button>


                                    <button
                                        type="button"
                                        class="
                                            btn-action
                                            btn-delete
                                        "
                                        onclick="
                                            eliminar(
                                                '<?= $id ?>',
                                                'empresas'
                                            )
                                        "
                                        title="Eliminar empresa"
                                    >

                                        🗑️ Eliminar

                                    </button>


                                </div>


                            </td>


                        <?php endif; ?>


                    </tr>


                <?php endforeach; ?>


            <?php else: ?>


                <tr>


                    <td
                        colspan="<?= $colspanTabla ?>"
                        class="text-center empresa-vacio-celda"
                    >


                        <div class="empresa-vacio">


                            <div
                                class="empresa-vacio-icono"
                            >

                                <?=
                                    $busqueda !== ''
                                        ? '🔎'
                                        : '🏢'
                                ?>

                            </div>


                            <strong>


                                <?php if (
                                    $busqueda !== ''
                                ): ?>

                                    No se encontraron empresas


                                <?php elseif (
                                    $esAdminTabla
                                ): ?>

                                    No hay empresas registradas


                                <?php else: ?>

                                    No tienes empresas asignadas


                                <?php endif; ?>


                            </strong>


                            <span>


                                <?php if (
                                    $busqueda !== ''
                                ): ?>

                                    Intenta con otro nombre,
                                    responsable o dirección.


                                <?php elseif (
                                    $esAdminTabla
                                ): ?>

                                    Cuando registres una empresa,
                                    aparecerá en esta sección.


                                <?php else: ?>

                                    Actualmente tu cuenta no tiene
                                    empresas disponibles.


                                <?php endif; ?>


                            </span>


                        </div>


                    </td>


                </tr>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


    <!-- =====================================================
         PAGINACIÓN
         MISMO ESTÁNDAR DE LOS DEMÁS MÓDULOS
         ===================================================== -->

    <?php if (
        $total_paginas > 1
    ): ?>


        <div class="pagination-wrapper">


            <div class="pagination-info">

                Página

                <span>

                    <?= $pagina_actual ?>

                </span>

                de

                <span>

                    <?= $total_paginas ?>

                </span>

            </div>


            <div class="pagination-controls">


                <button
                    type="button"
                    <?= $pagina_actual <= 1
                        ? 'disabled'
                        : ''
                    ?>
                    onclick="
                        cambiarPaginaEmpresas(
                            <?= max(
                                1,
                                $pagina_actual - 1
                            ) ?>
                        )
                    "
                    class="
                        pagination-btn
                        <?= $pagina_actual <= 1
                            ? 'disabled'
                            : ''
                        ?>
                    "
                >

                    ← Anterior

                </button>


                <div class="pagination-current">

                    Página
                    <?= $pagina_actual ?>

                </div>


                <button
                    type="button"
                    <?= $pagina_actual >= $total_paginas
                        ? 'disabled'
                        : ''
                    ?>
                    onclick="
                        cambiarPaginaEmpresas(
                            <?= min(
                                $total_paginas,
                                $pagina_actual + 1
                            ) ?>
                        )
                    "
                    class="
                        pagination-btn
                        <?= $pagina_actual >= $total_paginas
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