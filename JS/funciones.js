/* =========================================================
   ARCHIVO PRINCIPAL DE FUNCIONES JS
   Navegación, Tema, AJAX, CRUD y Login
   ========================================================= */


/* =========================================================
   1. NAVEGACIÓN
   ========================================================= */

// Carga una página o módulo dentro del contenido principal.
function ver(ruta) {
    const contenedor = document.getElementById('contenido-principal');

    // Si no existe el contenedor principal, utiliza contenedor1.
    if (!contenedor) {
        let div1 = document.querySelector("#contenedor1");

        if (div1) {
            fetch(ruta)
                .then(response => response.text())
                .then(data => { div1.innerHTML = data; })
                .catch(error => console.error("Error al cargar la página:", error));
        }

        return;
    }

    // Mensaje mientras carga el módulo.
    contenedor.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 texto-secundario">Cargando módulo...</p>
        </div>
    `;

    fetch(ruta)
        .then(response => {
            if (!response.ok) {
                throw new Error('No se pudo cargar el módulo solicitado.');
            }

            return response.text();
        })
        .then(html => {
            contenedor.innerHTML = html;
        })
        .catch(error => {
            contenedor.innerHTML = `
                <div class="alert alert-danger m-3" role="alert">
                    <h4 class="alert-heading">⚠️ Error de Carga</h4>
                    <p class="mb-0">${error.message} (Ruta: <code>${ruta}</code>).</p>
                </div>
            `;
        });
}


// Ir al apartado de empresas.
function irAApartadoEmpresas() {
    window.location.href = "lista_empresas.php";
}


// Ir al apartado de operadores.
function irAApartadoOperadores() {
    window.location.href = "lista_operadores.php";
}


// Ir al apartado de usuarios.
function irAApartadoUsuarios() {
    window.location.href = "lista_usuarios.php";
}



/* =========================================================
   2. TEMA CLARO / OSCURO
   ========================================================= */

// Cambia entre modo claro y oscuro.
function alternarTema() {
    const html = document.documentElement;
    const temaActual = html.getAttribute('data-theme');
    const nuevoTema = temaActual === 'dark' ? 'light' : 'dark';

    html.setAttribute('data-theme', nuevoTema);
    localStorage.setItem('tema_sistema', nuevoTema);

    actualizarElementosInterfaz(nuevoTema);
}


// Cambia el icono y texto del botón de tema.
function actualizarElementosInterfaz(tema) {
    const icono = document.getElementById('icono-tema');
    const texto = document.getElementById('texto-tema');

    if (!icono || !texto) return;

    if (tema === 'dark') {
        icono.textContent = '☀️';
        texto.textContent = 'Modo Claro';
    } else {
        icono.textContent = '🌙';
        texto.textContent = 'Modo Oscuro';
    }
}


// Aplica automáticamente el tema guardado.
(function() {
    const temaGuardado = localStorage.getItem('tema_sistema') || 'light';

    document.documentElement.setAttribute('data-theme', temaGuardado);

    document.addEventListener('DOMContentLoaded', () => {
        actualizarElementosInterfaz(temaGuardado);
    });
})();



/* =========================================================
   3. CONTROL DE INTERFAZ
   ========================================================= */

// Muestra el selector de empresa dependiendo del rol.
function controlarDespliegueEmpresa() {
    const rolSelect = document.getElementById('rol');
    const seccionEmpresa = document.getElementById('seccion_empresa');

    if (!rolSelect || !seccionEmpresa) return;

    const rolVal = rolSelect.value;

    if (
        rolVal === 'EMPRESA' ||
        rolVal === 'OPERADOR' ||
        rolVal === 'PROPIETARIO' ||
        rolVal === 'RRHH'
    ) {
        seccionEmpresa.style.display = 'block';

    } else {
        seccionEmpresa.style.display = 'none';

        const selectEmpresa = document.getElementById('id_empresa');

        if (selectEmpresa) {
            selectEmpresa.value = '';
        }
    }
}



/* =========================================================
   4. FORMULARIOS GENERALES AJAX
   ========================================================= */

// Guarda formularios mediante Fetch y muestra mensajes.
function guardarFormularioGenerico(event, idFormulario, urlBackend, idContenedorAlertas) {
    if (event) event.preventDefault();

    const formulario = document.getElementById(idFormulario);
    const contenedorAlertas = document.getElementById(idContenedorAlertas);

    if (!formulario || !contenedorAlertas) {
        console.error("No se encontró el formulario o el contenedor de alertas.");
        return;
    }

    const datosFormulario = new FormData(formulario);

    contenedorAlertas.innerHTML = `
        <div class="text-muted small">
            Procesando información con el servidor...
        </div>
    `;

    fetch(urlBackend, {
        method: 'POST',
        body: datosFormulario
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor.');
        }

        return response.json();
    })
    .then(data => {
        const esExito = data.status === 'success';

        contenedorAlertas.innerHTML = `
            <div class="alert alert-${esExito ? 'success' : 'danger'} alert-dismissible fade show mt-2" role="alert">
                ${esExito ? '✨ <strong>¡Éxito!</strong> ' : '⚠️ <strong>Error:</strong> '}
                ${data.message}

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        `;

        if (esExito) {
            formulario.reset();

            const idOculto = formulario.querySelector('input[type="hidden"]');

            if (idOculto) {
                idOculto.value = '';
            }

            if (typeof controlarDespliegueEmpresa === 'function') {
                controlarDespliegueEmpresa();
            }

            cerrarModalActivo();
        }
    })
    .catch(error => {
        console.error('Error detectado:', error);

        contenedorAlertas.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                ⚠️ Error de conexión de red o fallo interno del backend PHP.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>
            </div>
        `;
    });
}



/* =========================================================
   5. FORMULARIOS POR MÓDULO
   ========================================================= */

// Guardar operador.
function guardarOperador(event) {
    guardarFormularioGenerico(
        event,
        'formGuardarOperador',
        'operadores/guardar.php',
        'contenedor-alertas-operadores'
    );
}


// Guardar usuario.
function enviarFormularioUsuario(event) {
    guardarFormularioGenerico(
        event,
        'formGuardarUsuario',
        'usuarios/guardar.php',
        'contenedor-alertas'
    );
}


// Guardar empresa.
function guardarModuloEmpresas(event) {
    guardarFormularioGenerico(
        event,
        'formGuardarEmpresa',
        'empresas/guardar.php',
        'contenedor-alertas-empresas'
    );
}



/* =========================================================
   6. AUTENTICACIÓN ANTIGUA
   ========================================================= */

