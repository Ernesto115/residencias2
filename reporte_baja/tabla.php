<?php
$rolTabla = strtoupper($rol ?? ($_SESSION['rol'] ?? ''));
$esRRHH = ($rolTabla === 'RRHH');

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
                    <th class="text-center">Estado</th>

                    <?php if (!$esRRHH): ?>
                        <th class="text-center">Calificación</th>
                        <th class="text-center">Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>

            <?php if (!empty($datos2)): ?>

                <?php foreach ($datos2 as $r): ?>

                    <?php
                    $id_reporte = (int)($r['id_reporte'] ?? 0);
                    $estatus = strtoupper($r['estatus_evaluacion'] ?? 'PENDIENTE');
                    $calif = (int)($r['calificacion_cuantitativa'] ?? 0);
                    $motivo = strtoupper($r['motivo_baja'] ?? '');

                    $motivoMostrar = $motivosTexto[$motivo] ?? $motivo;

                    if ($motivo === 'OTRO' && !empty($r['calif_cualitativa'])) {
                        $motivoMostrar = 'Otro: ' . $r['calif_cualitativa'];
                    }

                    /* COLOR DE CALIFICACIÓN */
                    if ($calif >= 1 && $calif <= 3) {
                        $bg = 'rgba(220,53,69,.15)';
                        $border = '#dc3545';
                        $color = '#ff6b6b';

                    } elseif ($calif >= 4 && $calif <= 5) {
                        $bg = 'rgba(253,126,20,.15)';
                        $border = '#fd7e14';
                        $color = '#ffa502';

                    } elseif ($calif >= 6 && $calif <= 7) {
                        $bg = 'rgba(255,193,7,.15)';
                        $border = '#ffc107';
                        $color = '#eccc68';

                    } elseif ($calif >= 8 && $calif <= 10) {
                        $bg = 'rgba(25,135,84,.15)';
                        $border = '#198754';
                        $color = '#2ed573';

                    } else {
                        $bg = 'rgba(108,117,125,.15)';
                        $border = '#6c757d';
                        $color = '#a4b0be';
                    }

                    /* DATOS PARA EL MODAL */
                    $jsOperador = htmlspecialchars(
                        json_encode($r['nombre_operador'] ?? '', JSON_UNESCAPED_UNICODE),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $jsEmpresa = htmlspecialchars(
                        json_encode($r['nombre_empresa'] ?? '', JSON_UNESCAPED_UNICODE),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $jsMotivo = htmlspecialchars(
                        json_encode($motivoMostrar, JSON_UNESCAPED_UNICODE),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                    <tr>

                        <!-- OPERADOR -->
                        <td class="font-medium">
                            <?= htmlspecialchars($r['nombre_operador'] ?? '') ?>
                        </td>

                        <!-- EMPRESA -->
                        <td>
                            <?= htmlspecialchars($r['nombre_empresa'] ?? '') ?>
                        </td>

                        <!-- MOTIVO -->
                        <td>
                            <span class="badge-role role-default">
                                <?= htmlspecialchars($motivoMostrar) ?>
                            </span>
                        </td>

                        <!-- ESTADO -->
                        <td class="text-center"
                            id="estado-reporte-<?= $id_reporte ?>">

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

                            <?php elseif ($estatus === 'COMPLETADA'): ?>

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


                        <!-- RRHH NO VE CALIFICACIÓN NI ACCIÓN -->
                        <?php if (!$esRRHH): ?>

                            <!-- CALIFICACIÓN -->
                            <td class="text-center font-medium"
                                id="calificacion-reporte-<?= $id_reporte ?>">

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
                                        ⭐ <?= $calif ?> / 10
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


                            <!-- ACCIÓN -->
                            <td class="text-center"
                                id="accion-reporte-<?= $id_reporte ?>">

                                <?php if ($estatus === 'PENDIENTE'): ?>

                                    <?php if (
                                        $rolTabla === 'PROPIETARIO' ||
                                        $rolTabla === 'ADMIN' ||
                                        $rolTabla === 'ADMINISTRADOR'
                                    ): ?>

                                        <button type="button"
                                                class="btn-action btn-edit"
                                                onclick="abrirRevisionBaja(
                                                    <?= $id_reporte ?>,
                                                    <?= $jsOperador ?>,
                                                    <?= $jsEmpresa ?>,
                                                    <?= $jsMotivo ?>
                                                )">
                                            ⭐ Revisar y Calificar
                                        </button>

                                    <?php endif; ?>


                                <?php elseif ($estatus === 'COMPLETADA'): ?>

                                    <button type="button"
                                            class="btn-action btn-edit"
                                            onclick="window.open(
                                                '/reporte_baja/constancia.php?id=<?= $id_reporte ?>',
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