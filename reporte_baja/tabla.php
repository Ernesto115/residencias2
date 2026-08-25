<?php

$rolTabla = strtoupper($rol ?? ($_SESSION['rol'] ?? ''));
$esRRHH = $rolTabla === 'RRHH';


/* =========================================================
   MOTIVOS
   ========================================================= */
$motivosTexto = [
    'ROBO' => 'Robo',
    'GASTO_COMBUSTIBLE' => 'Gasto de Combustible',
    'CHOQUES' => 'Choques / Colisiones',
    'MULTAS' => 'Multas / Infracciones',
    'FALTAS' => 'Faltas / Inasistencias',
    'RENUNCIA_VOLUNTARIA' => 'Renuncia Voluntaria',
    'DESPIDO' => 'Despido',
    'ABANDONO_TRABAJO' => 'Abandono de Trabajo',
    'INCUMPLIMIENTO' => 'Incumplimiento',
    'OTRO' => 'Otro'
];


/* =========================================================
   COLOR DE CALIFICACIÓN
   ========================================================= */
function colorCalificacion($n)
{
    if ($n >= 8) {
        return ['rgba(25,135,84,.15)', '#198754', '#2ed573'];
    }

    if ($n >= 7) {
        return ['rgba(234,179,8,.15)', '#eab308', '#facc15'];
    }

    if ($n >= 6) {
        return ['rgba(249,115,22,.15)', '#f97316', '#fb923c'];
    }

    if ($n > 0) {
        return ['rgba(220,53,69,.15)', '#dc3545', '#ff6b6b'];
    }

    return ['rgba(108,117,125,.15)', '#6c757d', '#a4b0be'];
}


/* =========================================================
   CAMPOS DEL NUEVO FORMATO
   ========================================================= */
$camposEvaluacion = [
    'eval_distancia',
    'eval_tiempo',
    'eval_ganancias',
    'eval_cuidado_vehiculo',
    'eval_productividad',
    'eval_rendimiento',
    'eval_cuidado_fisico'
];

?>