// Inicio de sesión anterior del sistema.
function enviar() {
    let div1 = document.querySelector("#contenedorErroresLogin");
    let div2 = document.querySelector("#contenedor2");

    const usuario = document.querySelector("#usuario").value;
    const clave = document.querySelector("#clave").value;

    fetch("/autenticacion/validarusuario.php?usuario=" + usuario + "&clave=" + clave)
        .then(response => response.text())
        .then(data => {
            if (
                data.includes("Clave incorrecta") ||
                data.includes("Usuario no valido")
            ) {
                div1.innerHTML = data;
                div2.innerHTML = "";

            } else {
                div2.innerHTML = data;
                div1.innerHTML = "";

                habilitarBotonesMenu();
            }
        });
}


// Habilita los botones del menú.
function habilitarBotonesMenu() {
    for (let i = 1; i <= 8; i++) {
        let btn = document.getElementById("btnFormulario" + i);

        if (btn) {
            btn.disabled = false;
        }
    }
}



/* =========================================================
   7. CRUD GENERAL
   ========================================================= */

// Guarda o actualiza registros de cualquier módulo.
function guardar(tb, pfrm, event) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }


    // Buscar formulario.
    let frm =
        document.getElementById(pfrm) ||
        document.getElementById(
            'frm' +
            tb.charAt(0).toUpperCase() +
            tb.slice(1).replace(/s$/, '')
        ) ||
        document.getElementById('frm');


    // Buscar contenedor de la tabla.
    let cont =
        document.querySelector("#contenedor3") ||
        document.querySelector(".table-container") ||
        document.querySelector(".table-responsive");


    if (!frm) {
        console.error(
            "⚠️ No se encontró el formulario:",
            pfrm,
            "para la tabla:",
            tb
        );

        alert("Error: No se encontró el formulario " + pfrm);

        return;
    }


    // Validar campos obligatorios.
    if (!frm.checkValidity()) {
        frm.reportValidity();
        return;
    }


    // Detectar si se está editando.
    let singularTb = tb.replace(/s$/, '');

    let campoId =
        frm.querySelector(`#id_${singularTb}`) ||
        frm.querySelector(`#id_${tb}`) ||
        frm.querySelector('input[type="hidden"]');

    let esEditar =
        campoId &&
        campoId.value !== "" &&
        campoId.value !== "0";


    // Confirmar reporte de baja.
    if (
        (tb === 'reporte_baja' || tb === 'reportes_baja') &&
        !esEditar &&
        !window.confirmadoBaja
    ) {

        if (typeof Swal !== 'undefined') {

            Swal.fire({
                title: '¿Seguro que deseas grabar este reporte?',
                text: 'Esta acción es irreversible y afectará el historial del operador.',
                icon: 'warning',
                iconColor: '#dc2626',
                showCancelButton: true,
                confirmButtonText: 'Sí, Grabar Reporte',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#334155',
                background: '#1e293b',
                color: '#f8fafc',
                heightAuto: false,

                didOpen: () => {
                    const swalContainer =
                        document.querySelector('.swal2-container');

                    if (swalContainer) {
                        swalContainer.style.zIndex = '999999';
                    }
                }

            }).then(result => {

                if (result.isConfirmed) {
                    window.confirmadoBaja = true;

                    guardar(tb, pfrm, null);

                    window.confirmadoBaja = false;
                }

            });

            return;

        } else if (
            !confirm(
                "¿Seguro que deseas grabar este reporte? " +
                "Esta acción afectará el historial del operador."
            )
        ) {
            return;
        }
    }


    // Preparar datos.
    let datos = new FormData(frm);

    if (!datos.has('tabla')) {
        datos.append('tabla', tb);
    }


    // Determinar la ruta correcta.
    let enSubcarpeta =
        window.location.pathname.includes('/' + tb + '/');

    let rutaDirecta =
        enSubcarpeta
            ? 'inst_act.php'
            : `${tb}/inst_act.php`;


    // Enviar datos.
    fetch(rutaDirecta, {
        method: "POST",
        body: datos
    })
    .then(response => {

        if (!response.ok) {
            return fetch(`../${tb}/inst_act.php`, {
                method: "POST",
                body: datos
            });
        }

        return response;
    })
    .then(response => {

        if (!response.ok) {
            throw new Error(
                "Error HTTP " +
                response.status +
                " en la ruta: " +
                rutaDirecta
            );
        }

        return response.text();
    })
    .then(data => {

        console.log(
            "📩 Respuesta Servidor (" + tb + "):",
            data
        );


        // Detectar errores de MySQL o registros duplicados.
        let tieneErrorDuplicado =
            data.includes('Error MySQL') ||
            data.includes('ya se encuentra registrado');


        // Actualizar la tabla.
        if (
            cont &&
            (
                data.includes('<table') ||
                data.includes('<tr') ||
                data.includes('<div') ||
                data.includes('cell-')
            )
        ) {
            cont.innerHTML = data;
        }


        // Ejecutar scripts que regrese PHP.
        let tempDiv = document.createElement('div');
        tempDiv.innerHTML = data;

        let scripts =
            tempDiv.getElementsByTagName('script');

        for (let i = 0; i < scripts.length; i++) {

            try {
                eval(scripts[i].innerText);

            } catch (e) {
                console.error(
                    "Error al ejecutar script de respuesta:",
                    e
                );
            }
        }


        if (tieneErrorDuplicado) {
            return;
        }


        // Limpiar formulario.
        frm.reset();

        if (campoId) {
            campoId.value = "";
        }


        // Cerrar modales.
        if (typeof cerrarModalOperador === 'function') {
            cerrarModalOperador();
        }

        if (typeof cerrarModalActivo === 'function') {
            cerrarModalActivo();
        }


        let modalEl =
            document.getElementById('modalOperador') ||
            document.querySelector('.modal-overlay');

        if (modalEl) {
            modalEl.classList.remove(
                'active',
                'show',
                'open'
            );

            modalEl.style.display = 'none';
        }


        // Mostrar confirmación.
        if (typeof mostrarToast === 'function') {

            let mensaje =
                esEditar
                    ? "Registro actualizado correctamente"
                    : "Registro guardado correctamente";

            mostrarToast(mensaje);
        }
    })
    .catch(error => {

        console.error(
            "❌ Error AJAX al guardar en " + tb + ":",
            error
        );

        alert(
            "Ocurrió un error al guardar en " +
            tb +
            ". Revisa la consola (F12)."
        );
    });
}


// Envía un formulario mediante POST.
function enviardatos(url_tabla) {
    let cont3 = document.querySelector("#contenedor3");
    let frm = document.getElementById("frm");

    if (!frm) return;

    let datos = new FormData(frm);

    fetch(url_tabla, {
        body: datos,
        method: "post"
    })
    .then(response => response.text())
    .then(data => {

        if (cont3) {
            cont3.innerHTML = data;
        }

        frm.reset();

        let campoId =
            frm.querySelector('input[type="hidden"]');

        if (campoId) {
            campoId.value = "";
        }
    });
}


