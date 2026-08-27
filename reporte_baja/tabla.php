<?php
/* =========================================================
   TABLA DE REPORTES DE BAJA
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) session_start();

/* VARIABLES SEGURAS */
$busqueda = trim($busqueda ?? ($_GET['busqueda'] ?? ''));
$estatus_filtro = strtolower(trim(
    $estatus_filtro ?? ($_GET['estatus'] ?? 'todos')
));
$pagina_actual = max(1, (int)(
    $pagina_actual ?? ($_GET['pagina'] ?? 1)
));
$total_paginas = max(1, (int)($total_paginas ?? 1));
$datos2 = $datos2 ?? [];

if (!in_array($estatus_filtro, ['todos','pendientes','completados'], true)) {
    $estatus_filtro = 'todos';
}


/* ROLES */
$rolTabla = strtoupper(trim(
    $rol ?? ($_SESSION['rol'] ?? '')
));

if ($rolTabla === 'ADMINISTRADOR') {
    $rolTabla = 'ADMIN';
}

if (in_array($rolTabla, ['RH','RECURSOS HUMANOS'], true)) {
    $rolTabla = 'RRHH';
}

$esRRHH = $rolTabla === 'RRHH';

$puedeAcciones = in_array(
    $rolTabla,
    ['ADMIN','PROPIETARIO'],
    true
);


/* MOTIVOS */
$motivosTexto = [
    'ROBO'=>'Robo',
    'GASTO_COMBUSTIBLE'=>'Gasto de Combustible',
    'CHOQUES'=>'Choques / Colisiones',
    'MULTAS'=>'Multas / Infracciones',
    'FALTAS'=>'Faltas / Inasistencias',
    'RENUNCIA_VOLUNTARIA'=>'Renuncia Voluntaria',
    'DESPIDO'=>'Despido',
    'ABANDONO_TRABAJO'=>'Abandono de Trabajo',
    'INCUMPLIMIENTO'=>'Incumplimiento',
    'OTRO'=>'Otro'
];

$camposEvaluacion = [
    'eval_distancia',
    'eval_tiempo',
    'eval_ganancias',
    'eval_cuidado_vehiculo',
    'eval_productividad',
    'eval_rendimiento',
    'eval_cuidado_fisico'
];

$h = fn($v) =>
    htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$js = fn($v) =>
    htmlspecialchars(
        json_encode($v, JSON_UNESCAPED_UNICODE),
        ENT_QUOTES,
        'UTF-8'
    );
?>


