window.addEventListener('load', () => {

    // Obtener el formulario principal
    const form = document.getElementById('form');

    // Función de validación de campos
    const validarCampo = (input, regex, errorId, mensajeError) => {
        const valor = input.value.trim();
        const errorMensaje = document.getElementById(errorId);

        if (!regex.test(valor)) {
            input.style.borderColor = 'red';
            errorMensaje.textContent = mensajeError;
        } else {
            input.style.borderColor = 'green';
            errorMensaje.textContent = '';
        }
    };

    // Validación de correo electrónico
    const correoInput = document.getElementById('usuarios_email');
    correoInput.addEventListener('input', () => {
        const correoRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
        validarCampo(correoInput, correoRegex, 'error-correo', 'Correo electrónico inválido');
    });

    // Validación de contraseñas
    const passwordInput = document.getElementById('usuarios_clave');
    const passwordRepetirInput = form.querySelector('input[name="password_repetir"]');

    passwordInput.addEventListener('input', () => {
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
        validarCampo(passwordInput, passwordRegex, 'error-password', 'La contraseña debe tener al menos 8 caracteres y un número');
    });

    passwordRepetirInput.addEventListener('input', () => {
        if (passwordInput.value !== passwordRepetirInput.value) {
            passwordRepetirInput.style.borderColor = 'red';
            document.getElementById('error-password-repetir').textContent = 'Las contraseñas no coinciden';
        } else {
            passwordRepetirInput.style.borderColor = 'green';
            document.getElementById('error-password-repetir').textContent = '';
        }
    });

    // Validación de CUIL
    const cuilInput = document.getElementById('personas_cuil');
    cuilInput.addEventListener('input', () => {
        const cuilRegex = /^\d{11}$/;
        validarCampo(cuilInput, cuilRegex, 'error-cuil', 'CUIL inválido, debe contener 11 dígitos sin guiones');
    });

    // Validación de apellidos
    const apellidoInput = document.getElementById('personas_apellido');
    apellidoInput.addEventListener('input', () => {
        const apellidoRegex = /^[a-zA-Z\s]+$/;
        validarCampo(apellidoInput, apellidoRegex, 'error-apellido', 'Ingrese apellidos válidos');
    });

    // Validación de nombres
    const nombreInput = document.getElementById('personas_nombre');
    nombreInput.addEventListener('input', () => {
        const nombreRegex = /^[a-zA-Z\s]+$/;
        validarCampo(nombreInput, nombreRegex, 'error-nombre', 'Ingrese nombres válidos');
    });

    // Calcular edad a partir de la fecha de nacimiento
    const fechaNacimientoInput = document.getElementById('personas_fechnac');
    const edadLabel = document.getElementById('edad');

    fechaNacimientoInput.addEventListener('input', () => {
        const fechaNacimiento = new Date(fechaNacimientoInput.value);
        const hoy = new Date();
        let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
        const mes = hoy.getMonth() - fechaNacimiento.getMonth();

        if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
            edad--;
        }
        edadLabel.textContent = `${edad} años`;
    });

    // Validación de dirección
    const direccionInput = document.getElementById('domicilios_calle');
    const errorDireccion = document.getElementById('error-direccion');

    const validateDireccion = () => {
        const valor = direccionInput.value.trim();
        if (valor === '') {
            direccionInput.style.borderColor = 'red';
            errorDireccion.textContent = 'La dirección es obligatoria';
            return false;
        } else {
            direccionInput.style.borderColor = 'green';
            errorDireccion.textContent = '';
            buscarUbicacion(valor); // Llama a la función de búsqueda solo si es válida
            return true;
        }
    };

    direccionInput.addEventListener('input', validateDireccion);

    // MAPA//
    // Configuración de Mapbox
    //TOKEN DEL MAPA
    mapboxgl.accessToken = 'pk.eyJ1IjoiZWxqb3R0YSIsImEiOiJjbTFqaTIwc3kwZXcyMmtuMzh6MGR2dGIyIn0.wjqvCUChDjybCndtVaveyw';

    //CREAR UN MAPA
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [-58.3816, -34.6037], // Coordenadas iniciales (Buenos Aires)
        zoom: 13
    });

    // Agregar controles de navegación
    map.addControl(new mapboxgl.NavigationControl());

    let marker;

    // Función para buscar la ubicación
    const buscarUbicacion = (direccion) => {
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(direccion)}.json?access_token=${mapboxgl.accessToken}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.features.length > 0) {
                    const coordenadas = data.features[0].center;
                    map.setCenter(coordenadas);
                    if (marker) {
                        marker.setLngLat(coordenadas);
                    } else {
                        marker = new mapboxgl.Marker().setLngLat(coordenadas).addTo(map);
                    }
                } else {
                    console.error('No se encontró la ubicación');
                }
            })
            .catch(error => console.error('Error al buscar la ubicación:', error));
    };

    // Manejar el evento de clic en el mapa
    map.on('click', (event) => {
        // Obtener las coordenadas del clic
        const coordinates = event.lngLat;

        // Mostrar las coordenadas en la consola
        console.log(`Coordenadas: Latitud: ${coordinates.lat}, Longitud: ${coordinates.lng}`);

        // Opcional: Agregar un marcador en el mapa
        new mapboxgl.Marker()
            .setLngLat([coordinates.lng, coordinates.lat]) // Establecer la posición del marcador
            .setPopup(new mapboxgl.Popup().setHTML(`<strong>Latitud:</strong> ${coordinates.lat}<br><strong>Longitud:</strong> ${coordinates.lng}`)) // Añadir un popup opcional
            .addTo(map); // Añadir el marcador al mapa
    });

    // Validación de teléfono
    const telefonoInput = document.getElementById('telefonos_numero');
    const errorTelefono = document.getElementById('error-telefono');

    const validateTelefono = () => {
        const valor = telefonoInput.value.trim();
        const telefonoRegex = /^\d{7,15}$/;

        if (valor === '') {
            telefonoInput.style.borderColor = 'red';
            errorTelefono.textContent = 'El teléfono es obligatorio';
        } else if (!telefonoRegex.test(valor)) {
            telefonoInput.style.borderColor = 'red';
            errorTelefono.textContent = 'Número de teléfono inválido';
        } else {
            telefonoInput.style.borderColor = 'green';
            errorTelefono.textContent = '';
        }
    };

    telefonoInput.addEventListener('input', validateTelefono);


    /*LO NUEVO*/
    // Validar selects del formulario 3
    const escuelaSelect = form.querySelector('select[name="escuelas_id"]');
    const formacionSelect = form.querySelector('select[name="formaciones_profesionales_id"]');
    const tipoSelect = form.querySelector('select[name="tipos_personas"]');

    const validarSelect = (select, errorId, mensajeError) => {
        const valor = select.value;
        const errorMensaje = document.getElementById(errorId);

        if (valor === '') {
            select.style.borderColor = 'red';
            errorMensaje.textContent = mensajeError;
        } else {
            select.style.borderColor = 'green';
            errorMensaje.textContent = '';
        }
    };

    // Evento de validación para Escuela
    escuelaSelect.addEventListener('change', () => {
        validarSelect(escuelaSelect, 'error-escuela', 'Debe seleccionar una escuela');
    });

    // Evento de validación para Formación Profesional
    formacionSelect.addEventListener('change', () => {
        validarSelect(formacionSelect, 'error-formacion', 'Debe seleccionar una formación profesional');
    });

    // Evento de validación para Tipo
    tipoSelect.addEventListener('change', () => {
        validarSelect(tipoSelect, 'error-tipo', 'Debe seleccionar un tipo válido');
    });

    // Validar al enviar el formulario
    form.addEventListener('submit', (e) => {
        validarSelect(escuelaSelect, 'error-escuela', 'Debe seleccionar una escuela');
        validarSelect(formacionSelect, 'error-formacion', 'Debe seleccionar una formación profesional');
        validarSelect(tipoSelect, 'error-tipo', 'Debe seleccionar un tipo válido');

        if (
            escuelaSelect.value === '' ||
            formacionSelect.value === '' ||
            tipoSelect.value === ''
        ) {
            e.preventDefault(); // Detener el envío si hay errores
        }
    });
});