// Envía detalles de una compra.
function enviarDetallesCompra() {
    let cont =
        document.querySelector("#contenedor_detalle");

    let datos =
        new FormData(
            document.getElementById("frm_detalle")
        );

    fetch("detalles_compra/ins_act.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {

        if (cont) {
            cont.innerHTML = data;
        }

        document
            .getElementById("frm_detalle")
            .reset();
    })
    .catch(error =>
        console.error(
            "Error al insertar/actualizar detalle:",
            error
        )
    );
}


// Envía detalles de una venta.
function enviarDetallesVenta() {
    let cont =
        document.querySelector("#contenedor_detalle");

    let datos =
        new FormData(
            document.getElementById("frm_detalle")
        );

    fetch("detalles_venta/inst_act.php", {
        method: "POST",
        body: datos
    })
    .then(response => response.text())
    .then(data => {

        if (cont) {
            cont.innerHTML = data;
        }

        document
            .getElementById("frm_detalle")
            .reset();
    })
    .catch(error =>
        console.error(
            "Error al insertar/actualizar detalle:",
            error
        )
    );
}


// Carga los datos de un registro para editarlo.
function editar(id, tb, pfrm) {
    let datos = new FormData();

    let campo_id =
        tb === 'empresas' ? 'id_empresa' :
        tb === 'operadores' ? 'id_operador' :
        tb === 'usuarios' ? 'id_usuario' :
        tb === 'reporte_baja' ? 'id_reporte' :
        'id';


    let sql =
        "select * from " +
        tb +
        " where " +
        campo_id +
        " = " +
        id;

    datos.append("sql", sql);


    fetch("../" + tb + "/registro.php?id=" + id, {
        body: datos,
        method: "post"
    })
    .then(response => response.json())
    .then(registro => {

        let frm =
            document.getElementById(pfrm) ||
            document.getElementById('formGuardarUsuario') ||
            document.getElementById('frm');


        if (frm) {

            // Colocar los datos en los inputs.
            Object.keys(registro).forEach(key => {

                let campo =
                    frm.querySelector("#" + key) ||
                    frm.querySelector(`[name="${key}"]`);

                if (
                    campo &&
                    registro[key] !== undefined &&
                    campo.type !== 'file'
                ) {
                    campo.value = registro[key];
                }
            });


            let campoOculto =
                frm.querySelector(`#${campo_id}`) ||
                frm.querySelector(`[name="${campo_id}"]`) ||
                frm.querySelector('input[type="hidden"]');

            if (campoOculto) {
                campoOculto.value = id;
            }


            // Actualizar empresa cuando se edita un usuario.
            if (
                tb === 'usuarios' &&
                typeof controlarDespliegueEmpresa === 'function'
            ) {
                controlarDespliegueEmpresa();
            }


            // Actualizar motivo cuando se edita un reporte.
            if (
                tb === 'reporte_baja' &&
                typeof evaluarMotivoBaja === 'function'
            ) {

                let selectMotivo =
                    frm.querySelector('#motivo_baja');

                if (selectMotivo) {
                    evaluarMotivoBaja(
                        selectMotivo.value
                    );
                }
            }


            let singularTb =
                tb.endsWith('s')
                    ? tb.slice(0, -1)
                    : tb;


            let idModalEspecifico =
                tb === 'reporte_baja'
                    ? 'modalReporte'
                    : 'modal' +
                      singularTb.charAt(0).toUpperCase() +
                      singularTb.slice(1);


            const modalPadre =
                frm.closest('.modal-overlay') ||
                frm.closest('.modal') ||
                document.getElementById(idModalEspecifico);


            if (modalPadre) {

                const tituloModal =
                    modalPadre.querySelector(
                        '.modal-title, .modal-title-text, h2, h3'
                    );

                if (tituloModal) {

                    let nombreEntidad =
                        tb
                            .replace(/s$/, '')
                            .replace('_', ' ');

                    tituloModal.innerText =
                        `Editar ${
                            nombreEntidad.charAt(0).toUpperCase() +
                            nombreEntidad.slice(1)
                        }`;
                }

                modalPadre.classList.add('active');

            } else {

                frm.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
    })
    .catch(error =>
        console.error(
            "Error al cargar datos para edición:",
            error
        )
    );


    // Cargar detalles de compra.
    if (pfrm === "frm") {

        let contenedor =
            document.querySelector(
                "#contenedor_detalle"
            );

        if (contenedor) {

            fetch(
                "../detalles_compra/index.php?id=" +
                id
            )
            .then(response => response.text())
            .then(data => {
                contenedor.innerHTML = data;
            });
        }
    }
}


// Elimina un registro.
function eliminar(id, tb) { 

    let contenedor = '#contenedor3'; 
 
    if (tb === 'detalles_compra') { 
        contenedor = '#contenedor_detalle'; 
    } 
 
    const contenedorElemento = 
        document.querySelector(contenedor); 
 
    if (!contenedorElemento) { 

        console.error(
            "No se encontró el contenedor objetivo:",
            contenedor
        );

        return;
    }


    /* =====================================================
       FUNCIÓN QUE REALIZA LA ELIMINACIÓN
       ===================================================== */

    function ejecutarEliminacion() {

        fetch(
            "/" +
            tb +
            "/eliminar.php?id=" +
            encodeURIComponent(id) +
            "&tb=" +
            encodeURIComponent(tb)
        )
        .then(async response => {

            const data = await response.text();


            /* =================================================
               PHP BLOQUEÓ LA ELIMINACIÓN
               ================================================= */

            if (!response.ok) {

                if (typeof Swal !== 'undefined') {

                    Swal.fire({
                        icon: 'warning',
                        title: 'No se puede eliminar',
                        text: data,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#e67e00',
                        background: '#1e293b',
                        color: '#ffffff'
                    });

                } else {

                    alert(data);
                }

                return null;
            }


            return data;
        })
        .then(data => {

            /* Si PHP rechazó la eliminación */
            if (data === null) {
                return;
            }


            /* Actualizar tabla */
            contenedorElemento.innerHTML = data;


            /* Mensaje de éxito */
            mostrarToast(
                "Registro eliminado correctamente"
            );
        })
        .catch(error => {

            console.error(
                "Error al eliminar en " +
                tb +
                ":",
                error
            );

        });
    }


    /* =====================================================
       CONFIRMACIÓN ANTES DE ELIMINAR
       ===================================================== */

    if (typeof Swal !== 'undefined') {

        Swal.fire({
            icon: 'question',
            title: '¿Deseas eliminar este registro?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,

            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',

            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#475569',

            background: '#1e293b',
            color: '#ffffff',

            reverseButtons: true
        })
        .then(resultado => {

            if (resultado.isConfirmed) {

                ejecutarEliminacion();
            }

        });

    } else {

        /* Respaldo por si SweetAlert no carga */
        const confirmar = confirm(
            "¿Deseas eliminar este registro?"
        );

        if (confirmar) {

            ejecutarEliminacion();
        }
    }
}

/* =========================================================
   8. NOTIFICACIONES
   ========================================================= */

// Muestra una notificación flotante.
function mostrarToast(mensaje) {
    let toastExistente =
        document.getElementById('toast-eliminar');

    if (toastExistente) {
        toastExistente.remove();
    }


    let toast =
        document.createElement('div');

    toast.id = 'toast-eliminar';


    toast.innerHTML = `
        <div style="
            background-color: #10b981;
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            flex-shrink: 0;
        ">✓</div>

        <span>${mensaje}</span>
    `;


    toast.style.cssText = `
        position: fixed;
        top: 40px;
        left: 50%;
        transform: translateX(-50%) translateY(-20px) scale(0.95);
        background-color: rgba(17, 24, 39, 0.92);
        color: #ffffff;
        border: 1.5px solid #10b981;
        padding: 14px 28px;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6),
                    0 0 15px rgba(16, 185, 129, 0.3);
        display: flex;
        align-items: center;
        gap: 14px;
        z-index: 99999;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        pointer-events: none;
    `;


    document.body.appendChild(toast);


    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform =
            'translateX(-50%) translateY(0) scale(1)';
    });


    setTimeout(() => {

        toast.style.opacity = '0';

        toast.style.transform =
            'translateX(-50%) translateY(-20px) scale(0.95)';

        setTimeout(
            () => toast.remove(),
            300
        );

    }, 2800);
}



