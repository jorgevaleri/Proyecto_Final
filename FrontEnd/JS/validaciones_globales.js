// VALIDACIONES GLOBALES

// FUNCION ANONIMA 
(function () {

  // ESPACIO PÚBLICO
  window.VALIDACIONES = window.VALIDACIONES || {};

  // CONVIERTE CUALQUIER VALOR A NÚMERO EVITA NaN
  function aNumeroSeguro(valor) {
    const n = Number(valor);
    return Number.isFinite(n) ? n : 0;
  }


  // MARCA VISUALMENTE UN ELEMENTO COMO INVALIDO
  function marcarError(el) {
    if (!el) return;
    el.classList.add('error');
    el.setAttribute('aria-invalid', 'true');
  }

  // QUITA LA MARCA DE ERROR
  function limpiarError(el) {
    if (!el) return;
    el.classList.remove('error');
    el.removeAttribute('aria-invalid');
  }

  // MARCA INPUTS / SELECTS VACIOS AL PERDER EL FOCO
  function marcarVacioAlPerderFoco(e) {
    var t = e.target;
    if (!t) return;
    var tag = t.tagName;
    if (tag === 'INPUT' || tag === 'SELECT') {
      var valido = true;
      if (tag === 'INPUT') valido = t.value !== '';
      else if (tag === 'SELECT') valido = t.selectedIndex > 0 && t.value !== '';
      if (!valido) marcarError(t); else limpiarError(t);
    }
  }

  // VALIDACIONES PARA INDEX / CALCULADORA
  // NO SE MUESTRAN VALIDACIONES HASTA LA PRIMER INTERACCION DEL USUARIO
  let mostrarValidacionesAsistencia = false;

  // MENSAJE ASISTENCIA
  let _msgAsistenciaEl = null;
  let _observerAsistencia = null;

  // CREA EL CONTENEDOR PARA EL MENSAJE (SI NO EXISTE)
  function _crearMensajeAsistencia(idMsg = 'validation_message') {
    if (_msgAsistenciaEl) return _msgAsistenciaEl;
    let el = document.getElementById(idMsg);
    if (!el) {
      el = document.createElement('div');
      el.id = idMsg;
      el.style.display = 'none';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
    }
    _msgAsistenciaEl = el;
    return el;
  }

  function _ubicarMensajeAsistencia(idMsg = 'validation_message') {
    const el = _crearMensajeAsistencia(idMsg);
    const main = document.querySelector('main.cuerpo');
    if (!main) return false;

    const tablas = main.querySelector('.tablas-container');
    const botones = main.querySelector('.botones');

    if (el.parentNode === main && (!tablas || el.nextElementSibling === tablas)) return true;

    if (tablas) { main.insertBefore(el, tablas); return true; }

    if (botones) {
      if (botones.nextSibling) botones.parentNode.insertBefore(el, botones.nextSibling);
      else main.appendChild(el);
      return true;
    }

    main.appendChild(el);
    return true;
  }

  function asegurarMensajeAsistencia(idMsg = 'validation_message') {
    const el = _crearMensajeAsistencia(idMsg);

    if (_ubicarMensajeAsistencia(idMsg)) {
      if (_observerAsistencia) { _observerAsistencia.disconnect(); _observerAsistencia = null; }
      return el;
    }

    if (!_observerAsistencia) {
      _observerAsistencia = new MutationObserver((mutations, obs) => {
        if (_ubicarMensajeAsistencia(idMsg)) {
          obs.disconnect();
          _observerAsistencia = null;
        }
      });
      _observerAsistencia.observe(document.body, { childList: true, subtree: true });
    }

    return el;
  }

  // ACTIVA VALIDACIONES DESPUES DE LA PRIMER INTERACCION
  function initTriggerAsistencia(idTabla = 'id_tabla1') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) return;
    const onFirst = () => {
      if (mostrarValidacionesAsistencia) return;
      mostrarValidacionesAsistencia = true;
      asegurarMensajeAsistencia();
      validarPaginaAsistencia(idTabla, 'id_dias_habiles');
    };
    tabla.addEventListener('input', onFirst, { passive: true });
    tabla.addEventListener('change', onFirst, { passive: true });
  }

  document.addEventListener('DOMContentLoaded', () => {
    asegurarMensajeAsistencia();
    initTriggerAsistencia('id_tabla1');
  });

  // SINCRONIZA DIAS HABILES DE LA PRIMER FILA
  function sincronizarDiasDesdePrimeraFila(idTabla = 'id_tabla1', idDias = 'id_dias_habiles') {
    const tabla = document.getElementById(idTabla);
    const diasEl = document.getElementById(idDias);
    if (!tabla || !diasEl) return;
    if (tabla.rows.length <= 1) { diasEl.value = ''; return; }
    const primera = tabla.rows[1];
    if (!primera) { diasEl.value = ''; return; }
    const inputs = primera.querySelectorAll('input[type="number"]');
    const a = inputs[0] ? inputs[0].value : '';
    const i = inputs[1] ? inputs[1].value : '';
    if ((a === '' || a === null) && (i === '' || i === null)) { diasEl.value = ''; return; }
    diasEl.value = aNumeroSeguro(a) + aNumeroSeguro(i);
  }

  // VALIDAR FILAS CON LOS DIAS HABILES
  function validarFilasContraDias(idTabla = 'id_tabla1', idDias = 'id_dias_habiles') {
    const t = document.getElementById(idTabla);
    if (!t) return { ok: false, reason: 'tabla_no_encontrada', detalles: [] };
    const filas = Array.from(t.querySelectorAll('tr')).slice(1);
    const diasEl = document.getElementById(idDias);
    const dias = diasEl ? aNumeroSeguro(diasEl.value) : 0;
    if (!diasEl || dias <= 0) return { ok: false, reason: 'dias_no_definidos', detalles: [] };

    const invalidas = [];
    filas.forEach((fila, idx) => {
      const selectSexo = fila.querySelector('select');
      const inputs = fila.querySelectorAll('input[type="number"]');
      const asi = aNumeroSeguro(inputs[0] ? inputs[0].value : 0);
      const ina = aNumeroSeguro(inputs[1] ? inputs[1].value : 0);

      if (!selectSexo || !selectSexo.value || selectSexo.value === '') {
        invalidas.push({ fila: idx + 1, tipo: 'sexo_vacio', asi, ina, suma: asi + ina });
        return;
      }
      if ((asi + ina) !== dias) invalidas.push({ fila: idx + 1, tipo: 'suma_incorrecta', asi, ina, suma: asi + ina });
    });

    return { ok: invalidas.length === 0, reason: invalidas.length ? 'filas_invalidas' : null, detalles: invalidas };
  }

  // VALIDACION DE LA PAGINA
  function validarPaginaAsistencia(idTabla = 'id_tabla1', idDias = 'id_dias_habiles', idMsg = 'validation_message') {
    sincronizarDiasDesdePrimeraFila(idTabla, idDias);
    const resultado = validarFilasContraDias(idTabla, idDias);
    const msgEl = asegurarMensajeAsistencia(idMsg);

    if (!mostrarValidacionesAsistencia) {
      const todas = document.querySelectorAll(`#${idTabla} tr:not(:first-child)`);
      todas.forEach(r => {
        r.classList.remove('invalid-row');
        const ins = r.querySelectorAll('input[type="number"], select');
        ins.forEach(i => limpiarError(i));
      });
      if (msgEl) { msgEl.style.display = 'none'; msgEl.className = ''; msgEl.textContent = ''; }
      return { ok: true, suppressed: true };
    }

    // LIMPIAR ERRORES
    const todas = document.querySelectorAll(`#${idTabla} tr:not(:first-child)`);
    todas.forEach(r => {
      r.classList.remove('invalid-row');
      const ins = r.querySelectorAll('input[type="number"], select');
      ins.forEach(i => limpiarError(i));
    });

    // MOSTRAR / ACTUALIZAR VISUALMENTE
    if (!resultado.ok) {
      if (resultado.reason === 'dias_no_definidos') {
        if (msgEl) { msgEl.className = 'error'; msgEl.textContent = 'ERROR: Días hábiles no definidos. Complete la PRIMERA fila.'; msgEl.style.display = 'block'; }
        todas.forEach(r => r.classList.add('invalid-row'));
        const primera = document.querySelector(`#${idTabla} tr:not(:first-child)`);
        if (primera) {
          const ins = primera.querySelectorAll('input[type="number"], select');
          ins.forEach(i => marcarError(i));
        }
        return { ok: false, resultado };
      } else {
        const countSexo = resultado.detalles.filter(d => d.tipo === 'sexo_vacio').length;
        const countSuma = resultado.detalles.filter(d => d.tipo === 'suma_incorrecta').length;
        let mensaje = `ERROR: ${resultado.detalles.length} fila(s) inválida(s).`;
        if (countSexo) mensaje += ` ${countSexo} sin sexo seleccionado.`;
        if (countSuma) mensaje += ` ${countSuma} con asistencia+inasistencia distinta a los días.`;
        mensaje += ' Revise las filas marcadas.';
        if (msgEl) { msgEl.className = 'error'; msgEl.textContent = mensaje; msgEl.style.display = 'block'; }

        todas.forEach((r, idx) => {
          const existe = resultado.detalles.find(d => d.fila === idx + 1);
          if (existe) {
            r.classList.add('invalid-row');
            const ins = r.querySelectorAll('input[type="number"], select');
            ins.forEach(i => marcarError(i));
          } else {
            r.classList.remove('invalid-row');
          }
        });

        return { ok: false, resultado };
      }
    } else {
      if (msgEl) {
        msgEl.className = 'success';
        msgEl.textContent = 'Validación OK — todas las filas coinciden con los días hábiles.';
        msgEl.style.display = 'block';
        setTimeout(() => { const e = document.getElementById(idMsg); if (e && e.className === 'success') e.style.display = 'none'; }, 1500);
      }
      todas.forEach(r => r.classList.remove('invalid-row'));
      return { ok: true };
    }
  }

  // BLOQUEA LOS CALCULOS SI HAY ERRORES
  function bloquearCalculosSiError(idTabla, idDias, callbackCalculo) {
    const res = validarPaginaAsistencia(idTabla, idDias);
    if (res && res.suppressed) {
      if (typeof callbackCalculo === 'function') callbackCalculo();
      return true;
    }
    if (!res.ok) return false;
    if (typeof callbackCalculo === 'function') callbackCalculo();
    return true;
  }

  const _internalsAsistencia = {
    enable: () => { mostrarValidacionesAsistencia = true; },
    disable: () => {
      mostrarValidacionesAsistencia = false;
      const el = document.getElementById('validation_message'); if (el) { el.style.display = 'none'; el.className = ''; el.textContent = ''; }
      const allRows = document.querySelectorAll(`#id_tabla1 tr:not(:first-child)`);
      allRows.forEach(r => { r.classList.remove('invalid-row'); r.querySelectorAll('input[type="number"], select').forEach(i => limpiarError(i)); });
    },
    isEnabled: () => mostrarValidacionesAsistencia
  };

  // LOGEO
  function asegurarMensajeLogin() {
    let el = document.getElementById('login_validation_message');
    if (!el) {
      el = document.createElement('div');
      el.id = 'login_validation_message';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      const form = document.getElementById('form1');
      if (form) {
        const boton = form.querySelector('.boton');
        if (boton && boton.parentNode) boton.parentNode.insertBefore(el, boton);
        else form.appendChild(el);
      } else {
        document.body.appendChild(el);
      }
    }
    return el;
  }

  function mostrarValidacionLogin(mensajes, servidorTexto) {
    const el = asegurarMensajeLogin();
    if (!el) return;
    if (servidorTexto && String(servidorTexto).trim()) {
      el.innerHTML = '<div class="server-error">' + String(servidorTexto) + '</div>';
    } else if (Array.isArray(mensajes) && mensajes.length) {
      el.innerHTML = '<ul class="login-errs">' + mensajes.map(m => '<li>' + String(m) + '</li>').join('') + '</ul>';
    } else {
      el.innerHTML = '';
      el.style.display = 'none';
      el.className = '';
      return;
    }
    el.className = 'error';
    el.style.display = 'block';
    try { el.setAttribute('tabindex', '-1'); el.focus(); } catch (e) { }
  }

  function limpiarValidacionLogin() {
    const el = document.getElementById('login_validation_message');
    if (!el) return;
    el.style.display = 'none';
    el.className = '';
    el.innerHTML = '';
  }

  function validarFormularioLogin(formEl) {
    const form = formEl || document.getElementById('form1');
    const mensajes = [];
    if (!form) return { ok: true, mensajes };

    const correo = form.querySelector('#usuarios_email');
    const clave = form.querySelector('#usuarios_clave');

    if (correo) limpiarError(correo);
    if (clave) limpiarError(clave);
    limpiarValidacionLogin();

    const valorCorreo = correo ? (correo.value || '').trim() : '';
    if (!valorCorreo) {
      mensajes.push('El correo es obligatorio.');
      if (correo) marcarError(correo);
    } else {
      let correoValido = false;
      if (window.VALIDACIONES && typeof window.VALIDACIONES.validarCorreo === 'function') {
        correoValido = window.VALIDACIONES.validarCorreo(correo);
      } else {
        correoValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valorCorreo);
      }
      if (!correoValido) {
        mensajes.push('Formato de correo inválido.');
        if (correo) marcarError(correo);
      }
    }

    const valorClave = clave ? (clave.value || '') : '';
    if (!valorClave || valorClave.trim() === '') {
      mensajes.push('La contraseña es obligatoria.');
      if (clave) marcarError(clave);
    }

    if (mensajes.length) {
      mostrarValidacionLogin(mensajes, null);
      return { ok: false, mensajes };
    }

    limpiarValidacionLogin();
    return { ok: true, mensajes: [] };
  }

  function validarCorreo(el) {
    if (!el) return false;
    const v = (el.value || '').trim();
    if (!v) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  // OLVIDE LA CONTRASEÑA
  function asegurarMensajeReset() {
    let el = document.getElementById('reset_validation_message');
    if (!el) {
      el = document.createElement('div');
      el.id = 'reset_validation_message';
      el.style.display = 'none';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      const form = document.getElementById('reset_form') || document.getElementById('form1');
      if (form) {
        const boton = form.querySelector('.boton');
        if (boton && boton.parentNode) boton.parentNode.insertBefore(el, boton);
        else form.appendChild(el);
      } else {
        document.body.appendChild(el);
      }
    }
    return el;
  }

  function mostrarValidacionReset(mensajes, servidorTexto) {
    const el = asegurarMensajeReset();
    if (!el) return;
    if (servidorTexto && String(servidorTexto).trim()) {
      el.innerHTML = '<div class="server-error">' + String(servidorTexto) + '</div>';
    } else if (Array.isArray(mensajes) && mensajes.length) {
      el.innerHTML = '<ul class="login-errs">' + mensajes.map(m => '<li>' + String(m) + '</li>').join('') + '</ul>';
    } else {
      el.innerHTML = '';
      el.style.display = 'none';
      el.className = '';
      return;
    }
    el.className = 'error';
    el.style.display = 'block';
    try { el.setAttribute('tabindex', '-1'); el.focus(); } catch (e) { }
  }

  function limpiarValidacionReset() {
    const el = document.getElementById('reset_validation_message');
    if (!el) return;
    el.style.display = 'none';
    el.className = '';
    el.innerHTML = '';
  }

  function validarFormularioReset(formEl) {
    const form = formEl || document.getElementById('reset_form');
    const mensajes = [];
    if (!form) return { ok: true, mensajes };

    const email = form.querySelector('#reset_email') || form.querySelector('input[type="email"][name="email"]');
    if (!email) return { ok: true, mensajes };

    if (typeof window.VALIDACIONES !== 'undefined' && typeof window.VALIDACIONES.limpiarError === 'function') {
      window.VALIDACIONES.limpiarError(email);
    } else {
      email.classList.remove('error');
    }
    limpiarValidacionReset();

    const val = (email.value || '').trim();
    if (!val) {
      mensajes.push('El correo es obligatorio.');
      if (typeof window.VALIDACIONES !== 'undefined' && typeof window.VALIDACIONES.marcarError === 'function') {
        window.VALIDACIONES.marcarError(email);
      } else {
        email.classList.add('error');
      }
    } else {
      let esValido = true;
      if (typeof window.VALIDACIONES !== 'undefined' && typeof window.VALIDACIONES.validarCorreo === 'function') {
        esValido = window.VALIDACIONES.validarCorreo(email);
      } else {
        esValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      }
      if (!esValido) {
        mensajes.push('Formato de correo inválido.');
        if (typeof window.VALIDACIONES !== 'undefined' && typeof window.VALIDACIONES.marcarError === 'function') {
          window.VALIDACIONES.marcarError(email);
        } else {
          email.classList.add('error');
        }
      }
    }

    if (mensajes.length) {
      mostrarValidacionReset(mensajes, null);
      return { ok: false, mensajes };
    }

    limpiarValidacionReset();
    return { ok: true, mensajes: [] };
  }

  // EXPORTAR API PUBLICA
  // UTILIDADES COMUNES
  window.VALIDACIONES.aNumeroSeguro = aNumeroSeguro;
  window.VALIDACIONES.marcarError = marcarError;
  window.VALIDACIONES.limpiarError = limpiarError;
  window.VALIDACIONES.marcarVacioAlPerderFoco = marcarVacioAlPerderFoco;

  // INDEX / CALCULADORA
  window.VALIDACIONES.asegurarMensajeAsistencia = asegurarMensajeAsistencia;
  window.VALIDACIONES.iniciarTriggerAsistencia = initTriggerAsistencia;
  window.VALIDACIONES.sincronizarDiasDesdePrimeraFila = sincronizarDiasDesdePrimeraFila;
  window.VALIDACIONES.validarFilasContraDias = validarFilasContraDias;
  window.VALIDACIONES.validarPaginaAsistencia = validarPaginaAsistencia;
  window.VALIDACIONES.bloquearCalculosSiError = bloquearCalculosSiError;
  window.VALIDACIONES._internals = _internalsAsistencia;

  // LOGEO
  window.VALIDACIONES.asegurarMensajeLogin = asegurarMensajeLogin;
  window.VALIDACIONES.mostrarValidacionLogin = mostrarValidacionLogin;
  window.VALIDACIONES.limpiarValidacionLogin = limpiarValidacionLogin;
  window.VALIDACIONES.validarFormularioLogin = validarFormularioLogin;
  window.VALIDACIONES.validarCorreo = validarCorreo;

  // OLVIDE CONTRASEÑA
  window.VALIDACIONES.asegurarMensajeReset = asegurarMensajeReset;
  window.VALIDACIONES.mostrarValidacionReset = mostrarValidacionReset;
  window.VALIDACIONES.limpiarValidacionReset = limpiarValidacionReset;
  window.VALIDACIONES.validarFormularioReset = validarFormularioReset;

  // ACTIVA VISUALIZACION Y EJECUTA VALIDACION
  window.VALIDACIONES.validarTodo = function (idTabla = 'id_tabla1', idDias = 'id_dias_habiles') {
    if (window.VALIDACIONES._internals && typeof window.VALIDACIONES._internals.enable === 'function') {
      window.VALIDACIONES._internals.enable();
    }
    const r = validarPaginaAsistencia(idTabla, idDias);
    return !!(r && r.ok);
  };

})();

