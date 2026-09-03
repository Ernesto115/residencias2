<!DOCTYPE html> 
<html lang="es"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Inicio de Sesión | Transportes 1° de Mayo</title> 
    <link href="/css/styles.css" rel="stylesheet"> 
    <script src="/JS/funciones.js"></script> 
</head> 

<body> 

<div class="login-page"> 

    <!-- ========================================== 
         LADO IZQUIERDO 
         ========================================== --> 
    <section class="login-left"> 

        <h2>Consejo Binacional<br>de Transportistas</h2> 

        <p class="login-left-description"> 
            Sistema Integral para la gestión de operadores, empresas transportistas y documentación laboral. 
        </p> 

        <div class="login-features"> 
            <h3>¿Qué puedes hacer?</h3> 

            <div class="login-feature"> 
                <span class="login-check">✓</span> 
                <span>Consultar operadores.</span> 
            </div> 

            <div class="login-feature"> 
                <span class="login-check">✓</span> 
                <span>Validar documentos.</span> 
            </div> 

            <div class="login-feature"> 
                <span class="login-check">✓</span> 
                <span>Consultar historial laboral.</span> 
            </div> 

            <div class="login-feature"> 
                <span class="login-check">✓</span> 
                <span>Registrar evaluaciones.</span> 
            </div> 
        </div> 

    </section> 


    <!-- ========================================== 
         LADO DERECHO 
         ========================================== --> 
    <section class="login-right"> 

        <div class="login-card-custom"> 

            <div class="login-system-badge">
                Sistema Empresarial
            </div> 

            <div class="login-main-icon">🚚</div> 

            <h1>Bienvenido</h1> 

            <p class="login-card-subtitle">
                Ingrese sus credenciales para acceder al sistema.
            </p> 


            <!-- ==========================================
                 FORMULARIO DE INICIO DE SESIÓN
                 ========================================== -->
            <form id="formLogin" method="POST" onsubmit="enviarLogin(event); return false;"> 
                
                <!-- Usuario -->
                <div class="login-form-group"> 
                    <label for="usuario">Usuario</label> 

                    <div class="login-input-wrapper"> 
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="login-input" 
                            placeholder="Ingrese su usuario" 
                            required
                        > 
                    </div> 
                </div> 


                <!-- Contraseña -->
                <div class="login-form-group"> 
                    <label for="clave">Contraseña</label> 

                    <div class="login-input-wrapper"> 
                        <input 
                            type="password" 
                            id="clave" 
                            name="clave" 
                            class="login-input login-password-input" 
                            placeholder="Ingrese su contraseña" 
                            required
                        > 

                        <!-- Botón para mostrar u ocultar contraseña -->
                        <span 
                            class="login-eye" 
                            onclick="mostrarPassword()" 
                            id="iconoPassword"
                            title="Mostrar contraseña"
                        >
                            <svg viewBox="0 0 24 24">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>

                    </div> 
                </div> 


                <!-- Recordar usuario -->
                <label class="login-remember"> 
                    <input type="checkbox" id="recordarme" name="recordarme"> 
                    <span>Recordarme</span> 
                </label> 


                <!-- Botón iniciar sesión -->
                <button type="submit" class="login-button-custom">
                    Iniciar sesión
                </button> 

            </form> 


     <!-- ==========================================
     MENSAJE DE CUENTA DESACTIVADA
     ========================================== -->

<?php

$motivo = $_GET['motivo'] ?? '';

if ($motivo === 'cuenta_desactivada'):
?>

    <div style="
        margin-top:18px;
        padding:12px 14px;
        border-radius:8px;
        background:rgba(245,158,11,.12);
        border:1px solid rgba(245,158,11,.45);
        color:#f8fafc;
        font-size:.9rem;
        line-height:1.45;
        text-align:left;
    ">

        ⚠️
        <strong>Sesión finalizada.</strong>

        <br>

        Tu cuenta fue desactivada por un administrador
        y ya no tiene acceso al sistema.

    </div>

<?php endif; ?>


<!-- ==========================================
     MENSAJES NORMALES DEL LOGIN
     ========================================== -->

<div id="contenedorErroresLogin"></div>


            <!-- Recuperar contraseña -->
            <a href="#" class="login-forgot">
                ¿Olvidaste tu contraseña?
            </a> 


            <!-- ==========================================
                 PIE DEL LOGIN
                 ========================================== -->
            <div class="login-footer"> 
                © 2026 Consejo Binacional de Transportistas
                <br>
                Todos los derechos reservados a Transportes 1° de Mayo S.A. de C.V. 

                <div class="login-version">
                    Versión 1.0.2
                </div> 
            </div> 

        </div> 

    </section> 

</div> 

</body> 
</html>