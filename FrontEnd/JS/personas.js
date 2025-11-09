// PERSONAS

document.addEventListener('DOMContentLoaded', () => {

  // ROOT
  const pageRoot = document.querySelector('.fp-page') || document.querySelector('main');
  if (!pageRoot) return;

  // BUSCAMOS FORMULARIO
  const personForm = pageRoot.querySelector('#personForm') || pageRoot.querySelector('form');
  if (!personForm) {
    return;
  }

  // SELECTORES IMPORTANTES
  const inputApellido = personForm.querySelector('input[name="personas_apellido"]');
  const inputNombre = personForm.querySelector('input[name="personas_nombre"]');

  function showFormMessage(msg) {
    const errBox = document.getElementById('personFormValidationMessage') || document.getElementById('personFormValidationMessage');
    if (errBox) {
      errBox.textContent = msg;
      errBox.classList.add('error');
      errBox.style.display = 'block';
    } else {
      alert(msg);
    }
  }

  personForm.addEventListener('submit', function (e) {
    if (inputApellido && inputApellido.value.trim() === '') {
      e.preventDefault();
      showFormMessage('Debe ingresar el apellido.');
      if (inputApellido) inputApellido.focus();
    }
  });

  // ASEGURA QUE UN RADIO ESTE MARCADO
  function ensureFirstRadioChecked(container, radioName) {
    if (!container) return;
    const radios = container.querySelectorAll(`input[type="radio"][name="${radioName}"]`);
    if (!radios || radios.length === 0) return;
    let any = false;
    radios.forEach(r => { if (r.checked) any = true; });
    if (!any) {
      radios[0].checked = true;
    }
  }

  function attachRadioFixes(container, radioName) {
    if (!container) return;
    container.addEventListener('change', () => ensureFirstRadioChecked(container, radioName));
  }

  // DOMICILIOS
  const doms = personForm.querySelector('#doms');
  if (doms) {
    let baseDom = doms.querySelector('.dom-block');
    const btnDom = personForm.querySelector('#addDom');

    if (baseDom) {
      function initDom(block) {
        const delBtn = block.querySelector('.del-dom');
        if (delBtn) {
          delBtn.removeEventListener && delBtn.removeEventListener('click', () => { });
          delBtn.addEventListener('click', () => {
            block.remove();
            ensureFirstRadioChecked(doms, 'domicilios_predeterminado');
          });
        }

        // ELEMENTOS DEL MAPA
        const mapEl = block.querySelector('.map');
        const search = block.querySelector('.map-search');
        const btnSearch = block.querySelector('.btn-search');
        const latInput = block.querySelector('input[name="domicilios_latitud[]"]');
        const lngInput = block.querySelector('input[name="domicilios_longitud[]"]');

        if (mapEl) {
          mapEl.innerHTML = '';
          try {

            // CREAR MAPA
            const map = L.map(mapEl).setView([-28.4682, -65.7795], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap'
            }).addTo(map);

            // MARCADOR ARRASTRABLE
            const marker = L.marker(map.getCenter(), { draggable: true }).addTo(map);
            marker.on('moveend', e => {
              const { lat, lng } = e.target.getLatLng();
              if (latInput) latInput.value = lat;
              if (lngInput) lngInput.value = lng;
            });

            // CENTRAR SI HAY COORDENADAS
            if (latInput && latInput.value && lngInput && lngInput.value) {
              const la = parseFloat(latInput.value);
              const lo = parseFloat(lngInput.value);
              if (!Number.isNaN(la) && !Number.isNaN(lo)) {
                marker.setLatLng([la, lo]);
                map.setView([la, lo], 15);
              }
            }

            // BUSQUEDA POR TEXTO
            if (btnSearch && search) {
              btnSearch.addEventListener('click', async (ev) => {
                ev.preventDefault();
                const q = (search.value || '').trim();
                if (!q) return alert('Ingresa una dirección');
                const base = (window.GEOCODE_ENDPOINT && typeof window.GEOCODE_ENDPOINT === 'string')
                  ? window.GEOCODE_ENDPOINT
                  : (function () {
                    const loc = window.location;
                    const pathParts = loc.pathname.split('/');
                    pathParts.pop();
                    return loc.origin + pathParts.join('/') + '/registrarse.php?action=geocode';
                  })();
                const url = base + '&q=' + encodeURIComponent(q);
                try {
                  const resp = await fetch(url, { credentials: 'same-origin' });
                  if (!resp.ok) {
                    console.error('geocode HTTP', resp.status);
                    return alert('Error al buscar en el mapa (servidor).');
                  }
                  const contentType = (resp.headers.get('content-type') || '').toLowerCase();
                  if (contentType.indexOf('application/json') === -1) {
                    const txt = await resp.text();
                    console.error('geocode no JSON:', txt);
                    return alert('Respuesta inválida del servicio de geocodificación.');
                  }
                  const data = await resp.json();
                  if (!Array.isArray(data) || !data[0]) {
                    return alert('Dirección no encontrada');
                  }
                  const la = parseFloat(data[0].lat);
                  const lo = parseFloat(data[0].lon);
                  if (!Number.isNaN(la) && !Number.isNaN(lo)) {
                    marker.setLatLng([la, lo]);
                    map.setView([la, lo], 15);
                    if (latInput) latInput.value = la;
                    if (lngInput) lngInput.value = lo;
                  } else {
                    alert('Coordenadas inválidas retornadas por el servicio.');
                  }
                } catch (err) {
                  console.error('geocode error', err);
                  alert('Error al buscar en el mapa (conexión).');
                }
              });
            }
          } catch (err) {
            console.error('Leaflet init error', err);
            if (mapEl) mapEl.textContent = 'Mapa no disponible';
          }
        }
      }

      // INICIALIZAR TODOS LOS BLOQUES
      doms.querySelectorAll('.dom-block').forEach((blk) => initDom(blk));

      // MARCAR EL PRIMER RADIO, SI NO HAY OTRO MARCADO
      ensureFirstRadioChecked(doms, 'domicilios_predeterminado');
      attachRadioFixes(doms, 'domicilios_predeterminado');

      // AGREGAR DOMICILIO
      if (btnDom) {
        btnDom.addEventListener('click', () => {
          const idx = doms.querySelectorAll('.dom-block').length;
          const clone = baseDom.cloneNode(true);

          clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
          const radio = clone.querySelector('input[type="radio"][name]');
          if (radio) {
            radio.value = idx;
            radio.name = 'domicilios_predeterminado';
            radio.checked = false;
          } else {
            const lab = document.createElement('label');
            lab.innerHTML = 'Predeterminado <input type="radio" name="domicilios_predeterminado" value="' + idx + '">';
            clone.appendChild(lab);
          }

          // BOTON ELIMINAR
          if (!clone.querySelector('.del-dom')) {
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'del-dom';
            del.textContent = '❌';
            clone.insertBefore(del, clone.firstChild);
          }

          doms.appendChild(clone);
          initDom(clone);
          ensureFirstRadioChecked(doms, 'domicilios_predeterminado');
        });
      }
    }
  }

  // TELEFONOS
  const tels = personForm.querySelector('#tels');
  if (tels) {
    const baseTel = tels.querySelector('.tel-block');
    const btnTel = personForm.querySelector('#addTel');

    function initTel(block) {
      const delBtn = block.querySelector('.del-tel');
      if (delBtn) {
        delBtn.addEventListener('click', () => {
          block.remove();
          ensureFirstRadioChecked(tels, 'telefonos_predeterminado');
        });
      }
    }

    tels.querySelectorAll('.tel-block').forEach((blk) => initTel(blk));

    // MARCAR EL PRIMER RADIO, SI NO HAY OTRO MARCADO
    ensureFirstRadioChecked(tels, 'telefonos_predeterminado');
    attachRadioFixes(tels, 'telefonos_predeterminado');

    // AGREGAR TELEFONO
    if (btnTel && baseTel) {
      btnTel.addEventListener('click', () => {
        const idx = tels.querySelectorAll('.tel-block').length;
        const clone = baseTel.cloneNode(true);
        clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
        const radio = clone.querySelector('input[type="radio"][name]');
        if (radio) {
          radio.value = idx;
          radio.name = 'telefonos_predeterminado';
          radio.checked = false;
        } else {
          const lab = document.createElement('label');
          lab.innerHTML = 'Predeterminado <input type="radio" name="telefonos_predeterminado" value="' + idx + '">';
          clone.appendChild(lab);
        }

        // BOTON ELIMINAR
        if (!clone.querySelector('.del-tel')) {
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'del-tel';
          del.textContent = '❌';
          clone.insertBefore(del, clone.firstChild);
        }
        tels.appendChild(clone);
        initTel(clone);
        ensureFirstRadioChecked(tels, 'telefonos_predeterminado');
      });
    }
  }

  // INSTITUCIONAL
  const tipoSel = personForm.querySelector('#inst_tipo');
  const escuelaLab = personForm.querySelector('#label_escuela');
  const formacionLab = personForm.querySelector('#label_formacion');

  function toggleInstitucional() {
    if (!tipoSel) return;
    const val = tipoSel.value;
    if (val === 'Director') {
      if (escuelaLab) escuelaLab.style.display = 'block';
      if (formacionLab) formacionLab.style.display = 'none';
    } else if (val === 'Docente') {
      if (escuelaLab) escuelaLab.style.display = 'block';
      if (formacionLab) formacionLab.style.display = 'block';
    } else if (val === 'Alumno') {
      if (escuelaLab) escuelaLab.style.display = 'none';
      if (formacionLab) formacionLab.style.display = 'none';
    } else {
      if (escuelaLab) escuelaLab.style.display = 'none';
      if (formacionLab) formacionLab.style.display = 'none';
    }
  }
  if (tipoSel) {
    tipoSel.addEventListener('change', toggleInstitucional);
    toggleInstitucional();
  }

});