// REGISTROS
function asegurarMensajeRegistro() {
  let el = document.getElementById('registro_validation_message');
  if (!el) {
    el = document.createElement('div');
    el.id = 'registro_validation_message';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    const form = document.getElementById('regForm');
    if (form) {
      const firstSection = form.querySelector('section');
      if (firstSection && firstSection.parentNode) firstSection.parentNode.insertBefore(el, firstSection);
      else form.insertBefore(el, form.firstChild);
    } else {
      document.body.insertBefore(el, document.body.firstChild);
    }
  }
  return el;
}

function mostrarValidacionRegistro(mensajes) {
  const el = asegurarMensajeRegistro();
  if (!el) return;
  if (Array.isArray(mensajes) && mensajes.length) {
    el.innerHTML = '<ul class="login-errs">' + mensajes.map(m => '<li>' + String(m) + '</li>').join('') + '</ul>';
    el.className = 'error';
    el.style.display = 'block';
    try { el.setAttribute('tabindex', '-1'); el.focus(); } catch (e) { }
  } else {
    el.innerHTML = '';
    el.style.display = 'none';
    el.className = '';
  }
}

function validarFormularioRegistro(formEl) {
  const form = formEl || document.getElementById('regForm');
  const mensajes = [];
  if (!form) return { ok: true, mensajes };

  // LIMPIAR MARCAS PREVIAS
  const allInputs = form.querySelectorAll('input, select, textarea');
  allInputs.forEach(i => limpiarError(i));
  mostrarValidacionRegistro([]);

  // CAMPOS PERSONA
  const ape = form.querySelector('#personas_apellido');
  const nom = form.querySelector('#personas_nombre');
  const dni = form.querySelector('#personas_dni');
  if (!ape || !nom || !dni) {
    mensajes.push('Campos personales incompletos.');
  } else {
    if (!ape.value.trim()) { mensajes.push('Apellido requerido.'); marcarError(ape); }
    if (!nom.value.trim()) { mensajes.push('Nombre requerido.'); marcarError(nom); }
    if (!/^\d{6,10}$/.test(dni.value.trim())) { mensajes.push('DNI inválido (solo números, 6-10 dígitos).'); marcarError(dni); }
  }

  // DOMICILIOS
  const doms = form.querySelectorAll('input[name="domicilios_calle[]"]');
  if (!doms || doms.length === 0) {
    mensajes.push('Agregá al menos un domicilio.');
  } else {
    let anyValidDom = false;
    doms.forEach((dEl) => {
      if (dEl.value && dEl.value.trim() !== '') anyValidDom = true;
    });
    if (!anyValidDom) {
      mensajes.push('Completá al menos una dirección válida.');
      doms.forEach(dEl => marcarError(dEl));
    }
  }

  // TELEFONOS
  const tels = form.querySelectorAll('input[name="telefonos_numero[]"]');
  if (!tels || tels.length === 0) {
    mensajes.push('Agregá al menos un teléfono.');
  } else {
    let anyValidTel = false;
    const telRegex = /^\d{7,15}$/;
    tels.forEach((tEl) => {
      const v = (tEl.value || '').trim();
      if (v && telRegex.test(v)) anyValidTel = true;
      else marcarError(tEl);
    });
    if (!anyValidTel) mensajes.push('Completá al menos un teléfono válido (7-15 dígitos).');
  }
  
  // INSTITUCIONAL
  const instTipo = form.querySelector('#inst_tipo');
  const instEsc = form.querySelector('#inst_escuela');
  const instForm = form.querySelector('#inst_formacion');
  if (!instTipo || !instTipo.value) {
    mensajes.push('Seleccioná un tipo institucional.');
    if (instTipo) marcarError(instTipo);
  } else {
    if (instTipo.value === 'Director') {
      if (!instEsc || !instEsc.value) { mensajes.push('Seleccioná la escuela para Director.'); if (instEsc) marcarError(instEsc); }
    } else if (instTipo.value === 'Docente') {
      if (!instEsc || !instEsc.value) { mensajes.push('Seleccioná la escuela para Docente.'); if (instEsc) marcarError(instEsc); }
      if (!instForm || !instForm.value) { mensajes.push('Seleccioná la formación profesional para Docente.'); if (instForm) marcarError(instForm); }
    }
  }

  // USUARIOS
  const email = form.querySelector('#usuarios_email');
  const pwd = form.querySelector('#usuarios_clave');
  const pwdConf = form.querySelector('#usuarios_clave_conf');

  if (!email || !email.value.trim()) { mensajes.push('Email requerido.'); if (email) marcarError(email); }
  else if (!validarCorreo(email)) { mensajes.push('Formato de email inválido.'); marcarError(email); }

  if (!pwd || !pwd.value) { mensajes.push('Contraseña requerida.'); if (pwd) marcarError(pwd); }
  else if (pwd.value.length < 6) { mensajes.push('La contraseña debe tener al menos 6 caracteres.'); marcarError(pwd); }

  if (!pwdConf || !pwdConf.value) { mensajes.push('Confirmá la contraseña.'); if (pwdConf) marcarError(pwdConf); }
  else if (pwd && pwdConf && pwd.value !== pwdConf.value) { mensajes.push('Las contraseñas no coinciden.'); if (pwd) marcarError(pwd); if (pwdConf) marcarError(pwdConf); }

  if (mensajes.length) {
    return { ok: false, mensajes };
  }
  return { ok: true, mensajes: [] };
}

// EXPORTAR FUNCIONES
window.VALIDACIONES = window.VALIDACIONES || {};
window.VALIDACIONES.asegurarMensajeRegistro = asegurarMensajeRegistro;
window.VALIDACIONES.mostrarValidacionRegistro = mostrarValidacionRegistro;
window.VALIDACIONES.validarFormularioRegistro = validarFormularioRegistro;