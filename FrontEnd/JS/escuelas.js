// ESCUELAS

document.addEventListener('DOMContentLoaded', function () {

  // MOSTRAR ALERTAS CON SWEETALERT2
  (function mostrarMsgUrl() {
    try {
      const p = new URLSearchParams(window.location.search);
      const m = p.get('msg');
      if (!m) return;
      if (window.Swal) {
        if (m === 'guardado') Swal.fire({ icon: 'success', title: 'Guardado', text: 'La escuela fue creada correctamente.' });
        else if (m === 'editado') Swal.fire({ icon: 'success', title: 'Editado', text: 'Cambios guardados correctamente.' });
        else if (m === 'eliminado') Swal.fire({ icon: 'success', title: 'Eliminado', text: 'La escuela fue eliminada.' });
        else if (m === 'restaurado') Swal.fire({ icon: 'success', title: 'Restaurado', text: 'La escuela fue restaurada.' });
      }
    } catch (e) { console.warn(e); }
  })();

  // VARIABLES DEL MAPA
  const contMap = document.getElementById('map');
  let mapa = null;
  let marcador = null;

  // INICIALIZA EL MAPA CON LEAFLET
  function inicializarMapaSeguro() {
    if (!contMap) return;
    if (typeof L === 'undefined') {
      console.error('Leaflet no está cargado. Añadí su <link> y <script> en head.php.');
      return;
    }

    // SI YA HAY UN MAPA GLOBAL, SE REMUEVE PARA EVITAR CONTAINER ALREDY INITIALIZED
    if (window._mapEscuelas) {
      try { window._mapEscuelas.remove(); } catch (e) { console.warn(e); }
      window._mapEscuelas = null;
    }

    // COORDENADAS INICIALES
    const latInput = document.getElementById('domicilio_lat');
    const lngInput = document.getElementById('domicilio_lng');
    const lat0 = parseFloat(latInput?.value || '') || -28.4682;
    const lng0 = parseFloat(lngInput?.value || '') || -65.7795;

    // CREAR MAPA
    window._mapEscuelas = L.map('map').setView([lat0, lng0], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(window._mapEscuelas);

    // MARCADOR ARRASTRABLE
    marcador = L.marker([lat0, lng0], { draggable: true }).addTo(window._mapEscuelas);
    marcador.on('moveend', function (e) {
      const p = e.target.getLatLng();
      if (latInput) latInput.value = p.lat;
      if (lngInput) lngInput.value = p.lng;
    });

    // SI EL INPUTS OCULTO YA CONTIENE COORDENADAS, CENTRAMOS Y COLOCAMOS MARCADOR
    if (latInput?.value && lngInput?.value) {
      const la = parseFloat(latInput.value), lo = parseFloat(lngInput.value);
      marcador.setLatLng([la, lo]);
      window._mapEscuelas.setView([la, lo], 16);
    }

    mapa = window._mapEscuelas;
    return { mapa, marcador };
  }

  const mapaData = inicializarMapaSeguro();

  // GEOCODIFICACION, USAR PROXY PARA EVITAR CORS/403
  async function geocodificarViaProxy(q) {
    if (!q || !q.trim()) {
      if (window.Swal) Swal.fire({ icon: 'info', title: 'Atención', text: 'Ingresá texto para buscar.' });
      return null;
    }

    const base = window.GEOCODE_ENDPOINT || 'registrarse.php';
    const url = `${base}?action=geocode&q=${encodeURIComponent(q)}`;

    try {
      const resp = await fetch(url);
      if (!resp.ok) {
        let txt = await resp.text().catch(() => '');
        console.error('Error proxy geocode:', resp.status, txt);
        if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'Error en el servicio de geocodificación (proxy).' });
        return null;
      }
      const datos = await resp.json();
      if (!Array.isArray(datos) || !datos[0]) {
        if (window.Swal) Swal.fire({ icon: 'warning', title: 'No encontrado', text: 'No se obtuvo resultado.' });
        return null;
      }
      return datos[0];
    } catch (e) {
      console.error('Error fetch geocode proxy:', e);
      if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo consultar el servicio de geocodificación.' });
      return null;
    }
  }

  // BOTON BUSCAR
  const inputMapSearch = document.getElementById('map_search');
  const btnMapSearch = document.getElementById('btn_map_search');

  async function handleBuscar() {
    if (!inputMapSearch) return;
    const q = inputMapSearch.value;
    const res = await geocodificarViaProxy(q);
    if (!res) return;
    const lat = parseFloat(res.lat), lon = parseFloat(res.lon);

    // MOVER MARCADOR Y CENTRAR
    if (window._mapEscuelas && marcador) {
      marcador.setLatLng([lat, lon]);
      window._mapEscuelas.setView([lat, lon], 16);
    }

    // ACTUALIZAR INPUTS
    const inLat = document.getElementById('domicilio_lat');
    const inLng = document.getElementById('domicilio_lng');
    if (inLat) inLat.value = lat;
    if (inLng) inLng.value = lon;
  }

  if (btnMapSearch) btnMapSearch.addEventListener('click', function (e) { e.preventDefault(); handleBuscar(); });
  if (inputMapSearch) {
    inputMapSearch.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); handleBuscar(); }
    });
  }

  // VALIDACION DEL FORMULARIO
  function validarFormulario() {
    const errores = [];
    const nombre = (document.querySelector('input[name="escuelas_nombre"]')?.value || '').trim();
    const cue = (document.querySelector('input[name="escuelas_cue"]')?.value || '').trim();
    const domicilioReal = (document.querySelector('input[name="domicilio_calle"]')?.value || '').trim();
    const lat = (document.getElementById('domicilio_lat')?.value || '').trim();
    const lng = (document.getElementById('domicilio_lng')?.value || '').trim();

    if (!nombre) errores.push('Debe ingresar el nombre de la escuela.');
    if (!cue) errores.push('Debe ingresar el CUE.');
    else if (!/^\d+$/.test(cue)) errores.push('El CUE debe contener solo dígitos.');
    if (!domicilioReal) errores.push('Debe ingresar la dirección real (calle y número).');
    if (!lat || !lng) errores.push('Debe ubicar la escuela en el mapa (obtener coordenadas).');

    return errores;
  }

  // MANEJO DEL ENVIO, VALIDAR Y CONFIRMAR CON SWEETALERT2
  const form = document.getElementById('escuelaForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const errores = validarFormulario();
      if (errores.length) {
        const html = '<ul style="text-align:left;">' + errores.map(x => `<li>${x}</li>`).join('') + '</ul>';
        if (window.Swal) {
          Swal.fire({ icon: 'warning', title: 'Errores en el formulario', html, confirmButtonText: 'Corregir' });
        } else {
          alert('Errores: ' + errores.join('; '));
        }
        return;
      }

      if (window.Swal) {
        Swal.fire({
          title: '¿Confirmás guardar?',
          text: 'Se guardarán los datos de la escuela.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, guardar',
          cancelButtonText: 'Cancelar'
        }).then(function (res) {
          if (res.isConfirmed) form.submit();
        });
      } else {
        form.submit();
      }
    });
  }

});