/* =========================================================
   9. OPERADORES
   ========================================================= */

// Valida los campos y guarda un operador.
function guardarModuloOperadores(event) {
    if (event) {
        event.preventDefault();
    }

    const form =
        document.getElementById('frm');

    const contenedorAlertas =
        document.getElementById(
            'contenedor-alertas-operadores'
        );

    if (!form) return;


    const inputs =
        form.querySelectorAll('.form-control');

    inputs.forEach(input => {
        input.style.borderColor = '#ccc';
    });


    if (contenedorAlertas) {
        contenedorAlertas.innerHTML = '';
    }


    let faltantes = [];


    const esEdicion =
        document.getElementById('id_operador') &&
        document
            .getElementById('id_operador')
            .value
            .trim() !== '';


    inputs.forEach(input => {

        // Si se está editando, el archivo no es obligatorio.
        if (
            esEdicion &&
            input.type === 'file'
        ) {
            return;
        }


        if (
            !input.checkValidity() ||
            input.value.trim() === ''
        ) {

            input.style.borderColor =
                '#dc3545';


            const label =
                input
                    .closest('.form-group')
                    ?.querySelector('.form-label')
                    ?.innerText;


            if (
                label &&
                !faltantes.includes(label)
            ) {
                faltantes.push(label);
            }
        }
    });


    if (faltantes.length > 0) {

        if (contenedorAlertas) {

            contenedorAlertas.innerHTML = `
                <div style="
                    background-color: #f8d7da;
                    color: #721c24;
                    padding: 12px;
                    border-radius: 5px;
                    border: 1px solid #f5c6cb;
                    margin-top: 10px;
                ">
                    <strong>
                        ⚠️ Campos requeridos faltantes:
                    </strong>
                    <br>

                    Por favor completa los siguientes apartados:

                    <b>${faltantes.join(', ')}</b>
                </div>
            `;
        }


        const primerInvalido =
            Array
                .from(inputs)
                .find(
                    input =>
                        input.style.borderColor ===
                        'rgb(220, 53, 69)'
                );


        if (primerInvalido) {
            primerInvalido.focus();
        }

        return false;
    }


    guardar(
        'operadores',
        'frm'
    );
}


// Filtra operadores de la tabla.
function filtrarOperadores(criterio, boton) {
    const botones =
        document.querySelectorAll(
            '.table-tabs .tab-btn'
        );

    botones.forEach(btn =>
        btn.classList.remove('active')
    );

    boton.classList.add('active');


    const filas =
        document.querySelectorAll(
            '#tablaOperadores tbody tr'
        );


    filas.forEach(fila => {

        const estatus =
            fila.getAttribute(
                'data-estatus'
            );

        const cruce =
            fila.getAttribute(
                'data-cruce'
            );


        if (criterio === 'todos') {

            fila.style.display = '';

        } else if (
            criterio === 'activos' ||
            criterio === 'inactivos'
        ) {

            fila.style.display =
                estatus === criterio
                    ? ''
                    : 'none';

        } else if (
            criterio === 'internacional'
        ) {

            fila.style.display =
                cruce === 'internacional'
                    ? ''
                    : 'none';
        }
    });
}



/* =========================================================
   10. PAGINACIÓN DE OPERADORES
   ========================================================= */

// Detecta clics en los botones de paginación.
document.addEventListener(
    'DOMContentLoaded',
    () => {

        const contenedorTabla =
            document.getElementById(
                'contenedor3'
            );

        if (contenedorTabla) {

            contenedorTabla.addEventListener(
                'click',
                e => {

                    const btnPaginacion =
                        e.target.closest(
                            '.pagination-btn:not(.disabled)'
                        );

                    if (
                        btnPaginacion &&
                        btnPaginacion.tagName === 'A'
                    ) {

                        e.preventDefault();

                        const urlParams =
                            btnPaginacion.getAttribute(
                                'href'
                            );

                        cargarTablaPaginada(
                            urlParams
                        );
                    }
                }
            );
        }
    }
);


// Carga una tabla paginada.
function cargarTablaPaginada(urlParams) {
    const contenedor =
        document.getElementById(
            'contenedor3'
        );

    if (!contenedor) return;

    contenedor.style.opacity = '0.5';

    fetch(
        `../operadores/tabla.php${urlParams}`
    )
    .then(response => {

        if (!response.ok) {
            throw new Error(
                'Error al cargar la tabla'
            );
        }

        return response.text();
    })
    .then(html => {

        contenedor.innerHTML = html;
        contenedor.style.opacity = '1';
    })
    .catch(error => {

        console.error(
            'Error AJAX al paginar:',
            error
        );

        contenedor.style.opacity = '1';
    });
}


// Filtro actual de operadores.
let estatusActivoOperadores = 'TODOS';


