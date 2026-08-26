<?php
session_start();

include_once "../db/db.php";

$db = new db();
$db->conectar();

/* =========================
   SESIÓN
   ========================= */

$rol = strtoupper($_SESSION['rol'] ?? '');

if ($rol === 'ADMINISTRADOR') {
    $rol = 'ADMIN';
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$idEmpresa = (int)($_SESSION['id_empresa'] ?? 0);
$multiempresa = (int)($_SESSION['multiempresa'] ?? 0);

$idOperador = (int)($_GET['id_operador'] ?? 0);

if ($idOperador <= 0) {
    echo '<div class="historial-vacio">Operador no válido.</div>';
    exit;
}


/* =========================
   PERMISOS
   ========================= */

$filtroOperador = "";

if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $filtroOperador = "
        AND o.id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $idUsuario
        )
    ";

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {

    $filtroOperador =
        "AND o.id_empresa = $idEmpresa";
}


/* =========================
   OPERADOR
   ========================= */

$sql = "
    SELECT o.*, e.nombre_empresa
    FROM operadores o
    LEFT JOIN empresas e
        ON e.id_empresa = o.id_empresa
    WHERE o.id_operador = $idOperador
    $filtroOperador
    LIMIT 1
";

$res = $db->obtenerRegistros($sql);

if (empty($res)) {
    echo '<div class="historial-vacio">
        No tienes permiso para consultar este operador.
    </div>';
    exit;
}

$operador = $res[0];

$nombre = trim(
    ($operador['nombres'] ?? '') . ' ' .
    ($operador['primer_apellido'] ?? '') . ' ' .
    ($operador['segundo_apellido'] ?? '')
);

$activo = (int)$operador['estatus'] === 1;


/* =========================
   FILTRO REPORTES
   ========================= */

$filtroReporte = "";

if ($rol === 'PROPIETARIO' && $multiempresa === 1) {

    $filtroReporte = "
        AND rb.id_empresa IN (
            SELECT id_empresa
            FROM usuario_empresas
            WHERE id_usuario = $idUsuario
        )
    ";

} elseif ($rol === 'PROPIETARIO' || $rol === 'RRHH') {

    $filtroReporte =
        "AND rb.id_empresa = $idEmpresa";
}


/* =========================
   REPORTES DE BAJA
   ========================= */

$sql = "
    SELECT rb.*, e.nombre_empresa
    FROM reportes_baja rb
    LEFT JOIN empresas e
        ON e.id_empresa = rb.id_empresa
    WHERE rb.id_operador = $idOperador
    $filtroReporte
    ORDER BY rb.id_reporte DESC
";

$reportes = $db->obtenerRegistros($sql);

$db->desconectar();


/* =========================
   AYUDAS
   ========================= */

