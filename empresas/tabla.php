<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   SEGURIDAD
   ========================================================= */

$rolTabla = strtoupper(trim($_SESSION['rol'] ?? ''));

if ($rolTabla === 'ADMINISTRADOR') {
    $rolTabla = 'ADMIN';
}

if (!in_array($rolTabla, ['ADMIN', 'PROPIETARIO'], true)) {

    http_response_code(403);

    exit(
        '<div class="alert alert-danger">
            No tienes permiso para consultar empresas.
        </div>'
    );
}


$datos2 = $datos2 ?? [];


/* =========================================================
   ESCAPAR TEXTO
   ========================================================= */

function eEmpresa($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>


<style>

/* =========================================================
   TABLA COMPACTA DE EMPRESAS
   ========================================================= */

#tablaEmpresas th {
    white-space: nowrap;
}

#tablaEmpresas td {
    padding-top: 10px;
    padding-bottom: 10px;
    vertical-align: middle;
}

.empresa-nombre {
    font-weight: 600;
    line-height: 1.2;
}

.empresa-razon {
    margin-top: 2px;
    font-size: .79rem;
    color: var(--texto-secundario);
    line-height: 1.2;
}

.empresa-direccion,
.empresa-responsable {
    font-size: .88rem;
}

.empresa-acciones {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.empresa-acciones .btn-action {
    width: 34px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.empresa-buscador {
    position: relative;
    width: 260px;
    max-width: 100%;
}

.empresa-buscador input {
    height: 38px;
    padding-right: 32px;
    font-size: .88rem;
}

.empresa-limpiar {
    position: absolute;
    right: 7px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--texto-secundario);
    cursor: pointer;
    font-size: 1.1rem;
    display: none;
}

@media (max-width: 768px) {

    .empresa-buscador {
        width: 100%;
    }
}

</style>



<div class="table-container">


    <!-- =====================================================
         CABECERA
         ===================================================== -->

    <div
        class="table-header-title"
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            flex-wrap:wrap;
            gap:10px;
            padding:12px 15px;
        "
    >

        <h3 style="margin:0;">
            Empresas registradas
        </h3>


        <!-- BUSCADOR -->
        <div class="empresa-buscador">

            <input
                type="text"
                id="inputBuscadorEmpresa"
                class="form-control"
                placeholder="🔍 Buscar empresa..."
                autocomplete="off"
                oninput="filtrarEmpresasTabla()"
            >

            <button
                type="button"
                id="btnLimpiarEmpresa"
                class="empresa-limpiar"
                onclick="limpiarBuscadorEmpresa()"
                title="Limpiar búsqueda"
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
            class="custom-table"
            id="tablaEmpresas"
        >

            <thead>

                <tr>

                    <th>
                        Empresa
                    </th>

                    <th>
                        Dirección Fiscal
                    </th>

                    <th>
                        Responsable
                    </th>

                    <th class="text-center">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (!empty($datos2)): ?>


                <?php foreach ($datos2 as $dato):

                    $id = (int)($dato['id_empresa'] ?? 0);

                    $nombre =
                        trim($dato['nombre_empresa'] ?? '');

                    $razon =
                        trim($dato['razon_social'] ?? '');

                    $direccion =
                        trim($dato['direccion_fiscal'] ?? '');

                    $responsable =
                        trim($dato['responsable'] ?? '');

                    $busqueda = strtolower(
                        $nombre . ' ' .
                        $razon . ' ' .
                        $direccion . ' ' .
                        $responsable
                    );

                ?>


                    <tr
                        class="fila-empresa"
                        data-busqueda="<?= eEmpresa($busqueda) ?>"
                    >


                        <!-- EMPRESA -->
                        <td>

                            <div class="empresa-nombre">
                                <?= eEmpresa($nombre) ?>
                            </div>

                            <div class="empresa-razon">
                                <?= eEmpresa($razon) ?>
                            </div>

                        </td>



                        <!-- DIRECCIÓN -->
                        <td>

                            <span class="empresa-direccion">
                                <?= eEmpresa($direccion) ?>
                            </span>

                        </td>



                        <!-- RESPONSABLE -->
                        <td>

                            <span class="empresa-responsable">
                                <?= eEmpresa($responsable) ?>
                            </span>

                        </td>



                        <!-- ACCIONES -->
                        <td class="text-center">

                            <div class="empresa-acciones">


                                <button
                                    type="button"
                                    class="btn-action btn-edit"
                                    onclick="editar(
                                        '<?= $id ?>',
                                        'empresas',
                                        'frm'
                                    )"
                                    title="Editar empresa"
                                >
                                    ✏️
                                </button>


                                <button
                                    type="button"
                                    class="btn-action btn-delete"
                                    onclick="eliminar(
                                        '<?= $id ?>',
                                        'empresas'
                                    )"
                                    title="Eliminar empresa"
                                >
                                    🗑️
                                </button>


                            </div>

                        </td>


                    </tr>


                <?php endforeach; ?>



                <!-- SIN RESULTADOS -->
                <tr
                    id="filaSinEmpresas"
                    style="display:none;"
                >

                    <td
                        colspan="4"
                        class="text-center"
                        style="padding:20px;"
                    >
                        No se encontraron empresas.
                    </td>

                </tr>


            <?php else: ?>


                <tr>

                    <td
                        colspan="4"
                        class="text-center"
                        style="padding:20px;"
                    >
                        No hay empresas registradas.
                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>



<script>

/* =========================================================
   BUSCADOR DE EMPRESAS
   ========================================================= */

function filtrarEmpresasTabla()
{
    const input =
        document.getElementById('inputBuscadorEmpresa');

    if (!input) return;


    const termino =
        input.value.toLowerCase().trim();


    const filas =
        document.querySelectorAll(
            '#tablaEmpresas .fila-empresa'
        );


    const btnLimpiar =
        document.getElementById('btnLimpiarEmpresa');


    const sinResultados =
        document.getElementById('filaSinEmpresas');


    let visibles = 0;


    filas.forEach(fila => {

        const texto =
            (fila.dataset.busqueda || '').toLowerCase();


        const coincide =
            texto.includes(termino);


        fila.style.display =
            coincide ? '' : 'none';


        if (coincide) {
            visibles++;
        }
    });


    if (btnLimpiar) {

        btnLimpiar.style.display =
            termino !== ''
                ? 'block'
                : 'none';
    }


    if (sinResultados) {

        sinResultados.style.display =
            termino !== '' && visibles === 0
                ? ''
                : 'none';
    }
}



/* =========================================================
   LIMPIAR BUSCADOR
   ========================================================= */

function limpiarBuscadorEmpresa()
{
    const input =
        document.getElementById('inputBuscadorEmpresa');


    if (!input) return;


    input.value = '';

    filtrarEmpresasTabla();

    input.focus();
}

</script>