// Cambia la página de operadores.
function cambiarPagina(
    numPagina,
    estatus = estatusActivoOperadores
) {

    estatusActivoOperadores = estatus;


    const contenedor =
        document.getElementById(
            'contenedor3'
        );


    if (!contenedor) {

        console.error(
            "No se encontró el contenedor #contenedor3"
        );

        return;
    }


    contenedor.style.opacity = '0.5';


    const urlPeticion =
        `../operadores/tabla.php?pagina=${numPagina}&estatus=${encodeURIComponent(estatus)}`;


    fetch(urlPeticion)
        .then(response => {

            if (!response.ok) {

                return fetch(
                    `operadores/tabla.php?pagina=${numPagina}&estatus=${encodeURIComponent(estatus)}`
                );
            }

            return response;
        })
        .then(response => {

            if (!response.ok) {
                throw new Error(
                    `HTTP Error status: ${response.status}`
                );
            }

            return response.text();
        })
        .then(html => {

            contenedor.innerHTML = html;
            contenedor.style.opacity = '1';
        })
        .catch(error => {

            console.error(
                'Error AJAX al paginar:',
                error
            );

            alert(
                "Error al cargar la página: Comprueba la consola del navegador."
            );

            contenedor.style.opacity = '1';
        });
}



/* =========================================================
   11. REPORTE DE BAJA
   ========================================================= */

// Muestra u oculta los campos de reporte de baja.
function toggleReporteBaja(estatus) {
    const seccionBaja =
        document.getElementById(
            'seccion_reporte_baja'
        );

    const motivo =
        document.getElementById(
            'motivo_baja'
        );

    const califCuant =
        document.getElementById(
            'calificacion_cuantitativa'
        );


    if (!seccionBaja) return;


    if (estatus === "0") {

        seccionBaja.style.display =
            "block";

        if (motivo) {
            motivo.setAttribute(
                'required',
                'required'
            );
        }

        if (califCuant) {
            califCuant.setAttribute(
                'required',
                'required'
            );
        }

    } else {

        seccionBaja.style.display =
            "none";

        if (motivo) {
            motivo.removeAttribute(
                'required'
            );
        }

        if (califCuant) {
            califCuant.removeAttribute(
                'required'
            );
        }
    }
}


// Cierra cualquier modal abierto.
function cerrarModalActivo() {
    const modalesConClase =
        document.querySelectorAll(
            '.modal.active, .modal-overlay.active'
        );

    modalesConClase.forEach(modal =>
        modal.classList.remove('active')
    );


    const modalesConDisplay =
        document.querySelectorAll(
            '.modal, .modal-overlay, #modalDetallesOperador'
        );


    modalesConDisplay.forEach(modal => {

        if (
            modal.style.display === 'block' ||
            modal.style.display === 'flex'
        ) {
            modal.style.display = 'none';
        }
    });
}


// Guarda un reporte de baja.
function guardarReporteBaja(event) {
    if (event) {
        event.preventDefault();
    }


    const formulario =
        document.getElementById('frm');


    const contenedorTabla =
        document.querySelector(
            '#contenedor3'
        ) ||
        document.querySelector(
            '.table-container'
        );


    const contenedorAlertas =
        document.getElementById(
            'contenedor-alertas-reportes'
        );


    if (!formulario) {

        console.error(
            "No se encontró el formulario con ID 'frm'."
        );

        return;
    }


    const selectOperador =
        document.getElementById(
            'id_operador'
        );

    const selectEmpresa =
        document.getElementById(
            'id_empresa'
        );

    const selectMotivo =
        document.getElementById(
            'motivo_baja'
        );

    const inputCalific =
        document.getElementById(
            'calificacion_cuantitativa'
        );


    if (
        !selectOperador?.value ||
        !selectEmpresa?.value ||
        !selectMotivo?.value ||
        !inputCalific?.value
    ) {

        if (contenedorAlertas) {

            contenedorAlertas.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    ⚠️
                    <strong>Campos incompletos:</strong>
                    Por favor complete Operador, Empresa, Motivo y Calificación Cuantitativa.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close">
                    </button>
                </div>
            `;
        }

        return false;
    }


    const formData =
        new FormData(formulario);


    if (contenedorAlertas) {

        contenedorAlertas.innerHTML = `
            <div class="text-muted small">
                Guardando reporte de baja...
            </div>
        `;
    }


    fetch(
        '../reporte_baja/inst_act.php',
        {
            method: 'POST',
            body: formData
        }
    )
    .then(response => {

        if (!response.ok) {
            throw new Error(
                'Error en el servidor al guardar el reporte.'
            );
        }

        return response.text();
    })
    .then(htmlTablaActualizada => {

        if (contenedorTabla) {
            contenedorTabla.innerHTML =
                htmlTablaActualizada;
        }


        if (contenedorAlertas) {

            contenedorAlertas.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                    ✨
                    <strong>¡Registro guardado!</strong>
                    El reporte de baja se ha procesado correctamente.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close">
                    </button>
                </div>
            `;


            setTimeout(
                () => {
                    contenedorAlertas.innerHTML = '';
                },
                4000
            );
        }


        formulario.reset();


        const campoIdReporte =
            formulario.querySelector(
                '#id_reporte'
            ) ||
            formulario.querySelector(
                'input[name="id_reporte"]'
            ) ||
            formulario.querySelector(
                'input[type="hidden"]'
            );


        if (campoIdReporte) {
            campoIdReporte.value = '';
        }


        cerrarModalActivo();
    })
    .catch(error => {

        console.error(
            'Error al guardar el reporte de baja:',
            error
        );


        if (contenedorAlertas) {

            contenedorAlertas.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                    ⚠️
                    <strong>Error:</strong>
                    No se pudo guardar el reporte de baja.
                    Revisa la consola o la conexión.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close">
                    </button>
                </div>
            `;
        }
    });
}



/* =========================================================
   12. CONTROL DE NAVEGACIÓN
   ========================================================= */

// Evita regresar con el botón Atrás.
history.pushState(
    null,
    null,
    location.href
);


window.onpopstate = function() {
    window.location.href =
        'index.php';
};



/* =========================================================
   13. MODALES
   ========================================================= */

// Abre un modal dependiendo del módulo.
function abrirModal(tipo) {
    const config = {

        operador: {
            modal: 'modalOperador',
            titulo: 'Nuevo Operador',
            alerta: 'contenedor-alertas-operadores'
        },

        empresa: {
            modal: 'modalEmpresa',
            titulo: 'Nueva Empresa',
            alerta: 'contenedor-alertas-empresas'
        },

        usuario: {
            modal: 'modalUsuario',
            titulo: 'Nuevo Usuario',
            alerta: 'contenedor-alertas-usuarios',
            frmAlt: 'formGuardarUsuario'
        },

        reporte: {
            modal: 'modalReporte',
            titulo: 'Nuevo Reporte de Baja',
            alerta: 'contenedor-alertas-reportes'
        }
    };


    const cfg = config[tipo];

    if (!cfg) return;


    const frm =
        (
            cfg.frmAlt &&
            document.getElementById(
                cfg.frmAlt
            )
        ) ||
        document.getElementById('frm');


    if (frm) {

        frm.reset();


        const campoId =
            frm.querySelector(
                `#id_${tipo}`
            ) ||
            frm.querySelector(
                'input[type="hidden"]'
            );


        if (campoId) {
            campoId.value = '';
        }
    }


    // Configuración especial para usuarios.
    if (tipo === 'usuario') {

        if (
            typeof controlarDespliegueEmpresa ===
            'function'
        ) {

            controlarDespliegueEmpresa();

        } else {

            const seccionEmp =
                document.getElementById(
                    'seccion_empresa'
                );


            if (seccionEmp) {
                seccionEmp.style.display =
                    'none';
            }
        }
    }


    const modal =
        document.getElementById(
            cfg.modal
        );


    if (modal) {

        const tituloModal =
            modal.querySelector(
                '.modal-title, .modal-title-text, h2, h3'
            );


        if (tituloModal) {
            tituloModal.innerText =
                cfg.titulo;
        }


        modal.classList.add(
            'active'
        );
    }


    const contenedorAlertas =
        document.getElementById(
            cfg.alerta
        ) ||
        document.getElementById(
            'contenedor-alertas'
        );


    if (contenedorAlertas) {
        contenedorAlertas.innerHTML = '';
    }
}


