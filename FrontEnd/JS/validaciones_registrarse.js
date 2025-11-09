// VALIDACIONES REGISTRARSE

document.addEventListener('DOMContentLoaded', () => {


  // MOSTRAR / LIMPIAR ERRORES
  function showFieldError(inputEl, message) {
    if (!inputEl) return;
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

  // VALIDACIONES BASICAS
  const isNotEmpty = v => typeof v === 'string' && v.trim() !== '';
  const validEmail = v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  const validDNIformat = v => /^\d{6,10}$/.test((v || '').replace(/\D/g, ''));

  // REFERENCIAS DOM
  const toStep2Btn = document.getElementById('toStep2');
  const step1Section = document.getElementById('step1');
  const step2Section = document.getElementById('step2');
  const form = document.getElementById('regForm');

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

  // LLAMADAS AL SERVIDOR
  const phpUrl = (window.GEOCODE_ENDPOINT && typeof window.GEOCODE_ENDPOINT === 'string')
    ? window.GEOCODE_ENDPOINT
    : (function () {
      const loc = window.location;
      const pathParts = loc.pathname.split('/');
      pathParts.pop();
      const folder = pathParts.join('/') + '/';
      return loc.origin + folder + 'registrarse.php';
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

  // CAMPOS DE USUARIOS 
  const emailInput = document.getElementById('usuarios_email');
  const passInput = document.getElementById('usuarios_clave');
  const passConf = document.getElementById('usuarios_clave_conf');

  [emailInput, passInput, passConf].forEach(i => {
    if (!i) return;
    i.addEventListener('input', () => clearFieldError(i));
    i.addEventListener('change', () => clearFieldError(i));
  });

  // LIMPIAR ERRORES DEL CONTENEDOR PADRA
  if (form) {
    form.addEventListener('input', function (e) {
      const t = e.target;
      if (t && (t.matches('input') || t.matches('select') || t.matches('textarea'))) {
        clearFieldError(t);
        const parentCont = t.closest('.blocks') || t.closest('.row') || t.closest('label');
        if (parentCont) clearFieldError(parentCont);
      }
    }, { passive: true });

    form.addEventListener('change', function (e) {
      const t = e.target;
      if (t && (t.matches('input') || t.matches('select') || t.matches('textarea'))) {
        clearFieldError(t);
        const parentCont = t.closest('.blocks') || t.closest('.row') || t.closest('label');
        if (parentCont) clearFieldError(parentCont);
      }
    });
  }

  // VALIDACION DOMICILIOS / TELEFONOS
  function validateDomicilios() {
    if (!domsContainer) return false;
    const blocks = domsContainer.querySelectorAll('.dom-block');
    if (!blocks || blocks.length === 0) {
      showFieldError(domsContainer, 'Agregá al menos un domicilio.');
      return false;
    }
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

  // VALIDAR INSTITUCIONAL
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
    clearFieldError(tipoSel);
    clearFieldError(instEscuela);
    clearFieldError(instFormacion);
    return true;
  }

  // CHEQUEAR DNI EN SERVIDOR
  async function checkDNIonServer(dniRaw) {
    const dni = (dniRaw || '').replace(/\D/g, '');
    if (!dni) return { ok: false, error: 'DNI vacío' };

    const url = phpUrl + '?action=check_dni&dni=' + encodeURIComponent(dni);
    console.log('check_dni ->', url);

    try {
      const resp = await fetch(url, { credentials: 'same-origin' });
      const text = await resp.text();
      let data;
      try { data = JSON.parse(text); }
      catch (e) {
        console.error('check_dni: respuesta no JSON:', text);
        return { ok: false, error: 'Respuesta inválida del servidor (ver consola)' };
      }
      if (!resp.ok) {
        console.error('check_dni HTTP error', resp.status, data);
        return { ok: false, error: 'Error servidor al verificar DNI' };
      }
      return { ok: true, data };
    } catch (err) {
      console.error('check_dni fetch error', err);
      return { ok: false, error: 'Error de conexión al verificar DNI' };
    }
  }

  // VALIDAR Y AVANZAR
  let checkingDNI = false;
  async function validateStep1_andMaybeAdvance() {
    if (checkingDNI) return;
    checkingDNI = true;
    toStep2Btn && (toStep2Btn.disabled = true);

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
      checkingDNI = false;
      toStep2Btn && (toStep2Btn.disabled = false);
      const fe = document.querySelector('.field-error');
      if (fe) {
        const prev = fe.previousElementSibling;
        if (prev && prev.focus) prev.focus();
      }
      return false;
    }

    const serverResult = await checkDNIonServer(dniRaw);
    if (!serverResult.ok) {
      const errMsg = serverResult.error || 'Error verificando DNI (ver consola).';
      if (window.Swal || window.SweetAlert2 || window.Swal2 || window.swal) {
        const S = window.Swal || window.SweetAlert2 || window.Swal2 || window.swal;
        S.fire({ icon: 'error', title: 'Error', text: errMsg });
      } else {
        alert(errMsg);
      }
      showFieldError(inpDNI, errMsg);
      checkingDNI = false;
      toStep2Btn && (toStep2Btn.disabled = false);
      return false;
    }

    const data = serverResult.data;

    // SI NO EXISTE LA PERSONA AVANZAMOS
    if (!data.exists_persona) {
      goToStep2();
      checkingDNI = false;
      toStep2Btn && (toStep2Btn.disabled = false);
      return true;
    }

    // EXISTE LA PERSONA
    if (data.has_usuario) {
      const msg = 'Esa persona ya tiene un usuario registrado. Si olvidaste la contraseña, recuperala desde Logeo.';
      if (window.Swal || window.SweetAlert2 || window.Swal2 || window.swal) {
        const S = window.Swal || window.SweetAlert2 || window.Swal2 || window.swal;
        S.fire({
          icon: 'info',
          title: 'Persona y usuario registrados',
          html: '<p style="text-align:left;margin:0;">' + msg + '</p>',
          confirmButtonText: 'Ir a Logeo'
        }).then(() => {
          window.location.href = 'logeo.php';
        });
      } else {
        alert(msg);
        window.location.href = 'logeo.php';
      }
      showFieldError(inpDNI, msg);
      checkingDNI = false;
      toStep2Btn && (toStep2Btn.disabled = false);
      return false;
    }

    // EXISTE PERSONA PERO SIN USUARIO
    const persona = data.persona || null;
    const message = 'El DNI ya existe en la base (persona registrada sin usuario). ¿Deseás crearle un usuario y contraseña ahora?';

    if (window.Swal || window.SweetAlert2 || window.Swal2 || window.swal) {
      const S = window.Swal || window.SweetAlert2 || window.Swal2 || window.swal;

      S.fire({
        title: 'Persona sin usuario',
        html: '<p style="text-align:left;margin:0 0 8px 0;">' + message + '</p>' +
          (persona ? ('<div style="text-align:left;font-size:0.95rem;color:#444;">' +
            '<strong>Nombre:</strong> ' + (persona.nombre || '-') + '<br>' +
            '<strong>Apellido:</strong> ' + (persona.apellido || '-') + (persona.fechnac ? ('<br><strong>F.Nac:</strong> ' + persona.fechnac) : '') +
            '</div>') : ''),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, crear usuario',
        cancelButtonText: 'No, llevarme a Logeo',
        reverseButtons: true,
        focusCancel: false,
      }).then((result) => {
        if (result.isConfirmed) {

          if (persona) {
            if (inpApellido) inpApellido.value = persona.apellido || inpApellido.value;
            if (inpNombre) inpNombre.value = persona.nombre || inpNombre.value;
            if (inpFecha) inpFecha.value = persona.fechnac || inpFecha.value;
            if (selSexo && persona.sexo) {
              [...selSexo.options].forEach(o => {
                if (o.value && persona.sexo && o.value.toLowerCase() === persona.sexo.toLowerCase()) o.selected = true;
              });
            }
            let hid = document.querySelector('input[name="existing_persona_id"]');
            if (!hid) {
              hid = document.createElement('input');
              hid.type = 'hidden';
              hid.name = 'existing_persona_id';
              document.getElementById('regForm').appendChild(hid);
            }
            hid.value = persona.id || '';
          }
          goToStep2();
        } else {
          window.location.href = 'logeo.php';
        }
        checkingDNI = false;
        toStep2Btn && (toStep2Btn.disabled = false);
      });
    } else {
      const r = confirm(message + '\n\nAceptar = crear usuario ahora / Cancelar = ir al logeo');
      if (r) {
        if (persona) {
          if (inpApellido) inpApellido.value = persona.apellido || inpApellido.value;
          if (inpNombre) inpNombre.value = persona.nombre || inpNombre.value;
          if (inpFecha) inpFecha.value = persona.fechnac || inpFecha.value;
          if (selSexo && persona.sexo) {
            [...selSexo.options].forEach(o => {
              if (o.value && persona.sexo && o.value.toLowerCase() === persona.sexo.toLowerCase()) o.selected = true;
            });
          }
          let hid = document.querySelector('input[name="existing_persona_id"]');
          if (!hid) {
            hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = 'existing_persona_id';
            document.getElementById('regForm').appendChild(hid);
          }
          hid.value = persona.id || '';
        }
        goToStep2();
      } else {
        window.location.href = 'logeo.php';
      }
      checkingDNI = false;
      toStep2Btn && (toStep2Btn.disabled = false);
    }

    return false;
  }

  // EXPORTAMOS LA FUNCION
  window.validateStep1_andMaybeAdvance = validateStep1_andMaybeAdvance;

  // MOSTRAMOS PASO 2
  function goToStep2() {
    if (step1Section) step1Section.style.display = 'none';
    if (step2Section) step2Section.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // VALIDACIONES PASO 2
  if (form) {
    form.addEventListener('submit', (e) => {
      const emailInput = document.getElementById('usuarios_email');
      const passInput = document.getElementById('usuarios_clave');
      const passConf = document.getElementById('usuarios_clave_conf');

      [emailInput, passInput, passConf].forEach(clearFieldError);

      let hasErr = false;
      if (!emailInput || !validEmail(emailInput.value || '')) {
        showFieldError(emailInput, 'Ingresá un email válido.');
        hasErr = true;
      }
      const pwd = (passInput && passInput.value) || '';
      const pwdc = (passConf && passConf.value) || '';
      if (pwd.length < 6) {
        showFieldError(passInput, 'La contraseña debe tener al menos 6 caracteres.');
        hasErr = true;
      }
      if (pwd !== pwdc) {
        showFieldError(passConf, 'Las contraseñas no coinciden.');
        hasErr = true;
      }

      if (hasErr) {
        e.preventDefault();
        const fe = document.querySelector('.field-error');
        if (fe) {
          const prev = fe.previousElementSibling;
          if (prev && prev.focus) prev.focus();
        }
        return false;
      }
      return true;
    });
  }

});