function esc($texto) {
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

function fechaHist($fecha) {
    return $fecha
        ? date('d/m/Y', strtotime($fecha))
        : 'No registrada';
}

function resultadoHist($valor) {

    $valor = (float)$valor;

    if ($valor >= 9) return 'EXCELENTE';
    if ($valor >= 8) return 'MUY BUENO';
    if ($valor >= 7) return 'BUENO';
    if ($valor >= 6) return 'REGULAR';

    return 'BAJO';
}

$motivos = [
    'ROBO' => 'Robo',
    'GASTO_COMBUSTIBLE' => 'Gasto excesivo de combustible',
    'CHOQUES' => 'Choques / Accidentes',
    'MULTAS' => 'Multas',
    'FALTAS' => 'Faltas / Inasistencias',
    'RENUNCIA_VOLUNTARIA' => 'Renuncia Voluntaria',
    'DESPIDO' => 'Despido',
    'ABANDONO_TRABAJO' => 'Abandono de Trabajo',
    'INCUMPLIMIENTO' => 'Incumplimiento',
    'OTRO' => 'Otro'
];

$puedeEvaluacion =
    $rol === 'ADMIN' ||
    $rol === 'PROPIETARIO';
?>


<!-- PERFIL -->
<div class="historial-perfil">

    <div>
        <small>OPERADOR</small>

        <h3><?= esc($nombre) ?></h3>

        <p>
            RFC:
            <?= esc($operador['rfc'] ?? 'No registrado') ?>
        </p>
    </div>

    <span class="historial-badge <?= $activo ? 'activo' : 'inactivo' ?>">
        <?= $activo ? 'ACTIVO' : 'INACTIVO' ?>
    </span>

</div>


<!-- SITUACIÓN ACTUAL -->
<h4 class="historial-titulo">
    Situación actual
</h4>

<div class="historial-actual">

    <div>
        <small>Empresa / Transportista</small>
        <strong>
            <?= esc(
                $operador['nombre_empresa']
                ?? 'Sin empresa asignada'
            ) ?>
        </strong>
    </div>

    <div>
        <small>Fecha de ingreso</small>
        <strong>
            <?= fechaHist(
                $operador['fecha_ingreso']
                ?? null
            ) ?>
        </strong>
    </div>

    <div>
        <small>Estado</small>

        <strong class="<?= $activo ? 'texto-activo' : 'texto-inactivo' ?>">
            ● <?= $activo ? 'Activo' : 'Inactivo' ?>
        </strong>
    </div>

</div>


<!-- HISTORIAL -->
<div class="historial-encabezado">

    <h4 class="historial-titulo">
        Historial de bajas
    </h4>

    <span>
        <?= count($reportes) ?>
        <?= count($reportes) === 1 ? 'registro' : 'registros' ?>
    </span>

</div>


<?php if (empty($reportes)): ?>

    <div class="historial-vacio">

        <strong>Sin reportes de baja</strong>

        <p>
            Este operador todavía no cuenta
            con registros de baja.
        </p>

    </div>

<?php else: ?>


<div class="historial-lista">


<?php foreach ($reportes as $r): ?>

<?php

$idReporte = (int)$r['id_reporte'];

$completada =
    strtoupper(
        $r['estatus_evaluacion'] ?? ''
    ) === 'COMPLETADA';

$motivo =
    $motivos[
        $r['motivo_baja'] ?? 'OTRO'
    ] ?? 'Otro';

$calificacion =
    $r['calificacion_cuantitativa'];

$nueva =
    $r['eval_distancia'] !== null &&
    $r['eval_tiempo'] !== null &&
    $r['eval_ganancias'] !== null &&
    $r['eval_cuidado_vehiculo'] !== null &&
    $r['eval_productividad'] !== null &&
    $r['eval_rendimiento'] !== null &&
    $r['eval_cuidado_fisico'] !== null;

?>


<div class="historial-card">


    <!-- CABECERA -->
    <div class="historial-card-head">

        <div>
            <small>EMPRESA / TRANSPORTISTA</small>

            <strong>
                <?= esc(
                    $r['nombre_empresa']
                    ?? 'Empresa no disponible'
                ) ?>
            </strong>
        </div>

        <span class="historial-estado <?= $completada ? 'completado' : 'pendiente' ?>">

            <?= $completada
                ? 'COMPLETADA'
                : 'PENDIENTE'
            ?>

        </span>

    </div>


    <!-- DATOS -->
    <div class="historial-card-datos">

        <div>
            <small>Ingreso</small>

            <strong>
                <?= fechaHist(
                    $r['fecha_ingreso']
                    ?? null
                ) ?>
            </strong>
        </div>


        <div>
            <small>Baja</small>

            <strong>
                <?= $completada
                    ? fechaHist(
                        $r['fecha_baja']
                        ?? null
                    )
                    : 'Pendiente'
                ?>
            </strong>
        </div>


        <div class="historial-motivo">
            <small>Motivo de baja</small>

            <strong>
                <?= esc($motivo) ?>
            </strong>
        </div>

    </div>


    <?php if (
        $completada &&
        $puedeEvaluacion &&
        $calificacion !== null
    ): ?>


    <!-- EVALUACIÓN -->
    <div class="historial-evaluacion">

        <div>
            <small>Evaluación final</small>

            <strong>
                ⭐ <?= number_format(
                    (float)$calificacion,
                    2
                ) ?> / 10
            </strong>
        </div>

        <div>
            <small>Resultado</small>

            <strong>
                <?= resultadoHist(
                    $calificacion
                ) ?>
            </strong>
        </div>

    </div>


    <!-- BOTONES -->
    <div class="historial-acciones">

        <button
            type="button"
            class="btn-action btn-ver-evaluacion"

            data-tipo="<?= $nueva ? 'nueva' : 'anterior' ?>"

            data-operador="<?= esc($nombre) ?>"

            data-empresa="<?= esc(
                $r['nombre_empresa']
                ?? ''
            ) ?>"

            data-motivo="<?= esc($motivo) ?>"

            data-general="<?= esc(
                $calificacion
            ) ?>"

            data-promedio="<?= esc(
                $r['promedio_servicio']
                ?? 0
            ) ?>"

            data-distancia="<?= esc(
                $r['eval_distancia']
                ?? 0
            ) ?>"

            data-tiempo="<?= esc(
                $r['eval_tiempo']
                ?? 0
            ) ?>"

            data-ganancias="<?= esc(
                $r['eval_ganancias']
                ?? 0
            ) ?>"

            data-cuidado="<?= esc(
                $r['eval_cuidado_vehiculo']
                ?? 0
            ) ?>"

            data-productividad="<?= esc(
                $r['eval_productividad']
                ?? 0
            ) ?>"

            data-rendimiento="<?= esc(
                $r['eval_rendimiento']
                ?? 0
            ) ?>"

            data-fisico="<?= esc(
                $r['eval_cuidado_fisico']
                ?? 0
            ) ?>"

            onclick="verEvaluacionBaja(this)"
        >
            📊 Ver Evaluación
        </button>


        <button
            type="button"
            class="btn-action btn-constancia-laboral"

            onclick="
                window.open(
                    '/reporte_baja/constancia.php?id=<?= $idReporte ?>',
                    '_blank'
                )
            "
        >
            📄 Constancia Laboral
        </button>

    </div>


    <?php endif; ?>


</div>


<?php endforeach; ?>


</div>

<?php endif; ?>