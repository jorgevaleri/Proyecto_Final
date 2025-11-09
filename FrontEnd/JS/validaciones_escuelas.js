// VALIDACIONES ESCUELAS

document.addEventListener('DOMContentLoaded', function () {

  // REFERENCIAS DOM
  const form = document.getElementById('escuelaForm');
  const nombreInput = document.getElementById('escuelas_nombre');
  const cueInput = document.getElementById('escuelas_cue');
  const mapSearch = document.getElementById('map_search');
  const domicilioCalleInput = document.getElementById('domicilio_calle');
  const latInput = document.getElementById('domicilio_lat');
  const lngInput = document.getElementById('domicilio_lng');
  const escuelasIdInput = document.getElementById('escuelas_id');
  const submitButtons = form ? form.querySelectorAll('button[type="submit"]') : [];

  // CONTENEDOR FIJO PARA ERRORES
  let errBox = document.getElementById('escuelaFormError');
  if (!errBox && form) {
    const created = document.createElement('div');
    created.id = 'escuelaFormError';
    created.className = 'error-box';
    created.setAttribute('role', 'status');
    created.setAttribute('aria-live', 'polite');
    form.insertBefore(created, form.firstChild);
    errBox = created;
  }

  // MOSTRAR / OCULTAR ERRORES
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function showError(msg) {
    if (!errBox) {
      alert(msg);
      return;
    }
    errBox.innerHTML = '<p class="msg">' + escapeHtml(msg) + '</p>';
    errBox.classList.add('visible');
    errBox.setAttribute('aria-hidden', 'false');
    errBox.setAttribute('role', 'status');
    errBox.setAttribute('aria-live', 'polite');
    errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function clearError() {
    if (!errBox) return;
    errBox.classList.remove('visible');
    setTimeout(function () {
      if (!errBox.classList.contains('visible')) {
        errBox.innerHTML = '';
        errBox.setAttribute('aria-hidden', 'true');
      }
    }, 220);
  }

  function checkCueExists(cue, excludeId = null) {
    return new Promise(function (resolve, reject) {
      const formData = new FormData();
      formData.append('ajax_action', 'check_cue');
      formData.append('cue', cue);
      if (excludeId) formData.append('exclude_id', excludeId);

      fetch('escuelas.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(response => {
          return response.text().then(text => ({ ok: response.ok, status: response.status, text }));
        })
        .then(obj => {
          if (!obj.ok) {
            console.warn('check_cue: respuesta HTTP no OK', obj.status, obj.text);
            reject('Error HTTP del servidor al verificar CUE (ver consola).');
            return;
          }

          let data;
          try {
            data = JSON.parse(obj.text);
          } catch (err) {
            console.error('check_cue: respuesta no JSON del servidor:', obj.text);
            reject('El servidor devolvió una respuesta inesperada al verificar el CUE. Revisa la consola (Network → escuelas.php) para ver la respuesta completa.');
            return;
          }

          if (data && data.ok === false && data.error === 'sin_session') {
            window.location = 'index.php';
            resolve(undefined);
            return;
          }

          if (data && typeof data.exists !== 'undefined' && data.ok === true) {
            resolve(Boolean(data.exists));
          } else {
            console.warn('check_cue: JSON inesperado:', data);
            reject('Respuesta inválida del servidor al verificar CUE.');
          }
        })
        .catch(err => {
          console.error('check_cue fetch error:', err);
          reject('Error de red al verificar CUE');
        });
    });
  }

  // VALIDACIONES DEL CUE
  if (cueInput) {
    cueInput.addEventListener('blur', function () {
      clearError();
      const cue = cueInput.value.trim();
      if (cue === '') return;
      if (!/^\d+$/.test(cue)) { showError('El CUE debe ser numérico.'); return; }
      const excludeId = escuelasIdInput ? escuelasIdInput.value : null;
      checkCueExists(cue, excludeId)
        .then(exists => {
          if (exists === undefined) return;
          if (exists) showError('Ya existe una escuela con ese CUE.');
          else clearError();
        })
        .catch(err => {
          console.warn('check_cue error:', err);
        });
    });
  }

  let tempBeforeUnloadListener = null;
  function addTempBeforeUnloadBlocker() {
    if (tempBeforeUnloadListener) return;
    tempBeforeUnloadListener = function (e) {
      if (e && typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
    };
    window.addEventListener('beforeunload', tempBeforeUnloadListener, true);
  }
  function removeTempBeforeUnloadBlocker() {
    if (!tempBeforeUnloadListener) return;
    window.removeEventListener('beforeunload', tempBeforeUnloadListener, true);
    tempBeforeUnloadListener = null;
  }

  // VALIDACIONES LOCALES Y ENVIO
  function validateAndSubmit(originalEventTarget) {
    clearError();

    const nombre = nombreInput ? nombreInput.value.trim() : '';
    const cue = cueInput ? cueInput.value.trim() : '';
    const domReal = domicilioCalleInput ? domicilioCalleInput.value.trim() : '';
    const mapTxt = mapSearch ? mapSearch.value.trim() : '';
    const lat = latInput ? latInput.value.trim() : '';
    const lng = lngInput ? lngInput.value.trim() : '';
    const excludeId = escuelasIdInput ? escuelasIdInput.value : null;

    // VALIDACIONES LOCALES
    if (nombre === '') { showError('Debe ingresar un nombre.'); return; }
    if (cue === '') { showError('Debe ingresar un CUE.'); return; }
    if (!/^\d+$/.test(cue)) { showError('El CUE debe contener sólo dígitos (numérico).'); return; }
    if (domReal === '') { showError('Debe ingresar la dirección real.'); return; }
    if (mapTxt === '') { showError('Debes ingresar texto para el mapa.'); return; }
    if (lat === '' || lng === '') { showError('Espera a que se calculen las coordenadas (buscar en mapa).'); return; }

    // DESACTIVAR BOTONES
    submitButtons.forEach(b => b.disabled = true);

    // BLOQUEO TEMPORAL
    addTempBeforeUnloadBlocker();

    // CHEQUEO DEL CUE EN EL SERVIDOR
    checkCueExists(cue, excludeId)
      .then(exists => {
        if (typeof exists === 'undefined') {
          return;
        }
        if (exists) {
          showError('Ya existe una escuela con el mismo CUE. No se puede guardar.');
          submitButtons.forEach(b => b.disabled = false);
          removeTempBeforeUnloadBlocker();
          return;
        }
        removeTempBeforeUnloadBlocker();
        if (form) form.submit();
      })
      .catch(err => {
        // MOSTRAR ERRORES CLARO Y DEJAR DETALLES EN CONSOLA
        showError('No se pudo verificar el CUE en el servidor. Intentá nuevamente.');
        console.warn('check_cue error:', err);
        submitButtons.forEach(b => b.disabled = false);
        removeTempBeforeUnloadBlocker();
      });
  }

  // PREVIENE DOBLE ENVIO
  submitButtons.forEach(btn => {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      validateAndSubmit(ev.target);
    });
  });

  // ENTER / ENVIAR
  if (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      validateAndSubmit(ev.submitter || null);
    });
  }

});