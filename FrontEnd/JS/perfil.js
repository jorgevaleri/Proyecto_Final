// PERFIL

document.addEventListener('DOMContentLoaded', function () {

  // CARGAR FORMACIONES PROFESIONALES SEGUN ESCUELA
  (function () {
    const escSelect = document.getElementById('escuelas_id');
    const formSelect = document.getElementById('formaciones_id');
    const preSelectedForm = window.PERFIL_INST_FORM || 0;
    const preSelectedEsc = window.PERFIL_INST_ESC || 0;

    function loadFormaciones(escuelaId, preselect) {
      if (!formSelect) return;
      formSelect.innerHTML = '<option value="">Cargando…</option>';
      fetch(`registros.php?endpoint=formaciones&escuela=${encodeURIComponent(escuelaId)}`)
        .then(r => r.json())
        .then(data => {
          let h = '<option value="">-- Seleccione formación --</option>';
          data.forEach(f => {
            h += `<option value="${f.id}" ${f.id == preselect ? 'selected' : ''}>${f.nombre}</option>`;
          });
          formSelect.innerHTML = h;
        })
        .catch(err => {
          formSelect.innerHTML = '<option value="">Error cargando</option>';
          console.error('Error cargando formaciones:', err);
        });
    }

    // SI EL SERVIDOR CARGO ESCUELAS PRESELECCIONADA, CARGAR FORMACIONES
    if (preSelectedEsc) loadFormaciones(preSelectedEsc, preSelectedForm);

    // CAMBIOS MANUALES DEL SELECT DE ESCUELA
    if (escSelect) {
      escSelect.addEventListener('change', function () {
        if (this.value) loadFormaciones(this.value, 0);
        else if (formSelect) formSelect.innerHTML = '<option value="">-- Seleccione formación --</option>';
      });
    }
  })();

  // REINDEXAR RADIOS Y MANTENER NOMBRES DE ARRAYS
  function reindexDomicilios() {
    const blocks = document.querySelectorAll('#doms .dom-block');
    blocks.forEach((blk, i) => {
      const radio = blk.querySelector('input[type="radio"][name="domicilios_predeterminado"]');
      if (radio) radio.value = i;
      const lat = blk.querySelector('input[name="domicilios_latitud[]"]');
      const lon = blk.querySelector('input[name="domicilios_longitud[]"]');
      if (lat) lat.name = 'domicilios_latitud[]';
      if (lon) lon.name = 'domicilios_longitud[]';
    });
    if (blocks.length > 0) {
      const anyChecked = Array.from(document.querySelectorAll('#doms input[type="radio"][name="domicilios_predeterminado"]')).some(r => r.checked);
      if (!anyChecked) {
        const first = document.querySelector('#doms .dom-block input[type="radio"][name="domicilios_predeterminado"]');
        if (first) first.checked = true;
      }
    }
  }

  function reindexTelefonos() {
    const blocks = document.querySelectorAll('#tels .tel-block');
    blocks.forEach((blk, i) => {
      const radio = blk.querySelector('input[type="radio"][name="telefonos_predeterminado"]');
      if (radio) radio.value = i;
    });
    if (blocks.length > 0) {
      const anyChecked = Array.from(document.querySelectorAll('#tels input[type="radio"][name="telefonos_predeterminado"]')).some(r => r.checked);
      if (!anyChecked) {
        const first = document.querySelector('#tels .tel-block input[type="radio"][name="telefonos_predeterminado"]');
        if (first) first.checked = true;
      }
    }
  }

  // AÑADIR / ELIMINAR BLOQUES
  const domsContainer = document.getElementById('doms');
  const telsContainer = document.getElementById('tels');

  function ensureDeleteButtons() {

    // DOMICILIOS
    if (domsContainer) {
      domsContainer.querySelectorAll('.dom-block').forEach((blk) => {
        if (!blk.querySelector('.del-dom')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'del-dom';
          btn.textContent = '❌';
          blk.insertBefore(btn, blk.firstChild);
        }
      });

      // OCULTAR X SI SOLO HAY UNO
      const domCount = domsContainer.querySelectorAll('.dom-block').length;
      if (domCount <= 1) domsContainer.querySelectorAll('.del-dom').forEach(b => b.style.display = 'none');
      else domsContainer.querySelectorAll('.del-dom').forEach(b => b.style.display = 'inline-block');
    }

    // TELEFONOS
    if (telsContainer) {
      telsContainer.querySelectorAll('.tel-block').forEach((blk) => {
        if (!blk.querySelector('.del-tel')) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'del-tel';
          btn.textContent = '❌';
          blk.insertBefore(btn, blk.firstChild);
        }
      });

      // OCULTAR X SI SOLO HAY UNO
      const telCount = telsContainer.querySelectorAll('.tel-block').length;
      if (telCount <= 1) telsContainer.querySelectorAll('.del-tel').forEach(b => b.style.display = 'none');
      else telsContainer.querySelectorAll('.del-tel').forEach(b => b.style.display = 'inline-block');
    }
  }

  // ELIMINAR DOMICILIO
  if (domsContainer) {
    domsContainer.addEventListener('click', function (e) {
      if (e.target.matches('.del-dom')) {
        e.preventDefault();
        const blk = e.target.closest('.dom-block');
        if (!blk) return;
        const total = document.querySelectorAll('#doms .dom-block').length;
        if (total <= 1) {
          blk.querySelectorAll('input').forEach(inp => {
            if (inp.type === 'radio') { inp.checked = false; }
            else inp.value = '';
          });
          const mapDiv = blk.querySelector('.map');
          if (mapDiv) mapDiv.innerHTML = '';
        } else {
          blk.parentNode.removeChild(blk);
          reindexDomicilios();
          ensureDeleteButtons();
        }
      }
    });
  }

  // ELIMINAR TELEFONO
  if (telsContainer) {
    telsContainer.addEventListener('click', function (e) {
      if (e.target.matches('.del-tel')) {
        e.preventDefault();
        const blk = e.target.closest('.tel-block');
        if (!blk) return;
        const total = document.querySelectorAll('#tels .tel-block').length;
        if (total <= 1) {
          blk.querySelectorAll('input').forEach(inp => inp.value = '');
        } else {
          blk.parentNode.removeChild(blk);
          reindexTelefonos();
          ensureDeleteButtons();
        }
      }
    });
  }

  // AGREGAR NUEVO DOMICILIO
  const addDomBtn = document.getElementById('addDom');
  if (addDomBtn && domsContainer) {
    addDomBtn.addEventListener('click', function () {
      const first = domsContainer.querySelector('.dom-block');
      if (!first) return;
      const clone = first.cloneNode(true);
      clone.querySelectorAll('input').forEach(inp => {
        if (inp.type === 'radio') inp.checked = false;
        else inp.value = '';
      });
      const mapDiv = clone.querySelector('.map');
      if (mapDiv) mapDiv.innerHTML = '';
      domsContainer.appendChild(clone);
      reindexDomicilios();
      ensureDeleteButtons();
      const calle = clone.querySelector('input[name="domicilios_calle[]"]');
      if (calle) calle.focus();
    });
  }

  // AGREGAR NUEVO TELEFONO
  const addTelBtn = document.getElementById('addTel');
  if (addTelBtn && telsContainer) {
    addTelBtn.addEventListener('click', function () {
      const first = telsContainer.querySelector('.tel-block');
      if (!first) return;
      const clone = first.cloneNode(true);
      clone.querySelectorAll('input').forEach(inp => inp.value = '');
      telsContainer.appendChild(clone);
      reindexTelefonos();
      ensureDeleteButtons();
      const num = clone.querySelector('input[name="telefonos_numero[]"]');
      if (num) num.focus();
    });
  }

  // BUSCAR DIRECCION
  function performGeocode(query, cbSuccess, cbFail) {
    if (!window.GEOCODE_ENDPOINT) {
      console.error('No GEOCODE_ENDPOINT');
      if (cbFail) cbFail('No GEOCODE_ENDPOINT');
      return;
    }
    const url = window.GEOCODE_ENDPOINT + '&q=' + encodeURIComponent(query);
    fetch(url).then(r => r.json()).then(json => {
      let lat = null, lon = null;
      if (json && Array.isArray(json.results) && json.results.length) {
        const loc = json.results[0].geometry?.location;
        if (loc) { lat = loc.lat; lon = loc.lng; }
      } else if (json && (json.lat || json.lng || json.lon)) {
        lat = json.lat || json.latitud || null;
        lon = json.lng || json.longitud || json.lon || null;
      } else if (json && json[0] && (json[0].lat || json[0].lon)) {
        lat = json[0].lat; lon = json[0].lon;
      }
      if (lat !== null && lon !== null) cbSuccess({ lat: lat, lon: lon, raw: json });
      else cbFail('No se encontró la dirección.');
    }).catch(err => {
      console.error('Error en geocode:', err);
      cbFail('Error en geocode');
    });
  }

  // BOTON BUSCAR
  if (domsContainer) {
    domsContainer.addEventListener('click', function (e) {
      if (e.target.matches('.btn-search')) {
        e.preventDefault();
        const blk = e.target.closest('.dom-block');
        if (!blk) return;
        const queryInput = blk.querySelector('.map-search');
        const q = (queryInput && queryInput.value) ? queryInput.value.trim() : '';
        const mapDiv = blk.querySelector('.map');
        const latInput = blk.querySelector('input[name="domicilios_latitud[]"]');
        const lonInput = blk.querySelector('input[name="domicilios_longitud[]"]');
        if (!q) {
          showFormErrors(['Ingresá una dirección para buscar.']);
          return;
        }
        if (mapDiv) mapDiv.innerHTML = 'Buscando...';
        performGeocode(q, function (pos) {
          if (latInput) latInput.value = pos.lat;
          if (lonInput) lonInput.value = pos.lon;
          if (mapDiv) {
            mapDiv.innerHTML = `<iframe width="100%" height="150" frameborder="0" style="border:0"
              src="https://maps.google.com/maps?q=${encodeURIComponent(pos.lat)},${encodeURIComponent(pos.lon)}&z=15&output=embed"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
          }
        }, function (errMsg) {
          if (mapDiv) mapDiv.innerHTML = '';
          showFormErrors([errMsg || 'No se encontró la dirección.']);
        });
      }
    });
  }

  // RENDERIZAR MAPAS EXISTENTES AL CARGAR
  function renderExistingMaps() {
    if (!domsContainer) return;
    domsContainer.querySelectorAll('.dom-block').forEach(blk => {
      const latInput = blk.querySelector('input[name="domicilios_latitud[]"]');
      const lonInput = blk.querySelector('input[name="domicilios_longitud[]"]');
      const mapDiv = blk.querySelector('.map');
      const lat = latInput ? (latInput.value || '').trim() : '';
      const lon = lonInput ? (lonInput.value || '').trim() : '';
      if (lat !== '' && lon !== '' && mapDiv) {
        if (!mapDiv.querySelector('iframe')) {
          mapDiv.innerHTML = `<iframe width="100%" height="150" frameborder="0" style="border:0"
            src="https://maps.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lon)}&z=15&output=embed"
            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
        }
      }
    });
  }

  // VALIDACIONES
  const personForm = document.getElementById('personForm');
  const errorsDivId = 'form-errors';

  function clearFieldErrors() {
    const div = document.getElementById(errorsDivId);
    if (div) { div.innerHTML = ''; div.classList.add('hidden'); }
    document.querySelectorAll('#personForm input, #personForm select').forEach(i => i.classList.remove('error'));
  }

  function showFormErrors(arr) {
    const div = document.getElementById(errorsDivId);
    if (!div) {
      alert(arr.join("\n"));
      return;
    }
    div.innerHTML = '<ul>' + arr.map(e => '<li>' + e + '</li>').join('') + '</ul>';
    div.classList.remove('hidden');
    if (window.Swal) {
      Swal.fire({ icon: 'error', title: 'Errores', html: arr.join('<br>') });
    }
  }

  if (personForm) {
    personForm.addEventListener('submit', function (e) {
      clearFieldErrors();
      const errs = [];

      // CAMPOS OBLIGATORIOS
      const apellido = personForm.querySelector('[name="personas_apellido"]');
      const nombre = personForm.querySelector('[name="personas_nombre"]');
      const email = personForm.querySelector('[name="usuarios_email"]');
      const inst_tipo = personForm.querySelector('[name="institucional_tipo"]') || personForm.querySelector('[name="inst_tipo"]');
      const escuela = personForm.querySelector('[name="escuelas_id"]') || personForm.querySelector('[name="inst_escuela"]');
      const form = personForm.querySelector('[name="formaciones_id"]') || personForm.querySelector('[name="inst_formacion"]');

      if (!apellido || (apellido.value || '').trim() === '') { errs.push('Apellido es obligatorio.'); if (apellido) apellido.classList.add('error'); }
      if (!nombre || (nombre.value || '').trim() === '') { errs.push('Nombre es obligatorio.'); if (nombre) nombre.classList.add('error'); }

      if (!email || (email.value || '').trim() === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        errs.push('Email inválido o vacío.'); if (email) email.classList.add('error');
      }

      if (inst_tipo && inst_tipo.value === '') { errs.push('Seleccioná un tipo institucional.'); inst_tipo.classList.add('error'); }
      if (escuela && (escuela.value === '' || escuela.value === '0')) { errs.push('Seleccioná una escuela válida.'); escuela.classList.add('error'); }
      if (form && (form.value === '' || form.value === '0')) { errs.push('Seleccioná una formación válida.'); form.classList.add('error'); }

      // CONTRASEÑA, SI UNO DE LOS 3 CAMPOS ESTA COMPLETO, VALIDAMOS EL CONJUNTO
      const pass_actual = personForm.querySelector('[name="pass_actual"]');
      const pass_nueva = personForm.querySelector('[name="pass_nueva"]');
      const pass_conf = personForm.querySelector('[name="pass_conf"]');
      const anyPass = (pass_actual && pass_actual.value) || (pass_nueva && pass_nueva.value) || (pass_conf && pass_conf.value);
      if (anyPass) {
        if (!pass_actual || !pass_nueva || !pass_conf) {
          errs.push('Para cambiar la contraseña completá los tres campos.');
        } else {
          if (pass_nueva.value.length < 6) { errs.push('La nueva contraseña debe tener al menos 6 caracteres.'); pass_nueva.classList.add('error'); }
          if (pass_nueva.value !== pass_conf.value) { errs.push('La nueva contraseña y la confirmación no coinciden.'); pass_conf.classList.add('error'); }
        }
      }

      if (errs.length) {
        e.preventDefault();
        showFormErrors(errs);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return false;
      }
    });
  }

  // MOSTRAR NOTIFICACIONES
  try {

    // ERROR DEL SERVIDOR
    if (Array.isArray(window.PERFIL_FLASH_ERRORS) && window.PERFIL_FLASH_ERRORS.length) {
      showFormErrors(window.PERFIL_FLASH_ERRORS);
    }

    // EXITO
    if (window.PERFIL_FLASH_SUCCESS) {
      if (window.Swal) {
        Swal.fire({ icon: 'success', title: 'Éxito', text: window.PERFIL_FLASH_SUCCESS });
      } else {
        console.log('SUCCESS:', window.PERFIL_FLASH_SUCCESS);
      }
    }

    // ACTUALIZACION
    if (window.PERFIL_UPDATED_FLAG && window.PERFIL_UPDATED_FLAG === true) {
      if (window.Swal) {
        Swal.fire({ icon: 'success', title: 'Perfil actualizado', text: 'Los cambios se guardaron correctamente.' });
      } else {
        alert('Perfil actualizado: los cambios se guardaron correctamente.');
      }
    }
  } catch (e) {
    console.error('Error mostrando notificaciones perfil:', e);
  }

  // INICIALIZACION FINAL
  ensureDeleteButtons();
  reindexDomicilios();
  reindexTelefonos();
  renderExistingMaps();

  // PARCHE RAPIDO PARA SVG, EVITA ADVERTENCIAS
  document.querySelectorAll('svg[viewBox*="%"]').forEach(svg => {
    try {
      const old = svg.getAttribute('viewBox');
      const fixed = old.replace(/%/g, '');
      svg.setAttribute('viewBox', fixed);
      console.warn('SVG viewBox corregido automáticamente:', old, '->', fixed);
    } catch (e) { /* noop */ }
  });

});