// Cierra un modal.
function cerrarModal(tipo) {
    const modalId =
        tipo.startsWith('modal')
            ? tipo
            : `modal${
                tipo.charAt(0).toUpperCase() +
                tipo.slice(1)
            }`;


    const modal =
        document.getElementById(
            modalId
        );


    if (modal) {
        modal.classList.remove(
            'active'
        );
    }
}


// Funciones rápidas para abrir y cerrar cada modal.
const abrirModalOperador = () => abrirModal('operador');
const cerrarModalOperador = () => cerrarModal('operador');

const abrirModalEmpresa = () => abrirModal('empresa');
const cerrarModalEmpresa = () => cerrarModal('empresa');

const abrirModalUsuario = () => abrirModal('usuario');
const cerrarModalUsuario = () => cerrarModal('usuario');

const abrirModalReporte = () => abrirModal('reporte');
const cerrarModalReporte = () => cerrarModal('reporte');


// Cierra el modal del operador al hacer clic afuera.
window.addEventListener(
    'click',
    function(event) {

        const modal =
            document.getElementById(
                'modalOperador'
            );

        if (event.target === modal) {
            cerrarModalOperador();
        }
    }
);



/* =========================================================
   14. FILTROS
   ========================================================= */

// Filtra los usuarios dependiendo del rol.
function filtrarUsuarios(criterio, boton) {
    const botones =
        document.querySelectorAll(
            '.table-tabs .tab-btn'
        );


    botones.forEach(btn =>
        btn.classList.remove(
            'active'
        )
    );


    if (boton) {
        boton.classList.add(
            'active'
        );
    }


    rolActivoUsuarios =
        criterio.toUpperCase();


    cambiarPaginaUsuarios(
        1,
        rolActivoUsuarios
    );
}


// Segunda función para filtrar operadores.
// NOTA: Esta función tiene el mismo nombre que una anterior.
function filtrarOperadores(tipo, elemento) {
    const modal =
        document.getElementById(
            'modalDetallesOperador'
        );


    if (modal) {
        modal.style.display = 'none';
    }


    const formulario =
        document.getElementById(
            'frm'
        );


    if (formulario) {
        formulario.style.display =
            'none';
    }


    document
        .querySelectorAll('.tab-btn')
        .forEach(btn =>
            btn.classList.remove(
                'active'
            )
        );


    if (elemento) {
        elemento.classList.add(
            'active'
        );
    }


    const filas =
        document.querySelectorAll(
            '#tablaOperadores tbody tr'
        );


    filas.forEach(fila => {

        const estatus =
            fila.getAttribute(
                'data-estatus'
            );

        const cruce =
            fila.getAttribute(
                'data-cruce'
            );


        if (tipo === 'todos') {

            fila.style.display = '';

        } else if (
            tipo === 'activos' &&
            estatus === 'activos'
        ) {

            fila.style.display = '';

        } else if (
            tipo === 'inactivos' &&
            estatus === 'inactivos'
        ) {

            fila.style.display = '';

        } else if (
            tipo === 'internacional' &&
            cruce === 'internacional'
        ) {

            fila.style.display = '';

        } else {

            fila.style.display =
                'none';
        }
    });
}



/* =========================================================
   15. DETALLES DE OPERADORES
   ========================================================= */

// Muestra u oculta una fila de detalles.
function toggleDetalle(id) {
    const filaDetalle =
        document.getElementById(
            'detalle-' + id
        );


    if (!filaDetalle) return;


    if (
        filaDetalle.style.display ===
        'none' ||
        filaDetalle.style.display ===
        ''
    ) {
        filaDetalle.style.display =
            'table-row';

    } else {
        filaDetalle.style.display =
            'none';
    }
}


// Abre un modal de detalles.
function abrirModalDetalles(id) {
    const modal =
        document.getElementById(
            'modal-detalle-' + id
        );

    if (modal) {
        modal.style.display =
            'flex';
    }
}


// Cierra un modal de detalles.
function cerrarModalDetalles(id) {
    const modal =
        document.getElementById(
            'modal-detalle-' + id
        );

    if (modal) {
        modal.style.display =
            'none';
    }
}



/* =========================================================
   16. BÚSQUEDA DE OPERADORES
   ========================================================= */

// Filtra operadores por RFC o nombre mientras escribe.
function filtrarTablaEnVivo() {
    const termino =
        document
            .getElementById(
                'inputBuscadorOperador'
            )
            .value
            .toLowerCase()
            .trim();


    const filas =
        document.querySelectorAll(
            '#tablaOperadores tbody tr'
        );


    filas.forEach(fila => {

        const rfc =
            fila
                .querySelector(
                    '.cell-rfc'
                )
                ?.textContent
                .toLowerCase() ||
            '';


        const nombre =
            fila
                .querySelector(
                    '.cell-nombre'
                )
                ?.textContent
                .toLowerCase() ||
            '';


        if (
            rfc.includes(termino) ||
            nombre.includes(termino)
        ) {

            fila.style.display = '';

        } else {

            fila.style.display =
                'none';
        }
    });
}