<div class="table-container">

    <div class="table-header-title">
        <h3>Tabla de Reportes de Baja</h3>
    </div>


    <div class="table-responsive">

        <table class="custom-table">

            <thead>
                <tr>

                    <th>Operador</th>

                    <th>Empresa</th>

                    <th>Motivo de Baja</th>

                    <th class="text-center">
                        Estado
                    </th>


                    <?php if (!$esRRHH): ?>

                        <th class="text-center">
                            Calificación
                        </th>

                        <th class="text-center">
                            Acción
                        </th>

                    <?php endif; ?>

                </tr>
            </thead>


            <tbody>

            <?php if (!empty($datos2)): ?>


                <?php foreach ($datos2 as $r): ?>

                    <?php

                    /* =================================================
                       DATOS PRINCIPALES
                       ================================================= */
                    $id =
                        (int)($r['id_reporte'] ?? 0);

                    $estatus =
                        strtoupper(
                            $r['estatus_evaluacion'] ?? 'PENDIENTE'
                        );

                    $calif =
                        is_numeric(
                            $r['calificacion_cuantitativa'] ?? null
                        )
                        ? (float)$r['calificacion_cuantitativa']
                        : 0;


                    /* =================================================
                       MOTIVO
                       ================================================= */
                    $motivo =
                        strtoupper(
                            $r['motivo_baja'] ?? ''
                        );

                    $motivoMostrar =
                        $motivosTexto[$motivo] ?? $motivo;


                    if (
                        $motivo === 'OTRO' &&
                        !empty($r['calif_cualitativa'])
                    ) {

                        $motivoMostrar =
                            'Otro: ' .
                            $r['calif_cualitativa'];
                    }


                    /* =================================================
                       DETECTAR FORMATO NUEVO / ANTERIOR
                       ================================================= */
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
                        $tieneEvaluacion
                        ? 'nueva'
                        : 'anterior';


                    /* =================================================
                       PROMEDIO DE SERVICIO
                       ================================================= */
                    $promedioServicio = 0;


                    if ($tieneEvaluacion) {

                        if (
                            isset($r['promedio_servicio']) &&
                            $r['promedio_servicio'] !== null
                        ) {

                            $promedioServicio =
                                (float)$r['promedio_servicio'];

                        } else {

                            $promedioServicio =
                                (
                                    (float)$r['eval_distancia'] +
                                    (float)$r['eval_tiempo'] +
                                    (float)$r['eval_ganancias']
                                ) / 3;
                        }
                    }


                    /* =================================================
                       COLOR
                       ================================================= */
                    [$bg, $border, $color] =
                        colorCalificacion($calif);


                    /* =================================================
                       DATOS JS
                       ================================================= */
                    $jsOperador = htmlspecialchars(
                        json_encode(
                            $r['nombre_operador'] ?? '',
                            JSON_UNESCAPED_UNICODE
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    );


                    $jsEmpresa = htmlspecialchars(
                        json_encode(
                            $r['nombre_empresa'] ?? '',
                            JSON_UNESCAPED_UNICODE
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    );


                    $jsMotivo = htmlspecialchars(
                        json_encode(
                            $motivoMostrar,
                            JSON_UNESCAPED_UNICODE
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>


                    <tr>


                        <!-- =================================================
                             OPERADOR
                             ================================================= -->

                        <td class="font-medium">

                            <?= htmlspecialchars(
                                $r['nombre_operador'] ?? ''
                            ) ?>

                        </td>



                        <!-- =================================================
                             EMPRESA
                             ================================================= -->

                        <td>

                            <?= htmlspecialchars(
                                $r['nombre_empresa'] ?? ''
                            ) ?>

                        </td>



                        <!-- =================================================
                             MOTIVO
                             ================================================= -->

                        <td>

                            <span class="badge-role role-default">

                                <?= htmlspecialchars(
                                    $motivoMostrar
                                ) ?>

                            </span>

                        </td>



                        <!-- =================================================
                             ESTADO
                             ================================================= -->

                        <td class="text-center"
                            id="estado-reporte-<?= $id ?>">


                            <?php if ($estatus === 'PENDIENTE'): ?>


                                <span style="
                                    background:rgba(245,158,11,.12);
                                    border:1px solid #f59e0b;
                                    color:#fbbf24;
                                    padding:5px 12px;
                                    border-radius:20px;
                                    font-weight:bold;
                                    font-size:.82rem;
                                ">

                                    ⏳ PENDIENTE

                                </span>


                            <?php else: ?>


                                <span style="
                                    background:rgba(16,185,129,.12);
                                    border:1px solid #10b981;
                                    color:#34d399;
                                    padding:5px 12px;
                                    border-radius:20px;
                                    font-weight:bold;
                                    font-size:.82rem;
                                ">

                                    ✅ COMPLETADA

                                </span>


                            <?php endif; ?>


                        </td>



                        <?php if (!$esRRHH): ?>


                            <!-- =================================================
                                 CALIFICACIÓN
                                 ================================================= -->

                            <td class="text-center font-medium"
                                id="calificacion-reporte-<?= $id ?>">


                                <?php if ($calif > 0): ?>


                                    <span style="
                                        background:<?= $bg ?>;
                                        border:1px solid <?= $border ?>;
                                        color:<?= $color ?>;
                                        padding:4px 12px;
                                        border-radius:20px;
                                        font-weight:bold;
                                        font-size:.85rem;
                                    ">

                                        ⭐ <?= number_format(
                                            $calif,
                                            2
                                        ) ?> / 10

                                    </span>


                                <?php else: ?>


                                    <span style="
                                        background:rgba(108,117,125,.15);
                                        border:1px solid #6c757d;
                                        color:#a4b0be;
                                        padding:4px 12px;
                                        border-radius:20px;
                                        font-size:.82rem;
                                    ">

                                        N/A

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- =================================================
                                 ACCIONES
                                 ================================================= -->

                            <td class="text-center"
                                id="accion-reporte-<?= $id ?>">


                                <?php if ($estatus === 'PENDIENTE'): ?>


                                    <!-- REVISAR / EVALUAR -->
                                    <button type="button"
                                            class="btn-action btn-edit"

                                            onclick="abrirRevisionBaja(
                                                <?= $id ?>,
                                                <?= $jsOperador ?>,
                                                <?= $jsEmpresa ?>,
                                                <?= $jsMotivo ?>
                                            )">

                                        ⭐ Revisar y Evaluar

                                    </button>



                                <?php elseif ($estatus === 'COMPLETADA'): ?>


                                    <!-- =================================================
                                         VER EVALUACIÓN
                                         NUEVA O FORMATO ANTERIOR
                                         ================================================= -->

                                    <button type="button"
                                            class="btn-action btn-edit"

                                            data-tipo="<?= $tipoEvaluacion ?>"

                                            data-operador="<?= htmlspecialchars(
                                                $r['nombre_operador'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-empresa="<?= htmlspecialchars(
                                                $r['nombre_empresa'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-general="<?= number_format(
                                                $calif,
                                                2,
                                                '.',
                                                ''
                                            ) ?>"


                                            <?php if ($tieneEvaluacion): ?>


                                                data-promedio="<?= number_format(
                                                    $promedioServicio,
                                                    2,
                                                    '.',
                                                    ''
                                                ) ?>"

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



                                    <!-- =================================================
                                         CONSTANCIA LABORAL
                                         ================================================= -->

                                    <button type="button"
                                            class="btn-action btn-edit"

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

                    <td colspan="<?= $esRRHH ? 4 : 6 ?>"
                        class="text-center">

                        No hay reportes de baja registrados.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>