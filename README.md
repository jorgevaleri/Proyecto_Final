📘 Registro de Asistencia de Alumnos

✅ Descripción del Proyecto
    Este sistema es una aplicación web desarrollada en PHP + MySQL, diseñada para gestionar:
        Personas (Datos Personales, Domicilios, Teléfonos)
        Usuarios y roles (Administrador, Director, Docente)
        Inscripciones a Formaciones Profesionales por Escuela y Año
        Estados de alumnos (Cursando, Promocionó, Abandonó)
        Cálculo y Registro mensual de asistencias e inasistencias
        Reportes, Resúmenes y Movimientos institucionales
    
    Permite a los actores institucionales (admin, directores y docentes) operar con sus permisos correspondientes y mantener un registro histórico de todos los movimientos.

✅ Requisitos del Sistema
    🔧 Software Obligatorio
        PHP >= 7.4
        MySQL / MariaDB
        Servidor Apache (recomendado XAMPP)
        Extensiones PHP:
            mysqli
            pdo_mysql (si se usa)
            openssl (para password hashing)
            json
            mbstring

📂 Carpetas necesarias
    El proyecto debe contener:
        /BackEnd
        /FrontEnd
            /CSS
            /JS

✅ Instalación
    1️⃣ Clonar o copiar el proyecto
        Descargar el repositorio y colocarlo dentro de:
        C:\xampp\htdocs\Seminario\

    2️⃣ Configurar la Base de Datos
        Abrir phpMyAdmin
        Crear una base de datos
        Importar el archivo SQL incluido en el proyecto
            
    3️⃣ Configurar la conexión
        BackEnd/conexion.php
        Configurar:
            $servername = "localhost";
            $username   = "root";
            $password   = "";
            $database   = "seminario";

✅ Estructura del Proyecto
    📂 BackEnd/
        autenticacion.php
        conexion.php    
    
    📂 FrontEnd/
        Contiene las carpetas
            CSS
                estilo.app.css
                estilo_comun.css
                estilo_publico.css
                index.css
                registrarse.css
            Imagenes
                Logo_2.png
                Logo_3.png
                Logo_4.png
            Includes
                inicializar.php
            JS
                calculadora_asistencia.js
                escuelas.js
                formacion_profesional.js
                logeo.js
                perfil.js
                personas.js
                registrarse.js
                registros.js
                usurios.js
                validaciones_escuelas.js
                validaciones_formacion_profesiona.js
                validaciones_globales.js
                validaciones_personas.js
                validaciones_registrarse.js
                validacioens_registros.js            
            deslogeo.php
            escuelas.php
            footer.php
            formacion_profesional.php
            head.php
            header.php
            index.php
            logeo.php
            menu_lateral.php
            menu_principal.php
            olvide_contrasenia.php
            perfil.php
            personas.php
            registrarse.php
            registros.php
            usuarios.php
    
    📂 README.md

✅ Primer Inicio del Sistema
    1️⃣ Crear usuario administrador manualmente
        Ingresar en phpMyAdmin:
            INSERT INTO usuarios (usuarios_email, usuarios_clave, usuarios_rol, personas_id)
            VALUES ('admin@admin.com', 'admin123', 'ADMINISTRADOR', 1);
        
        ⚠️ Importante: la contraseña se re-hasheará automáticamente al primer login.

    2️⃣ Ingresar al sistema
        Entrar desde:
            http://localhost/Seminario/FrontEnd/index.php
    
    3️⃣ Cargar Base de Datos
        Crear base de datos nueva con el nombre que quieras (ej. mi_proyecto).
        Ir a Importar → subir db/mi_proyecto.sql → Ejecutar.

    ✅ Roles del Sistema
        👑 ADMINISTRADOR
            ✅ Crear/editar usuarios
            ✅ Cambiar roles
            ✅ Ver todas las escuelas y formaciones
            ✅ Acceso completo a registros e informes

        🏫 DIRECTOR
            ✅ Ver listados de alumnos de su escuela
            ✅ Ver resúmenes y reportes
            🔒 No puede editar roles

        👨‍🏫 DOCENTE
            ✅ Cargar alumnos
            ✅ Editar fichas
            ✅ Usar la Calculadora mensual
            ✅ Ver sus propias formaciones
            🔒 No puede ver datos de otras escuelas

✅ Módulos Principales
    1️⃣ Registro de Personas
        Incluye:
            Datos personales
            Múltiples domicilios con mapa
            Múltiples teléfonos con opción “predeterminado”
            Vinculación a usuario

    2️⃣ Institucional
        Asocia personas a:
            Escuela
            Formación profesional
            Rol dentro de la institución

    3️⃣ Ingresar Alumnos
        Inscripción por escuela, formación y año
        Control de duplicados
        Cambios de estado (cursando/promocionó/abandonó)

    4️⃣ Calculadora (Asistencias)
        Permite:
            Cargar asistencias e inasistencias por alumno
            Calcular totales, promedios y porcentajes
            Guardar todo en base de datos
            Ver reportes mensuales

    5️⃣ Resumen Mensual
        Genera automáticamente:
            Entradas
            Salidas
            Quedan
            Movimientos de alumnos por mes

✅ Mensajería y Validaciones
    El sistema utiliza:
        ✅ SweetAlert2 para éxitos y errores
        ✅ Validaciones en JavaScript
        ✅ Validaciones en servidor (PHP)
        ✅ Respuestas JSON limpias con ob_end_clean()
    Esto previene errores y mejora la experiencia del usuario.

✅ Documentacion del sistema
    Ingresar al siguiente link
        https://www.notion.so/Registro-de-Asistencia-Digital-para-Alumnos-de-Formaci-n-Profesional-de-Adultos-09f9709096224c51b5c51b1fabacaace?source=copy_link

✅ Contacto
    En caso de dudas o mejoras, contactar al desarrollador del proyecto.
    Jorge Norberto Valeri Sopaga - 3834800300