// Buscar al presionar Enter.
document
    .getElementById(
        'inputBuscadorOperador'
    )
    ?.addEventListener(
        'keypress',
        function(e) {

            if (e.key === 'Enter') {

                e.preventDefault();

                const termino =
                    this.value.trim();


                if (
                    typeof cargarTablaOperadores ===
                    'function'
                ) {

                    cargarTablaOperadores(
                        1,
                        termino
                    );

                } else if (
                    typeof cargarTabla ===
                    'function'
                ) {

                    cargarTabla(
                        1,
                        termino
                    );
                }
            }
        }
    );


// Limpia el buscador.
function limpiarBuscadorBD() {

    if (
        typeof cargarTablaOperadores ===
        'function'
    ) {

        cargarTablaOperadores(
            1,
            ''
        );

    } else if (
        typeof cargarTabla ===
        'function'
    ) {

        cargarTabla(
            1,
            ''
        );
    }
}



/* =========================================================
   17. SEGUNDO GUARDADO DE REPORTE DE BAJA
   ========================================================= */

// Segunda función para guardar una baja.
// NOTA: Tiene el mismo nombre que una función anterior.
function guardarReporteBaja(e) {
    e.preventDefault();


    const form =
        document.getElementById(
            'frmBaja'
        );


    const formData =
        new FormData(form);


    fetch(
        'inst_act.php',
        {
            method: 'POST',
            body: formData
        }
    )
    .then(response =>
        response.text()
    )
    .then(respuesta => {

        if (
            respuesta.trim() === '1'
        ) {

            alert(
                'Operador dado de baja correctamente.'
            );


            form.reset();

            cerrarModalActivo();


            if (
                typeof cargarTabla ===
                'function'
            ) {
                cargarTabla();
            }

        } else {

            alert(
                'Ocurrió un error al registrar la baja.'
            );
        }
    })
    .catch(error =>
        console.error(
            'Error:',
            error
        )
    );
}



/* =========================================================
   18. ESTADO DE OPERADORES
   ========================================================= */

// Permite ver detalles solamente si el operador está activo.
function verDetallesOperador(id, estatus) {
    const estatusLimpio =
        String(estatus)
            .toUpperCase()
            .trim();


    if (
        estatusLimpio === '0' ||
        estatusLimpio === 'INACTIVO'
    ) {

        if (
            typeof mostrarToast ===
            'function'
        ) {

            mostrarToast(
                "⚠️ El registro está INACTIVO y no se pueden consultar sus detalles."
            );

        } else {

            alert(
                "El registro está INACTIVO y no se pueden consultar sus detalles."
            );
        }

        return false;
    }


    const modal =
        document.getElementById(
            'modalDetallesOperador'
        );


    if (modal) {
        modal.style.display =
            'block';
    }
}


// Comprueba si un operador está inactivo.
function estaInactivo(estatus) {
    const estatusLimpio =
        String(estatus)
            .toUpperCase()
            .trim();

    return (
        estatusLimpio === '0' ||
        estatusLimpio === 'INACTIVO'
    );
}


// Edita un operador solamente si está activo.
function editarOperador(id, estatus) {

    if (estaInactivo(estatus)) {

        if (
            typeof mostrarToast ===
            'function'
        ) {

            mostrarToast(
                "⚠️ El registro está INACTIVO y no se puede editar."
            );

        } else {

            alert(
                "⚠️ El registro está INACTIVO y no se puede editar."
            );
        }

        return false;
    }


    editar(
        id,
        'operadores',
        'frm'
    );
}


// Elimina un operador solamente si está activo.
function eliminarOperador(id, estatus) {

    if (estaInactivo(estatus)) {

        if (
            typeof mostrarToast ===
            'function'
        ) {

            mostrarToast(
                "⚠️ El registro está INACTIVO y no se puede eliminar."
            );

        } else {

            alert(
                "⚠️ El registro está INACTIVO y no se puede eliminar."
            );
        }

        return false;
    }


    eliminar(
        id,
        'operadores'
    );
}



/* =========================================================
   19. CALIFICACIONES
   ========================================================= */

// Selecciona una calificación del 1 al 10.
function seleccionarCalificacion(valor, elemento) {
    const inputHidden =
        document.getElementById(
            'calificacion_cuantitativa'
        );


    if (inputHidden) {
        inputHidden.value = valor;
    }


    const botones =
        elemento.parentElement
            .querySelectorAll(
                '.rating-btn'
            );


    botones.forEach(btn =>
        btn.classList.remove(
            'active'
        )
    );


    elemento.classList.add(
        'active'
    );
}


// Carga la calificación cuando se edita un reporte.
function cargarDatosEnFormulario(data) {

    if (
        data.calificacion_cuantitativa
    ) {

        const val =
            parseInt(
                data.calificacion_cuantitativa
            );


        const btnTarget =
            document.querySelector(
                `.rating-btn[data-value="${val}"]`
            );


        if (btnTarget) {

            seleccionarCalificacion(
                val,
                btnTarget
            );
        }
    }
}


// Muestra el campo cualitativo cuando el motivo es OTRO.
function evaluarMotivoBaja(valor) {
    const rowCualitativa =
        document.getElementById(
            'row_calif_cualitativa'
        );

    const txtCualitativa =
        document.getElementById(
            'calif_cualitativa'
        );


    if (
        !rowCualitativa ||
        !txtCualitativa
    ) {
        return;
    }


    if (valor === 'OTRO') {

        rowCualitativa.style.display =
            'flex';

        txtCualitativa.setAttribute(
            'required',
            'required'
        );

    } else {

        rowCualitativa.style.display =
            'none';

        txtCualitativa.removeAttribute(
            'required'
        );
    }
}



/* =========================================================
   20. PAGINACIÓN DE USUARIOS
   ========================================================= */

// Cambia la página de la tabla de usuarios.
function cambiarPaginaUsuarios(
    numPagina,
    rol = rolActivoUsuarios
) {

    const contenedor =
        document.getElementById(
            'contenedor3'
        );


    if (!contenedor) {

        console.error(
            "No se encontró el contenedor #contenedor3"
        );

        return;
    }


    contenedor.style.opacity =
        '0.5';


    const urlPeticion =
        `../usuarios/tabla.php?pagina=${numPagina}&rol=${encodeURIComponent(rol)}`;


    fetch(urlPeticion)
        .then(response => {

            if (!response.ok) {

                return fetch(
                    `usuarios/tabla.php?pagina=${numPagina}&rol=${encodeURIComponent(rol)}`
                );
            }

            return response;
        })
        .then(response => {

            if (!response.ok) {
                throw new Error(
                    `HTTP Error status: ${response.status}`
                );
            }

            return response.text();
        })
        .then(html => {

            contenedor.innerHTML =
                html;

            contenedor.style.opacity =
                '1';
        })
        .catch(error => {

            console.error(
                'Error AJAX al paginar/filtrar usuarios:',
                error
            );

            contenedor.style.opacity =
                '1';
        });
}



