// REGISTRARSE

document.addEventListener('DOMContentLoaded', function () {

  // PASOS 1 Y 2
  const step1 = document.getElementById('step1');
  const step2 = document.getElementById('step2');

  // EVITAR NAVEGACION POR DEFECTO
  document.getElementById('toStep2')?.addEventListener('click', (e) => {
    e.preventDefault();

    // USAMOS LAS VALIDACIONES EXTERNAS
    if (window.validateStep1_andMaybeAdvance && typeof window.validateStep1_andMaybeAdvance === 'function') {
      try {
        window.validateStep1_andMaybeAdvance();
        return;
      } catch (err) {
        console.error('Error al ejecutar validateStep1_andMaybeAdvance():', err);
      }
    }

    // ESCONDER PASO 1 Y MOSTRAR PASO 2
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    if (step1) step1.style.display = 'none';
    if (step2) step2.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // OJO MOSTRAR / OCULTAR CONTRASEÑA
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    const selector = btn.getAttribute('toggle');
    const input = document.querySelector(selector);
    if (!input) return;
    if (input.type === 'text') btn.classList.add('active');
    else btn.classList.remove('active');

    btn.addEventListener('click', function () {
      if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('active');
        btn.title = 'Ocultar contraseña';
      } else {
        input.type = 'password';
        btn.classList.remove('active');
        btn.title = 'Mostrar contraseña';
      }
    });
  });

  // ASEGURAR QUE SIEMPRE HAYA UN RADIO MARCADO
  function ensureFirstChecked(name) {
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    if (!radios || radios.length === 0) return;
    if (![...radios].some(r => r.checked)) radios[0].checked = true;
  }
  ensureFirstChecked('domicilios_predeterminado');
  ensureFirstChecked('telefonos_predeterminado');

  // DOMICILIOS
  const domsContainer = document.getElementById('doms');
  const baseDom = domsContainer ? domsContainer.querySelector('.dom-block') : null;
  const addDomBtn = document.getElementById('addDom');

  function initDomBlock(block, index) {
    const delBtn = block.querySelector('.del-dom');
    if (delBtn) {
      delBtn.addEventListener('click', function () {
        const wasChecked = block.querySelector('input[type="radio"][name="domicilios_predeterminado"]')?.checked;
        block.remove();
        domsContainer.querySelectorAll('.dom-block').forEach((b, i) => {
          const r = b.querySelector('input[type="radio"][name="domicilios_predeterminado"]');
          if (r) r.value = i;
        });
        if (wasChecked) ensureFirstChecked('domicilios_predeterminado');
      });
    }

    // ELEMENTOS PARA MAPA
    const mapEl = block.querySelector('.map');
    const searchInput = block.querySelector('.map-search');
    const btnSearch = block.querySelector('.btn-search');
    const latInput = block.querySelector('input[name="domicilios_latitud[]"]');
    const lngInput = block.querySelector('input[name="domicilios_longitud[]"]');

    if (!mapEl || typeof L === 'undefined') return;

    // INICIALIZAR LEAFLET
    mapEl.innerHTML = '';
    const defaultCenter = [-34.6037, -58.3816];
    const map = L.map(mapEl).setView(defaultCenter, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(map);

    const marker = L.marker(map.getCenter(), { draggable: true }).addTo(map);
    marker.on('moveend', function () {
      const { lat, lng } = marker.getLatLng();
      if (latInput) latInput.value = lat;
      if (lngInput) lngInput.value = lng;
    });

    // SI YA EXISTEN COORDENADAS, APLICARLAS
    if (latInput && latInput.value && lngInput && lngInput.value) {
      const la = parseFloat(latInput.value), lo = parseFloat(lngInput.value);
      if (!isNaN(la) && !isNaN(lo)) {
        marker.setLatLng([la, lo]);
        map.setView([la, lo], 15);
      }
    }

    // BUSCADOR
    function doSearch() {
      const q = (searchInput.value || '').trim();
      if (!q) return alert('Ingresa una dirección');

      const endpointBase = (window.GEOCODE_ENDPOINT && typeof window.GEOCODE_ENDPOINT === 'string')
        ? window.GEOCODE_ENDPOINT
        : (function () {
          const loc = window.location;
          const baseHref = loc.href.split('?')[0].split('#')[0];
          if (baseHref.endsWith('registrarse.php')) return baseHref;
          const pathParts = loc.pathname.split('/');
          pathParts.pop();
          const folder = pathParts.join('/') + '/';
          return loc.origin + folder + 'registrarse.php';
        })();

      const url = endpointBase + '?action=geocode&q=' + encodeURIComponent(q);
      console.log('Geocode: fetching ->', url);

      fetch(url, { credentials: 'same-origin' })
        .then(resp => resp.text().then(text => ({ resp, text })))
        .then(({ resp, text }) => {
          if (!resp.ok) {
            console.error('Geocode HTTP error:', resp.status, resp.statusText, 'response:', text);
            throw new Error('Geocoding HTTP error: ' + resp.status + ' ' + resp.statusText);
          }
          let data;
          try {
            data = JSON.parse(text);
          } catch (err) {
            console.error('Geocode: respuesta no-JSON (texto):', text.slice(0, 1200));
            throw new Error('Respuesta no-JSON del servidor. Ver consola para ver el contenido.');
          }
          return data;
        })
        .then(data => {
          if (!data) throw new Error('Geocode: datos vacíos');
          if (data.error) {
            console.error('Geocode server error object:', data);
            alert('Error del servicio de geocoding: ' + (data.error || 'Sin detalles (ver consola)'));
            return;
          }
          if (!Array.isArray(data) || !data[0]) {
            alert('Dirección no encontrada');
            return;
          }
          const item = data[0];
          const la = parseFloat(item.lat);
          const lo = parseFloat(item.lon);
          if (isNaN(la) || isNaN(lo)) {
            console.error('Geocode returned invalid coords:', item);
            alert('Coordenadas inválidas recibidas del servicio');
            return;
          }
          marker.setLatLng([la, lo]);
          map.setView([la, lo], 15);
          if (latInput) latInput.value = la;
          if (lngInput) lngInput.value = lo;
        })
        .catch(err => {
          console.error('Geocode error:', err);
          alert('Error al buscar en el mapa. Revisá la consola para más detalles.');
        });
    }

    // BOTON BUSCAR
    btnSearch && btnSearch.addEventListener('click', doSearch);
    searchInput && searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        doSearch();
      }
    });
  }

  // INICIALIZAR BLOQUES EXISTENTES
  if (domsContainer) {
    domsContainer.querySelectorAll('.dom-block').forEach((blk, i) => initDomBlock(blk, i));

    // AGREGAR DOMICILIO
    addDomBtn?.addEventListener('click', function () {
      if (!baseDom) return;
      const idx = domsContainer.children.length;
      const clone = baseDom.cloneNode(true);

      clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
      const radio = clone.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = false;
        radio.value = idx;
      }

      // BOTON ELIMINAR
      if (!clone.querySelector('.del-dom')) {
        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'del-dom';
        del.textContent = '❌';
        clone.insertBefore(del, clone.firstChild);
      }

      domsContainer.appendChild(clone);
      initDomBlock(clone, idx);
    });
  }

  // TELEFONOS
  const telsContainer = document.getElementById('tels');
  const baseTel = telsContainer ? telsContainer.querySelector('.tel-block') : null;
  const addTelBtn = document.getElementById('addTel');

  function initTelBlock(block, idx) {
    const delBtn = block.querySelector('.del-tel');
    if (delBtn) {
      delBtn.addEventListener('click', function () {
        const wasChecked = block.querySelector('input[type="radio"][name="telefonos_predeterminado"]')?.checked;
        block.remove();
        telsContainer.querySelectorAll('.tel-block').forEach((b, i) => {
          const r = b.querySelector('input[type="radio"][name="telefonos_predeterminado"]');
          if (r) r.value = i;
        });
        if (wasChecked) ensureFirstChecked('telefonos_predeterminado');
      });
    }
  }

  if (telsContainer) {
    telsContainer.querySelectorAll('.tel-block').forEach((blk, i) => initTelBlock(blk, i));

    // AGREGAR TELEFONO
    addTelBtn?.addEventListener('click', function () {
      if (!baseTel) return;
      const idx = telsContainer.children.length;
      const clone = baseTel.cloneNode(true);
      clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
      const radio = clone.querySelector('input[type="radio"]');
      if (radio) {
        radio.checked = false;
        radio.value = idx;
      }

      // BOTON ELIMINAR
      if (!clone.querySelector('.del-tel')) {
        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'del-tel';
        del.textContent = '❌';
        clone.insertBefore(del, clone.firstChild);
      }
      telsContainer.appendChild(clone);
      initTelBlock(clone, idx);
    });
  }

  // MOSTRAR / OCULTAR SELECTS INSTITUCIONALES SEGUN TIPO
  const tipoSel = document.getElementById('inst_tipo');
  const escuelaLab = document.getElementById('label_escuela');
  const formacionLab = document.getElementById('label_formacion');

  function toggleInstitucional() {
    const val = tipoSel ? tipoSel.value : '';
    if (val === 'Director') {
      if (escuelaLab) escuelaLab.style.display = 'block';
      if (formacionLab) formacionLab.style.display = 'none';
    } else if (val === 'Docente') {
      if (escuelaLab) escuelaLab.style.display = 'block';
      if (formacionLab) formacionLab.style.display = 'block';
    } else {
      if (escuelaLab) escuelaLab.style.display = 'none';
      if (formacionLab) formacionLab.style.display = 'none';
    }
  }
  tipoSel?.addEventListener('change', toggleInstitucional);
  toggleInstitucional();

  // ASEGURAR PRIMER RADIO EN AMBOS GRUPOS SI QUEDO SIN MARCAR
  ensureFirstChecked('domicilios_predeterminado');
  ensureFirstChecked('telefonos_predeterminado');

});