<div class="table-container">

    <!-- FILTROS Y BUSCADOR -->
    <div class="table-header-title"
         style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">

        <div class="table-tabs">

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'todos' ? 'active' : '' ?>"
                    onclick="cargarReportes(1,'todos',<?= $js($busqueda) ?>)">
                Todos
            </button>

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'pendientes' ? 'active' : '' ?>"
                    onclick="cargarReportes(1,'pendientes',<?= $js($busqueda) ?>)">
                Pendientes
            </button>

            <button type="button"
                    class="tab-btn <?= $estatus_filtro === 'completados' ? 'active' : '' ?>"
                    onclick="cargarReportes(1,'completados',<?= $js($busqueda) ?>)">
                Completados
            </button>

        </div>


        <!-- BUSCADOR -->
        <div class="search-box-wrapper"
             style="position:relative;min-width:280px;flex:1;max-width:380px;">

            <input type="text"
                   id="busquedaReportes"
                   class="form-control"
                   placeholder="🔍 Operador, RFC, empresa o motivo..."
                   value="<?= $h($busqueda) ?>"
                   onkeydown="if(event.key==='Enter'){
                       event.preventDefault();
                       cargarReportes(
                           1,
                           <?= $js($estatus_filtro) ?>,
                           this.value
                       );
                   }"
                   style="padding-right:38px;height:40px;font-size:.9rem;">

            <?php if ($busqueda !== ''): ?>

                <button type="button"
                        title="Limpiar búsqueda"
                        onclick="cargarReportes(
                            1,
                            <?= $js($estatus_filtro) ?>,
                            ''
                        )"
                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--texto-secundario);">
                    &times;
                </button>

            <?php endif; ?>

        </div>

    </div>


    <!-- TABLA -->
    <div class="table-responsive">

        <table class="custom-table">

            <thead>
                <tr>
                    <th>Operador</th>
                    <th>Empresa</th>
                    <th class="text-center">Estado</th>

                    <?php if ($puedeAcciones): ?>
                        <th class="text-center">Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>


            <tbody>

            <?php if (!empty($datos2)): ?>

                <?php foreach ($datos2 as $r):

                    $id = (int)($r['id_reporte'] ?? 0);

                    $estatus = strtoupper(
                        $r['estatus_evaluacion'] ?? 'PENDIENTE'
                    );

                    $calif = is_numeric(
                        $r['calificacion_cuantitativa'] ?? null
                    )
                        ? (float)$r['calificacion_cuantitativa']
                        : 0;

                    $motivo = strtoupper(
                        $r['motivo_baja'] ?? ''
                    );

                    $motivoMostrar =
                        $motivosTexto[$motivo] ?? $motivo;

                    if (
                        $motivo === 'OTRO' &&
                        !empty($r['calif_cualitativa'])
                    ) {
                        $motivoMostrar =
                            'Otro: ' . $r['calif_cualitativa'];
                    }


                    /* TIPO DE EVALUACIÓN */
                    $tieneEvaluacion = true;

                    foreach ($camposEvaluacion as $campo) {

                        if (
                            !array_key_exists($campo, $r) ||
                            $r[$campo] === null
                        ) {
                            $tieneEvaluacion = false;
                            break;
                        }
                    }

                    $tipoEvaluacion =
                        $tieneEvaluacion ? 'nueva' : 'anterior';

                    $promedioServicio = 0;

                    if ($tieneEvaluacion) {

                        $promedioServicio =
                            $r['promedio_servicio'] !== null
                            ? (float)$r['promedio_servicio']
                            : (
                                (float)$r['eval_distancia'] +
                                (float)$r['eval_tiempo'] +
                                (float)$r['eval_ganancias']
                              ) / 3;
                    }
                ?>


                <tr>

                    <!-- OPERADOR -->
                    <td class="font-medium">
                        <?= $h($r['nombre_operador'] ?? '') ?>
                    </td>


                    <!-- EMPRESA -->
                    <td>
                        <?= $h($r['nombre_empresa'] ?? '') ?>
                    </td>


                    <!-- ESTADO -->
                    <td class="text-center"
                        id="estado-reporte-<?= $id ?>">

                        <?php if ($estatus === 'PENDIENTE'): ?>

                            <span class="badge-status"
                                  style="background:rgba(245,158,11,.12);border:1px solid #f59e0b;color:#fbbf24;">
                                ⏳ PENDIENTE
                            </span>

                        <?php else: ?>

                            <span class="badge-status status-activo">
                                ✅ COMPLETADA
                            </span>

                        <?php endif; ?>

                    </td>


                    <?php if ($puedeAcciones): ?>

                    <!-- ACCIONES -->
                    <td class="text-center"
                        id="accion-reporte-<?= $id ?>">

                        <?php if ($estatus === 'PENDIENTE'): ?>

                            <button type="button"
                                    class="btn-action btn-edit"
                                    onclick="abrirRevisionBaja(
                                        <?= $id ?>,
                                        <?= $js($r['nombre_operador'] ?? '') ?>,
                                        <?= $js($r['nombre_empresa'] ?? '') ?>,
                                        <?= $js($motivoMostrar) ?>
                                    )">
                                ⭐ Revisar y Evaluar
                            </button>


                        <?php elseif ($estatus === 'COMPLETADA'): ?>

                            <!-- EVALUACIÓN -->
                            <button type="button"
                                    class="btn-action btn-edit btn-evaluacion"
                                    data-tipo="<?= $tipoEvaluacion ?>"
                                    data-operador="<?= $h($r['nombre_operador'] ?? '') ?>"
                                    data-empresa="<?= $h($r['nombre_empresa'] ?? '') ?>"
                                    data-motivo="<?= $h($motivoMostrar) ?>"
                                    data-general="<?= number_format($calif,2,'.','') ?>"

                                    <?php if ($tieneEvaluacion): ?>

                                        data-promedio="<?= number_format($promedioServicio,2,'.','') ?>"
                                        data-distancia="<?= (int)$r['eval_distancia'] ?>"
                                        data-tiempo="<?= (int)$r['eval_tiempo'] ?>"
                                        data-ganancias="<?= (int)$r['eval_ganancias'] ?>"
                                        data-cuidado="<?= (int)$r['eval_cuidado_vehiculo'] ?>"
                                        data-productividad="<?= (int)$r['eval_productividad'] ?>"
                                        data-rendimiento="<?= (int)$r['eval_rendimiento'] ?>"
                                        data-fisico="<?= (int)$r['eval_cuidado_fisico'] ?>"

                                    <?php endif; ?>

                                    onclick="verEvaluacionBaja(this)">

                                📊 Ver Evaluación

                            </button>


                            <!-- CONSTANCIA -->
                            <button type="button"
                                    class="btn-action btn-edit btn-constancia-laboral"
                                    onclick="window.open(
                                        '/reporte_baja/constancia.php?id=<?= $id ?>',
                                        '_blank'
                                    )">

                                📄 Constancia Laboral

                            </button>

                        <?php endif; ?>

                    </td>

                    <?php endif; ?>

                </tr>


                <?php endforeach; ?>


            <?php else: ?>

                <tr>
                    <td colspan="<?= $puedeAcciones ? 4 : 3 ?>"
                        class="text-center">

                        No se encontraron reportes de baja.

                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>

        <div class="pagination-wrapper">

            <div class="pagination-info">

                Página
                <span><?= $pagina_actual ?></span>

                de
                <span><?= $total_paginas ?></span>

            </div>


            <div class="pagination-controls">

                <button type="button"
                        class="pagination-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>"
                        <?= $pagina_actual <= 1 ? 'disabled' : '' ?>

                        onclick="cargarReportes(
                            <?= $pagina_actual - 1 ?>,
                            <?= $js($estatus_filtro) ?>,
                            <?= $js($busqueda) ?>
                        )">

                    ← Anterior

                </button>


                <div class="pagination-current">
                    Página <?= $pagina_actual ?>
                </div>


                <button type="button"
                        class="pagination-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>"
                        <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>

                        onclick="cargarReportes(
                            <?= $pagina_actual + 1 ?>,
                            <?= $js($estatus_filtro) ?>,
                            <?= $js($busqueda) ?>
                        )">

                    Siguiente →

                </button>

            </div>

        </div>

    <?php endif; ?>

</div>