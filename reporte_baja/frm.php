<?php

/* =========================================================
   ROL
   ========================================================= */

$rolFormulario = strtoupper(
    trim(
        $rol ??
        ($_SESSION['rol'] ?? '')
    )
);


if ($rolFormulario === 'ADMINISTRADOR') {
    $rolFormulario = 'ADMIN';
}


if (
    in_array(
        $rolFormulario,
        ['RH', 'RECURSOS HUMANOS'],
        true
    )
) {
    $rolFormulario = 'RRHH';
}


/* =========================================================
   PERMISOS
   ========================================================= */

if (
    !in_array(
        $rolFormulario,
        ['ADMIN', 'PROPIETARIO', 'RRHH'],
        true
    )
) {

    echo '
        <div class="alert alert-danger">
            No tienes permiso para utilizar este formulario.
        </div>
    ';

    return;
}


/*
   ADMIN:
   SOLO CONSULTA

   PROPIETARIO / RRHH:
   PUEDEN SOLICITAR BAJA
*/

$puedeSolicitar =
    in_array(
        $rolFormulario,
        ['PROPIETARIO', 'RRHH'],
        true
    );


$esRRHH =
    $rolFormulario === 'RRHH';


/*
   SOLO EL PROPIETARIO PUEDE EVALUAR
*/

$puedeEvaluar =
    $rolFormulario === 'PROPIETARIO';


/* =========================================================
   OPERADORES CON BAJA PENDIENTE
   ========================================================= */

$ids_pendientes = [];


if (
    $puedeSolicitar &&
    class_exists('db')
) {

    $db_check = new db();

    $res = $db_check->obtenerRegistros(
        "SELECT DISTINCT id_operador
         FROM reportes_baja
         WHERE estatus_evaluacion = 'PENDIENTE'"
    );

    $ids_pendientes = array_map(
        'intval',
        array_column(
            $res,
            'id_operador'
        )
    );
}


/* =========================================================
   MOTIVOS
   ========================================================= */

$motivos = [

    'ROBO' =>
        'Robo',

    'GASTO_COMBUSTIBLE' =>
        'Gasto Excesivo de Combustible',

    'CHOQUES' =>
        'Choques / Colisiones',

    'MULTAS' =>
        'Multas / Infracciones',

    'FALTAS' =>
        'Faltas / Inasistencias',

    'RENUNCIA_VOLUNTARIA' =>
        'Renuncia Voluntaria',

    'DESPIDO' =>
        'Despido',

    'ABANDONO_TRABAJO' =>
        'Abandono de Trabajo',

    'INCUMPLIMIENTO' =>
        'Incumplimiento',

    'OTRO' =>
        'Otro'
];


/* =========================================================
   CRITERIOS DE EVALUACIÓN
   ========================================================= */

$criteriosServicio = [

    'eval_distancia' =>
        ['🛣️', 'Distancia (KM)'],

    'eval_tiempo' =>
        ['⏱️', 'Horas de Servicio'],

    'eval_ganancias' =>
        ['💰', 'Ganancias']

];


$criteriosDesempeno = [

    'eval_cuidado_vehiculo' =>
        ['🚛', 'Cuidado del Camión'],

    'eval_productividad' =>
        ['📋', 'Productividad (Días Trabajados)'],

    'eval_rendimiento' =>
        ['⛽', 'Rendimiento de Combustible'],

    'eval_cuidado_fisico' =>
        ['🛡️', 'Antidoping / Cuidado Físico']

];


/* =========================================================
   FUNCIÓN PARA MOSTRAR CRITERIOS
   ========================================================= */

function mostrarCriterios($criterios, $max)
{
    foreach (
        $criterios
        as $campo => [$icono, $nombre]
    ) {
        ?>

        <div class="eval-criterio">


            <div class="eval-nombre">

                <span class="eval-icono">
                    <?= $icono ?>
                </span>

                <?= htmlspecialchars($nombre) ?>

            </div>


            <input type="hidden"
                   name="<?= $campo ?>"
                   id="<?= $campo ?>">


            <div class="eval-botones">


                <?php for ($i = 1; $i <= $max; $i++): ?>


                    <button type="button"
                            class="eval-btn"
                            data-campo="<?= $campo ?>"
                            data-value="<?= $i ?>"
                            onclick="seleccionarEvaluacion(
                                '<?= $campo ?>',
                                <?= $i ?>,
                                this
                            )">

                        <?= $i ?>

                    </button>


                <?php endfor; ?>


            </div>


        </div>

        <?php
    }
}

?>


<?php if ($puedeSolicitar): ?>


<!-- =========================================================
     SOLICITAR BAJA
     SOLO PROPIETARIO / RRHH
     ========================================================= -->

<div class="table-header-title">

    <div class="table-tabs-wrapper">

        <button type="button"
                class="btn-agregar-op"
                onclick="abrirModalReporte()">

            + Solicitar Baja

        </button>

    </div>

