
/* =========================================================
   COSAS NETTO2 - PASO 2D-G.2
   VENTANA DE ENTREGA UNICA DE CREDENCIALES RRHH

   IMPORTANTE:
   - Muestra credenciales solo tras confirmacion del servidor.
   - NO modifica usuarios, empresas ni contraseñas por si misma.
   - NO registra contraseñas en consola ni storage.
   - La vista previa utiliza datos ficticios.
   - Cierra el formulario y recarga la tabla al confirmar.
   ========================================================= */

function mostrarCredencialesTemporalesRRHH(datos, opciones = {}) {
    const vistaPrevia = opciones.vistaPrevia === true;

    // Una respuesta real solo puede mostrar credenciales si
    // el backend confirmo expresamente la creacion de la cuenta.
    if (
        !vistaPrevia &&
        (!datos || datos.ok !== true || datos.estado !== 'RRHH_CREADO')
    ) {
        return false;
    }

    if (!datos || typeof datos !== 'object') {
        return false;
    }

    const usuario = datos.usuario;
    const empresa = datos.empresa;
    const contrasena = datos.contrasena_temporal;

    if (
        typeof usuario !== 'string' || usuario.trim() === '' ||
        typeof empresa !== 'string' || empresa.trim() === '' ||
        typeof contrasena !== 'string' || contrasena === ''
    ) {
        return false;
    }

    // SweetAlert2 ya se utiliza en Cosas Netto2.
    if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
        return false;
    }

    // Escapamos cualquier dato externo antes de agregarlo al HTML.
    const escaparHTML = (valor) =>
        String(valor).replace(/[&<>"']/g, (caracter) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[caracter]);

    const usuarioSeguro = escaparHTML(usuario);
    const empresaSegura = escaparHTML(empresa);
    const contrasenaSegura = escaparHTML(contrasena);

    const avisoPrueba = vistaPrevia
        ? `<p style="color:#fde68a; font-weight:700; text-align:center;">
               VISTA PREVIA: NO SE CREO NINGUNA CUENTA.
           </p>`
        : `<p style="color:#bbf7d0; text-align:center;">
               La cuenta RRHH fue registrada correctamente.
           </p>`;

    const html = `
        <div style="text-align:left; line-height:1.6;">
            ${avisoPrueba}

            <div style="background:#0f172a; padding:13px 16px;
                        margin-bottom:12px; border-radius:9px;">
                <div style="font-size:12px; color:#94a3b8;">
                    USUARIO
                </div>
                <div style="font-weight:700; word-break:break-all;">
                    ${usuarioSeguro}
                </div>
            </div>

            <div style="background:#0f172a; padding:13px 16px;
                        margin-bottom:12px; border-radius:9px;">
                <div style="font-size:12px; color:#94a3b8;">
                    EMPRESA
                </div>
                <div style="font-weight:700; word-break:break-word;">
                    ${empresaSegura}
                </div>
            </div>

            <div style="background:#103b44; padding:13px 16px;
                        margin-bottom:16px; border-radius:9px;">
                <div style="font-size:12px; color:#99f6e4;">
                    CONTRASENA TEMPORAL
                </div>
                <div style="font-family:monospace; font-size:20px;
                            font-weight:700; word-break:break-all;">
                    ${contrasenaSegura}
                </div>
            </div>

            <p style="font-size:13px; color:#cbd5e1; text-align:center;">
                Guarda estas credenciales antes de cerrar la ventana.
                La contrasena temporal no podra consultarse otra vez
                desde el sistema. El RRHH debera cambiarla al iniciar sesion.
            </p>
        </div>
    `;

    // La ventana debe permanecer visible hasta que el propietario
    // confirme que guardo las credenciales. Solo despues se cierra
    // Nuevo RRHH y se actualiza el listado, sin recargar el sitio.
    Swal.fire({
        icon: vistaPrevia ? 'info' : 'success',
        title: vistaPrevia
            ? 'Vista previa de credenciales RRHH'
            : 'RRHH creado correctamente',
        html,
        confirmButtonText: 'Ya guarde las credenciales',
        confirmButtonColor: '#0d9488',
        background: '#1e293b',
        color: '#f8fafc',
        width: 580,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCloseButton: false
    }).then((respuesta) => {
        // Una vista previa no debe cerrar formularios ni recargar tablas.
        if (!vistaPrevia && respuesta.isConfirmed) {
            finalizarAltaRRHHInterfaz();
        }
    });

    return true;
}

/* =========================================================
   COSAS NETTO2 - PASO 2D-H.3B
   FINALIZAR INTERFAZ DESPUES DE GUARDAR CREDENCIALES

   - Se ejecuta solo tras una confirmacion de alta real.
   - Cierra el formulario Nuevo RRHH.
   - Limpia sus datos y avisos.
   - Reutiliza la paginacion existente para actualizar RRHH.
   - NO envia solicitudes de creacion, ni altera MySQL.
   ========================================================= */

function finalizarAltaRRHHInterfaz() {
    // Son funciones que ya ofrece JS/funciones.js.
    if (typeof cerrarModalUsuario !== 'function') {
        return false;
    }

    // La paginacion existente actualiza #contenedor3 con tabla.php.
    if (
        typeof cambiarPaginaUsuarios !== 'function' ||
        !document.getElementById('contenedor3')
    ) {
        return false;
    }

    // Cerramos exclusivamente el modal Nuevo RRHH.
    cerrarModalUsuario();

    const formulario = document.getElementById('formGuardarUsuario');
    if (formulario && typeof formulario.reset === 'function') {
        formulario.reset();
    }

    const avisos = document.getElementById('contenedor-alertas-usuarios');
    if (avisos) {
        avisos.replaceChildren();
    }

    // Volver a la primera pagina: alli aparece la cuenta mas reciente.
    // El filtro RRHH tambien mantiene el comportamiento de PROPIETARIO.
    cambiarPaginaUsuarios(1, 'RRHH');

    return true;
}

/* =========================================================
   COSAS NETTO2 - PASO 2D-D.5B
   PREPARAR ENVIO DE FORMULARIO RRHH

   - Usa usuarios/crear_rrhh.php, no usuarios/guardar.php.
   - Envia el token CSRF incluido en el formulario.
   - Interpreta respuestas HTTP aunque sean 403/409/422.
   - Nunca imprime ni almacena credenciales en consola/storage.
   - Solo muestra credenciales tras HTTP 201 + RRHH_CREADO.
   - Evita solicitudes simultaneas desde este controlador.
   - El boton Crear RRHH envia datos al endpoint autorizado.
   - El backend controla la activacion y permisos de altas.
   ========================================================= */

let solicitudAltaRRHHEnCurso = false;

// Texto seguro: no se usa innerHTML para mensajes del servidor.
function mostrarAvisoFormularioRRHH(mensaje, tipo = 'warning') {
    const contenedor = document.getElementById('contenedor-alertas-usuarios');
    if (!contenedor) return;

    const aviso = document.createElement('div');
    aviso.className = 'alert alert-' + (tipo === 'danger' ? 'danger' : 'warning');
    aviso.setAttribute('role', 'alert');
    aviso.textContent = mensaje;

    contenedor.replaceChildren(aviso);
}

// Funcion de envio reutilizable; admite FormData de prueba o del formulario.
async function enviarSolicitudAltaRRHH(datos) {
    const respuestaResumen = (http, estado, credencialesMostradas = false) => ({
        http,
        estado,
        credenciales_mostradas: credencialesMostradas
    });

    if (!(datos instanceof FormData)) {
        return respuestaResumen(null, 'FORMULARIO_INVALIDO');
    }

    // Antes de enviar una futura alta real, debe ser posible mostrar
    // inmediatamente las credenciales despues del COMMIT.
    if (
        typeof Swal === 'undefined' ||
        typeof Swal.fire !== 'function' ||
        typeof mostrarCredencialesTemporalesRRHH !== 'function'
    ) {
        mostrarAvisoFormularioRRHH(
            'No esta disponible la ventana de credenciales. Vuelve a cargar el modulo.',
            'danger'
        );
        return respuestaResumen(null, 'VISOR_NO_DISPONIBLE');
    }

    const token = datos.get('csrf_token_rrhh');

    if (typeof token !== 'string' || !/^[a-f0-9]{64}$/.test(token)) {
        mostrarAvisoFormularioRRHH(
            'No se encontro el token de seguridad. Vuelve a cargar el formulario.',
            'danger'
        );
        return respuestaResumen(null, 'TOKEN_NO_DISPONIBLE');
    }

    if (solicitudAltaRRHHEnCurso) {
        return respuestaResumen(null, 'SOLICITUD_EN_CURSO');
    }

    solicitudAltaRRHHEnCurso = true;

    try {
        const respuesta = await fetch('/usuarios/crear_rrhh.php', {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        });

        // Si la sesion termino o se requiere cambiar la contrasena,
        // el manejador central del proyecto realiza la navegacion.
        if (
            typeof manejarRedireccionSesion === 'function' &&
            manejarRedireccionSesion(respuesta)
        ) {
            return respuestaResumen(respuesta.status, 'SESION_REDIRECCIONADA');
        }

        const tipoContenido = respuesta.headers.get('content-type') || '';

        if (!tipoContenido.toLowerCase().includes('application/json')) {
            mostrarAvisoFormularioRRHH(
                'El servidor devolvio una respuesta inesperada. No repitas el envio; verifica primero si la cuenta fue registrada.',
                'danger'
            );
            return respuestaResumen(respuesta.status, 'RESPUESTA_INESPERADA');
        }

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch (_) {
            mostrarAvisoFormularioRRHH(
                'No fue posible leer la confirmacion del servidor. No repitas el envio sin verificar la cuenta.',
                'danger'
            );
            return respuestaResumen(respuesta.status, 'RESPUESTA_INCOMPLETA');
        }

        // Condiciones obligatorias y simultaneas de exito.
        if (
            respuesta.status === 201 &&
            resultado &&
            resultado.ok === true &&
            resultado.estado === 'RRHH_CREADO'
        ) {
            const seMostraron = mostrarCredencialesTemporalesRRHH(resultado);

            if (!seMostraron) {
                mostrarAvisoFormularioRRHH(
                    'La cuenta fue creada, pero no pudo mostrarse su contrasena. No intentes crearla otra vez; sera necesario restablecer el acceso.',
                    'danger'
                );
                return respuestaResumen(201, 'CREDENCIALES_NO_MOSTRADAS');
            }

            // No se devuelve la contrasena ni el objeto completo.
            return respuestaResumen(201, 'RRHH_CREADO', true);
        }

        // Un HTTP exitoso distinto del contrato exacto es ambiguo:
        // no mostrar una confirmacion falsa ni sugerir reintentar.
        if (respuesta.ok) {
            mostrarAvisoFormularioRRHH(
                'La respuesta del servidor no coincide con la confirmacion esperada. Verifica si la cuenta existe antes de cualquier nuevo intento.',
                'danger'
            );
            return respuestaResumen(respuesta.status, 'EXITO_NO_CONFIRMADO');
        }

        const estado = typeof resultado?.estado === 'string'
            ? resultado.estado
            : 'RESPUESTA_NO_RECONOCIDA';

        const mensaje = typeof resultado?.mensaje === 'string'
            ? resultado.mensaje
            : 'No fue posible completar la solicitud RRHH.';

        mostrarAvisoFormularioRRHH(
            mensaje,
            estado === 'CREACION_PENDIENTE' ? 'warning' : 'danger'
        );

        return respuestaResumen(respuesta.status, estado);

    } catch (_) {
        // Un fallo de red no demuestra que el servidor no haya guardado.
        mostrarAvisoFormularioRRHH(
            'No se pudo confirmar el resultado. No repitas el envio sin verificar primero si la cuenta existe.',
            'danger'
        );
        return respuestaResumen(null, 'RESULTADO_DESCONOCIDO');

    } finally {
        solicitudAltaRRHHEnCurso = false;
    }
}

// Funcion conectada con el boton Crear RRHH del formulario.
async function enviarNuevoRRHH() {
    const formulario = document.getElementById('formGuardarUsuario');

    if (!formulario) {
        return {
            http: null,
            estado: 'FORMULARIO_NO_ENCONTRADO',
            credenciales_mostradas: false
        };
    }

    if (!formulario.reportValidity()) {
        return {
            http: null,
            estado: 'FORMULARIO_INCOMPLETO',
            credenciales_mostradas: false
        };
    }

    return enviarSolicitudAltaRRHH(new FormData(formulario));
}
