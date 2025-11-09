// VALIDACIONES PERSONAS

document.addEventListener('DOMContentLoaded', () => {

  // MENSAJE DE ERROR
  function showFieldError(inputEl, message) {
    if (!inputEl) return;

    // MARCAR INPUT
    inputEl.classList.add('error');
    let referenceNode = inputEl;
    if (inputEl.parentElement && inputEl.parentElement.tagName.toLowerCase() === 'label') {
      referenceNode = inputEl.parentElement;
    }

    let next = referenceNode.nextElementSibling;
    if (next && next.classList && next.classList.contains('field-error')) {
      next.textContent = message;
    } else {
      const err = document.createElement('div');
      err.className = 'field-error';
      err.textContent = message;
      referenceNode.parentNode.insertBefore(err, referenceNode.nextSibling);
      next = err;
    }

    if (!next.id) next.id = 'err_' + Math.random().toString(36).slice(2, 9);
    inputEl.setAttribute('aria-describedby', next.id);
    inputEl.setAttribute('aria-invalid', 'true');
  }

  // LIMPIAR ERRORES
  function clearFieldError(inputEl) {
    if (!inputEl) return;
    inputEl.classList.remove('error');
    inputEl.removeAttribute('aria-invalid');

    const described = inputEl.getAttribute('aria-describedby');
    if (described) {
      const node = document.getElementById(described);
      if (node && node.classList && node.classList.contains('field-error')) node.remove();
      inputEl.removeAttribute('aria-describedby');
      return;
    }

    const next = inputEl.nextElementSibling;
    if (next && next.classList && next.classList.contains('field-error')) next.remove();
    else if (inputEl.parentElement && inputEl.parentElement.tagName.toLowerCase() === 'label') {
      const afterLabel = inputEl.parentElement.nextElementSibling;
      if (afterLabel && afterLabel.classList && afterLabel.classList.contains('field-error')) afterLabel.remove();
    }
  }

  // VALIDACIONES SIMPLES
  const isNotEmpty = v => typeof v === 'string' && v.trim() !== '';
  const validDNIformat = v => /^\d{6,10}$/.test((v || '').replace(/\D/g, ''));

  // REFERENCIAS DOM
  const form = document.getElementById('personForm');
  const inpApellido = document.getElementById('personas_apellido');
  const inpNombre = document.getElementById('personas_nombre');
  const inpDNI = document.getElementById('personas_dni');
  const inpFecha = document.getElementById('personas_fechnac');
  const selSexo = document.getElementById('personas_sexo');
  const domsContainer = document.getElementById('doms');
  const telsContainer = document.getElementById('tels');
  const tipoSel = document.getElementById('inst_tipo');
  const instEscuela = document.getElementById('inst_escuela');
  const instFormacion = document.getElementById('inst_formacion');

  // ENDPOINTS CHEQUEA DNI
  const phpUrl = (window.PERSONAS_ENDPOINT && typeof window.PERSONAS_ENDPOINT === 'string')
    ? window.PERSONAS_ENDPOINT
    : (function () {
      const loc = window.location;
      const path = loc.pathname.split('/').pop();
      return loc.origin + '/' + path;
    })();

  // LIMPIAR ERRORES AL TIPEAR
  [inpApellido, inpNombre, inpDNI, inpFecha, selSexo].forEach(el => {
    if (!el) return;
    el.addEventListener('input', () => clearFieldError(el));
    el.addEventListener('change', () => clearFieldError(el));
  });

  function attachDynamicClears(root) {
    if (!root) return;
    root.querySelectorAll('input, select, textarea').forEach(i => {
      i.addEventListener('input', () => clearFieldError(i));
      i.addEventListener('change', () => clearFieldError(i));
    });
  }
  attachDynamicClears(domsContainer);
  attachDynamicClears(telsContainer);

  // VALIDACIONES DE BLOQUES DINAMICOS
  function validateDomicilios() {
    if (!domsContainer) return false;
    const blocks = domsContainer.querySelectorAll('.dom-block');
    if (!blocks || blocks.length === 0) {
      showFieldError(domsContainer, 'Agregá al menos un domicilio.');
      return false;
    }

    // COMPROBAR QUE AL MENOS UNA CALLE TENGA CONTENIDO
    for (const b of blocks) {
      const calle = b.querySelector('input[name="domicilios_calle[]"]');
      if (calle && isNotEmpty(calle.value || '')) {
        clearFieldError(domsContainer);
        clearFieldError(calle);
        return true;
      }
    }
    showFieldError(domsContainer, 'Completá la calle y número de al menos un domicilio.');
    return false;
  }

  function validateTelefonos() {
    if (!telsContainer) return false;
    const blocks = telsContainer.querySelectorAll('.tel-block');
    if (!blocks || blocks.length === 0) {
      showFieldError(telsContainer, 'Agregá al menos un teléfono.');
      return false;
    }
    for (const b of blocks) {
      const num = b.querySelector('input[name="telefonos_numero[]"]');
      if (num && isNotEmpty(num.value || '')) {
        clearFieldError(telsContainer);
        clearFieldError(num);
        return true;
      }
    }
    showFieldError(telsContainer, 'Completá el número de al menos un teléfono.');
    return false;
  }

  function validateInstitucional() {
    if (!tipoSel) return true;
    const tipo = (tipoSel.value || '').trim();
    if (!tipo) {
      showFieldError(tipoSel, 'Seleccioná el tipo institucional.');
      return false;
    }
    if (tipo === 'Director') {
      if (!instEscuela || !isNotEmpty(instEscuela.value || '')) {
        showFieldError(instEscuela, 'Seleccioná la escuela para Director.');
        return false;
      }
    } else if (tipo === 'Docente') {
      if (!instEscuela || !isNotEmpty(instEscuela.value || '')) {
        showFieldError(instEscuela, 'Seleccioná la escuela para Docente.');
        return false;
      }
      if (!instFormacion || !isNotEmpty(instFormacion.value || '')) {
        showFieldError(instFormacion, 'Seleccioná la formación profesional para Docente.');
        return false;
      }
    }

    // LIMPIAR ERRORES PREVIOS
    clearFieldError(tipoSel);
    clearFieldError(instEscuela);
    clearFieldError(instFormacion);
    return true;
  }

  // CHEQUEAR DNI
  async function checkDNIonServer(dniRaw) {
    const dni = (dniRaw || '').replace(/\D/g, '');
    if (!dni) return { ok: false, error: 'DNI vacío' };

    const base = (window.CHECK_DNI_ENDPOINT && typeof window.CHECK_DNI_ENDPOINT === 'string')
      ? window.CHECK_DNI_ENDPOINT
      : (function () {
        var loc = window.location;
        var pathParts = loc.pathname.split('/');
        pathParts.pop();
        return loc.origin + pathParts.join('/') + '/registrarse.php?action=check_dni';
      })();

    const url = base + '&dni=' + encodeURIComponent(dni);
    console.log('check_dni ->', url);

    try {
      const resp = await fetch(url, { credentials: 'same-origin' });
      const text = await resp.text();
      const contentType = (resp.headers.get('content-type') || '').toLowerCase();

// DEVOLVER MENSJA PARA MOSTAR
      if (!resp.ok) {
        console.error('check_dni HTTP error', resp.status, text);
        return { ok: false, error: 'Error servidor al verificar DNI (HTTP ' + resp.status + ')' };
      }

      if (contentType.indexOf('application/json') === -1) {
        console.error('check_dni: respuesta no JSON:', text);
        return { ok: false, error: 'Respuesta inválida del servidor (no JSON). Verificá la ruta del endpoint.' };
      }

      let data;
      try { data = JSON.parse(text); }
      catch (e) {
        console.error('check_dni: JSON parse error', e, text);
        return { ok: false, error: 'Respuesta JSON inválida del servidor.' };
      }

      return { ok: true, data };
    } catch (err) {
      console.error('check_dni fetch error', err);
      return { ok: false, error: 'Error de conexión al verificar DNI' };
    }
  }

  // SABER SI SE ESTA AGREGANDO / EDITANDO
  const urlParams = new URLSearchParams(window.location.search);
  const urlAction = (urlParams.get('action') || '').toLowerCase();
  const urlId = parseInt(urlParams.get('id') || '0', 10) || 0;
  const isEdit = (urlAction === 'edit' && urlId > 0);
  const isAdd = (urlAction === 'add' || urlAction === '');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // LIMPIAR ERRORES
      [inpApellido, inpNombre, inpDNI, inpFecha, selSexo].forEach(clearFieldError);
      clearFieldError(domsContainer);
      clearFieldError(telsContainer);
      if (tipoSel) clearFieldError(tipoSel);
      if (instEscuela) clearFieldError(instEscuela);
      if (instFormacion) clearFieldError(instFormacion);

      let ok = true;
      if (!isNotEmpty(inpApellido.value || '')) { showFieldError(inpApellido, 'Completá el apellido.'); ok = false; }
      if (!isNotEmpty(inpNombre.value || '')) { showFieldError(inpNombre, 'Completá el nombre.'); ok = false; }

      const dniRaw = (inpDNI.value || '').replace(/\s+/g, '');
      if (!isNotEmpty(dniRaw) || !validDNIformat(dniRaw)) { showFieldError(inpDNI, 'Ingresá un DNI válido (6-10 números).'); ok = false; }

      if (!isNotEmpty(inpFecha.value || '')) { showFieldError(inpFecha, 'Completá la fecha de nacimiento.'); ok = false; }
      if (!isNotEmpty(selSexo.value || '')) { showFieldError(selSexo, 'Seleccioná el sexo.'); ok = false; }

      const domsOk = validateDomicilios();
      const telsOk = validateTelefonos();
      if (!domsOk || !telsOk) ok = false;

      const instOk = validateInstitucional();
      if (!instOk) ok = false;

      if (!ok) {
        const fe = document.querySelector('.field-error');
        if (fe) {
          const prev = fe.previousElementSibling;
          if (prev && typeof prev.focus === 'function') prev.focus();
        }
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: 'Errores en el formulario', text: 'Revisá los campos marcados.' });
        }
        return false;
      }

      // VALIDACIONE EN EL SERVIDOR
      const server = await checkDNIonServer(dniRaw);
      if (!server.ok) {
        const msg = server.error || 'Error verificando DNI en el servidor.';
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: 'Error', text: msg });
        } else {
          alert(msg);
        }
        showFieldError(inpDNI, msg);
        return false;
      }

      const data = server.data || {};
      if (data.exists_persona) {
        const foundId = data.persona && data.persona.id ? parseInt(data.persona.id, 10) : 0;
        if (isEdit && foundId === urlId) {
        } else {
          const msg = 'El DNI ya está cargado en otra persona en la base de datos.';
          if (window.Swal) {
            Swal.fire({ icon: 'warning', title: 'DNI duplicado', text: msg });
          } else {
            alert(msg);
          }
          showFieldError(inpDNI, msg);
          return false;
        }
      }

      form.submit();
      return true;
    });
  }

});