</div>


<!-- =========================================================
     MODAL SOLICITUD DE BAJA
     ========================================================= -->

<div id="modalReporte"
     class="modal-overlay">


    <div class="modal-container">


        <!-- ENCABEZADO -->

        <div class="modal-header">


            <h2 class="modal-title-text">
                Solicitud de Baja de Operador
            </h2>


            <button type="button"
                    class="btn-cerrar-modal"
                    onclick="cerrarModalReporte()">

                &times;

            </button>


        </div>


        <div class="modal-body-scroll">


            <form id="frm"
                  class="form-grid"
                  action="javascript:void(0);"
                  onsubmit="guardar('reporte_baja','frm',event)">


                <input type="hidden"
                       id="id_reporte"
                       name="id_reporte"
                       value="">


                <!-- INFORMACIÓN -->

                <div style="
                    padding:12px 15px;
                    margin-bottom:18px;
                    border:1px solid var(--borde-sutil);
                    border-radius:10px;
                    color:var(--texto-secundario);
                ">

                    Esta solicitud quedará pendiente
                    hasta que el propietario revise
                    y confirme la baja del operador.

                </div>


                <!-- =================================================
                     OPERADOR / EMPRESA
                     ================================================= -->

                <div class="form-row">


                    <!-- OPERADOR -->

                    <div class="form-group">


                        <label class="form-label">
                            Operador
                        </label>


                        <select class="form-control"
                                name="id_operador"
                                id="id_operador"
                                required>


                            <option value="">
                                -- Seleccionar Operador --
                            </option>


                            <?php foreach (
                                $operadores ?? []
                                as $op
                            ):

                                $id =
                                    (int)$op['id_operador'];


                                if (
                                    (int)(
                                        $op['estatus'] ?? 1
                                    ) !== 1 ||
                                    in_array(
                                        $id,
                                        $ids_pendientes,
                                        true
                                    )
                                ) {
                                    continue;
                                }


                                $nombre = trim(
                                    ($op['nombres'] ?? '') . ' ' .
                                    ($op['primer_apellido'] ?? '') . ' ' .
                                    ($op['segundo_apellido'] ?? '')
                                );

                            ?>


                                <option value="<?= $id ?>">

                                    <?= htmlspecialchars(
                                        $nombre
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <!-- EMPRESA -->

                    <div class="form-group">


                        <label class="form-label">
                            Empresa
                        </label>


                        <?php if (
                            $esRRHH &&
                            !empty($empresas)
                        ): ?>


                            <input type="text"
                                   class="form-control"
                                   value="<?= htmlspecialchars(
                                       $empresas[0]['nombre_empresa']
                                       ?? ''
                                   ) ?>"
                                   readonly>


                            <input type="hidden"
                                   name="id_empresa"
                                   id="id_empresa"
                                   value="<?= (int)(
                                       $empresas[0]['id_empresa']
                                       ?? 0
                                   ) ?>">


                        <?php else: ?>


                            <select class="form-control"
                                    name="id_empresa"
                                    id="id_empresa"
                                    required>


                                <option value="">
                                    -- Seleccionar Empresa --
                                </option>


                                <?php foreach (
                                    $empresas ?? []
                                    as $emp
                                ): ?>


                                    <option value="<?= (int)$emp['id_empresa'] ?>">

                                        <?= htmlspecialchars(
                                            $emp['nombre_empresa']
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        <?php endif; ?>


                    </div>


                </div>


                <!-- =================================================
                     MOTIVO
                     ================================================= -->

                <div class="form-row">


                    <div class="form-group"
                         style="flex:1">


                        <label class="form-label">
                            Motivo de Baja
                        </label>


                        <select class="form-control"
                                name="motivo_baja"
                                id="motivo_baja"
                                required
                                onchange="evaluarMotivoBaja(this.value)">


                            <option value="">
                                -- Seleccionar Motivo --
                            </option>


                            <?php foreach (
                                $motivos
                                as $valor => $texto
                            ): ?>


                                <option value="<?= $valor ?>">

                                    <?= htmlspecialchars(
                                        $texto
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                </div>


                <!-- =================================================
                     OTRO
                     ================================================= -->

                <div class="form-row"
                     id="row_calif_cualitativa"
                     style="display:none">


                    <div class="form-group"
                         style="flex:1">


                        <label class="form-label">

                            📝 Especificar Motivo /
                            Comentarios

                        </label>


                        <textarea class="form-control"
                                  name="calif_cualitativa"
                                  id="calif_cualitativa"
                                  maxlength="500"
                                  rows="3"
                                  placeholder="Especifica detalladamente el motivo de la baja..."></textarea>


                    </div>


                </div>


                <!-- =================================================
                     AVISO DE EVALUACIÓN
                     ================================================= -->

                <div style="
                    margin-top:10px;
                    padding:12px 15px;
                    border-radius:10px;
                    background:rgba(234,88,12,.08);
                    border:1px solid rgba(234,88,12,.30);
                ">


                    <strong>
                        ⭐ Evaluación pendiente
                    </strong>


                    <div style="
                        margin-top:4px;
                        font-size:.9rem;
                        opacity:.8;
                    ">

                        La evaluación será realizada
                        por el propietario antes de
                        confirmar definitivamente la baja.

                    </div>


                </div>


                <!-- ALERTAS -->

                <div id="contenedor-alertas-reportes"
                     class="mt-3">
                </div>


                <!-- ACCIONES -->

                <div class="form-actions"
                     style="
                        margin-top:20px;
                        display:flex;
                        gap:12px;
                        justify-content:flex-end;
                     ">


                    <button type="button"
                            class="btn-action btn-delete"
                            onclick="cerrarModalReporte()">

                        Cancelar

                    </button>


                    <button type="submit"
                            class="btn-prof-primary">

                        Solicitar Baja

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<?php endif; ?>



<?php if ($puedeEvaluar): ?>


<!-- =========================================================
     EVALUACIÓN
     SOLO PROPIETARIO
     ========================================================= -->

<div id="modalRevisionBaja"
     class="modal-overlay">


    <div class="modal-container">


        <!-- ENCABEZADO -->

        <div class="modal-header">


            <h2 class="modal-title-text">

                ⭐ Evaluación del Operador

            </h2>


            <button type="button"
                    class="btn-cerrar-modal"
                    onclick="cerrarRevisionBaja()">

                &times;

            </button>


        </div>


        <div class="modal-body-scroll">


            <form id="frmRevisionBaja"
                  class="form-grid"
                  action="javascript:void(0);">


                <input type="hidden"
                       id="revision_id_reporte"
                       name="id_reporte">


                <!-- =================================================
                     OPERADOR / EMPRESA
                     ================================================= -->

                <div class="form-row">


                    <div class="form-group">


                        <label class="form-label">
                            Operador
                        </label>


                        <input type="text"
                               class="form-control"
                               id="revision_operador"
                               readonly>


                    </div>


                    <div class="form-group">


                        <label class="form-label">
                            Empresa
                        </label>


                        <input type="text"
                               class="form-control"
                               id="revision_empresa"
                               readonly>


                    </div>


                </div>


                <!-- =================================================
                     SERVICIO
                     ================================================= -->

                <div class="eval-seccion">


                    <div class="eval-seccion-titulo">

                        📊 Evaluación del Servicio

                    </div>


                    <div class="eval-seccion-subtitulo">

                        Escala del 1 al 5

                    </div>


                    <?php

                    mostrarCriterios(
                        $criteriosServicio,
                        5
                    );

                    ?>


                    <div class="eval-promedio">

                        Promedio de Servicio:

                        <span id="promedioServicioVista">
                            — / 5
                        </span>

                    </div>


                </div>


                <!-- =================================================
                     DESEMPEÑO
                     ================================================= -->

                <div class="eval-seccion">


                    <div class="eval-seccion-titulo">

                        🚛 Evaluación de Desempeño

                    </div>


                    <div class="eval-seccion-subtitulo">

                        Escala del 1 al 10

                    </div>


                    <?php

                    mostrarCriterios(
                        $criteriosDesempeno,
                        10
                    );

                    ?>


                </div>


                <!-- =================================================
                     GENERAL
                     ================================================= -->

                <div class="eval-resumen">


                    <div class="eval-resumen-titulo">

                        PUNTUACIÓN GENERAL

                    </div>


                    <div class="eval-puntuacion"
                         id="puntuacionGeneral">

                        — / 10

                    </div>


                    <div class="eval-clasificacion"
                         id="clasificacionGeneral">

                        Completa la evaluación

                    </div>


                </div>


                <!-- =================================================
                     ADVERTENCIA
                     ================================================= -->

                <div style="
                    margin-top:20px;
                    padding:14px 16px;
                    border-radius:10px;
                    background:rgba(220,38,38,.08);
                    border:1px solid rgba(220,38,38,.30);
                ">


                    <strong>

                        🚨 Baja definitiva

                    </strong>


                    <div style="
                        margin-top:5px;
                        font-size:.9rem;
                        opacity:.85;
                    ">

                        Al confirmar, el operador será
                        marcado como inactivo y todas las
                        calificaciones quedarán guardadas
                        permanentemente.

                    </div>


                </div>


                <!-- ACCIONES -->

                <div class="form-actions"
                     style="
                        margin-top:22px;
                        display:flex;
                        gap:12px;
                        justify-content:flex-end;
                     ">


                    <button type="button"
                            class="btn-action btn-delete"
                            onclick="cerrarRevisionBaja()">

                        Cancelar

                    </button>


                    <button type="button"
                            class="btn-prof-primary"
                            id="btnConfirmarBaja"
                            onclick="confirmarBaja()">

                        Confirmar Baja

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<?php endif; ?>