/* =========================================================
   21. CONTRASEÑAS
   ========================================================= */

// Muestra u oculta contraseñas de otros formularios.
function togglePassword(inputId, boton) {
    const input =
        document.getElementById(
            inputId
        );


    if (!input) return;


    const eyeShow =
        boton.querySelector(
            '.eye-show'
        );

    const eyeHide =
        boton.querySelector(
            '.eye-hide'
        );


    if (input.type === 'password') {

        input.type = 'text';


        if (
            eyeShow &&
            eyeHide
        ) {

            eyeShow.style.display =
                'none';

            eyeHide.style.display =
                'block';
        }

    } else {

        input.type = 'password';


        if (
            eyeShow &&
            eyeHide
        ) {

            eyeShow.style.display =
                'block';

            eyeHide.style.display =
                'none';
        }
    }
}


// Muestra u oculta la contraseña del LOGIN.
function mostrarPassword() {
    const password =
        document.getElementById(
            'clave'
        );

    const icono =
        document.getElementById(
            'iconoPassword'
        );


    if (
        !password ||
        !icono
    ) {
        return;
    }


    const mostrar =
        password.type === 'password';


    password.type =
        mostrar
            ? 'text'
            : 'password';


    icono.title =
        mostrar
            ? 'Ocultar contraseña'
            : 'Mostrar contraseña';


    // Cambiar icono del ojo.
    icono.innerHTML = mostrar
        ? `
            <svg viewBox="0 0 24 24">
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.7a2 2 0 002.7 2.7"/>
                <path d="M9.9 4.2A10.7 10.7 0 0112 4c5 0 9 4 10 8a11.8 11.8 0 01-2.1 4"/>
                <path d="M6.6 6.6C4.4 8 2.8 10 2 12c1.3 4 5 8 10 8a10.7 10.7 0 005.4-1.5"/>
            </svg>
        `
        : `
            <svg viewBox="0 0 24 24">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;
}



/* =========================================================
   22. LOGIN PRINCIPAL
   ========================================================= */

// Valida el usuario y lo redirige según su rol.
function enviarLogin(event) {
    event.preventDefault();


    const usuarioInput =
        document.getElementById(
            "usuario"
        );

    const claveInput =
        document.getElementById(
            "clave"
        );

    const errores =
        document.getElementById(
            "contenedorErroresLogin"
        );


    const btnSubmit =
        event.target.querySelector(
            'button[type="submit"]'
        ) ||
        event.target;


    const usuario =
        usuarioInput
            ? usuarioInput.value.trim()
            : "";

    const clave =
        claveInput
            ? claveInput.value
            : "";


    // Validar campos vacíos.
    if (
        !usuario ||
        !clave
    ) {

        errores.innerHTML = `
            <div class="alert alert-warning mt-3">
                ⚠️ Por favor, ingresa tu usuario y contraseña.
            </div>
        `;

        return;
    }


    // Desactivar botón.
    if (btnSubmit) {
        btnSubmit.disabled = true;
    }


    errores.innerHTML = `
        <div class="text-muted small mt-3">
            Verificando acceso...
        </div>
    `;


    // Enviar datos al servidor.
    fetch(
        "/autentificacion/validarusuario.php",
        {
            method: "POST",

            headers: {
                "Content-Type":
                    "application/x-www-form-urlencoded"
            },

            body:
                "usuario=" +
                encodeURIComponent(usuario) +
                "&clave=" +
                encodeURIComponent(clave)
        }
    )
    .then(response =>
        response.text()
    )
    .then(texto => {

        console.log(
            "Respuesta PHP:",
            texto
        );


        let data;


        // Convertir respuesta a JSON.
        try {

            data =
                JSON.parse(texto);

        } catch (error) {

            console.error(
                "PHP NO DEVOLVIÓ JSON:",
                texto
            );


            errores.innerHTML = `
                <div class="alert alert-danger mt-3">
                    ⚠️ El servidor devolvió una respuesta incorrecta.
                </div>
            `;


            if (btnSubmit) {
                btnSubmit.disabled = false;
            }

            return;
        }


        // Login correcto.
        if (
            data.status ===
            "success"
        ) {

            console.log(
                "Login correcto. Rol:",
                data.rol
            );


            const rol =
                (data.rol || "")
                    .trim()
                    .toUpperCase();


            const rolesDashboard = [
                "ADMIN",
                "ADMINISTRADOR",
                "PROPIETARIO",
                "RRHH",
                "RH",
                "RECURSOS HUMANOS"
            ];


            // Dashboard principal.
            if (
                rolesDashboard.includes(
                    rol
                )
            ) {

                window.location.href =
                    "/index.php";


            // Operadores.
            } else if (
                rol === "OPERADOR"
            ) {

                window.location.href =
                    "/operadores/index.php";


            // Empresas.
            } else if (
                rol === "EMPRESA"
            ) {

                window.location.href =
                    "/empresas/index.php";


            // Rol desconocido.
            } else {

                errores.innerHTML = `
                    <div class="alert alert-danger mt-3">
                        ⚠️ Rol no reconocido: ${data.rol}
                    </div>
                `;


                if (btnSubmit) {
                    btnSubmit.disabled = false;
                }
            }


        // Credenciales incorrectas.
        } else {

            errores.innerHTML = `
                <div class="alert alert-danger mt-3">
                    ⚠️ ${data.message || "Credenciales incorrectas"}
                </div>
            `;


            if (btnSubmit) {
                btnSubmit.disabled = false;
            }
        }
    })
    .catch(error => {

        console.error(
            "Error del login:",
            error
        );


        errores.innerHTML = `
            <div class="alert alert-danger mt-3">
                ⚠️ Error de conexión con el servidor.
            </div>
        `;


        if (btnSubmit) {
            btnSubmit.disabled = false;
        }
    });
}



/* =========================================================
   23. SIDEBAR
   ========================================================= */

// Carga la barra lateral del sistema.
function cargarSidebar() {
    fetch(
        '../includes/sidebar.php'
    )
    .then(response =>
        response.text()
    )
    .then(html => {

        const contenedor =
            document.getElementById(
                'contenedorSidebar'
            );


        if (contenedor) {
            contenedor.innerHTML =
                html;
        }
    })
    .catch(error =>
        console.error(
            "Error al cargar la barra lateral (sidebar):",
            error
        )
    );
}