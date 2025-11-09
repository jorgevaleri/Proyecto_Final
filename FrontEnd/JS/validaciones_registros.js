// VALIDACIOENS REGISTROS

// FUNCION ANONIMA
(function () {
  window.VALIDACIONES = window.VALIDACIONES || {};

  // CONVIERTE A NUMERO, DEVUELVE 0 SI NO ES UN NUMERO
  function aNumeroSeguro(v) {
    const n = Number(String(v).trim());
    return Number.isFinite(n) ? n : 0;
  }
  function marcarError(el) { if (!el) return; el.classList.add('error'); el.setAttribute('aria-invalid','true'); }
  function limpiarError(el) { if (!el) return; el.classList.remove('error'); el.removeAttribute('aria-invalid'); }

  // MANOJO DE VALIDACIONES
  let _msgEl = null;
  let _observer = null;

  // MENSAJE DE ERROR
  function _crearMensaje(idMsg = 'validation_message') {
    if (_msgEl) return _msgEl;
    let el = document.getElementById(idMsg);
    if (!el) {
      el = document.createElement('div');
      el.id = idMsg;
      el.style.display = 'none';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
    }
    _msgEl = el;
    return el;
  }

  function _ubicarMensaje(idMsg = 'validation_message') {
    const el = _crearMensaje(idMsg);
    const main = document.querySelector('main.fp-page') || document.querySelector('main.cuerpo') || document.body;
    if (!main) return false;

    const tablas = document.querySelector('.tablas-container');
    const botones = document.querySelector('.botones');

    if (tablas) {
      try {
        const parent = tablas.parentNode || main;
        if (el.parentNode === parent && el.nextElementSibling === tablas) return true;
        parent.insertBefore(el, tablas);
        return true;
      } catch (e) {
        try { main.appendChild(el); return true; } catch (e2) { return false; }
      }
    }

    if (botones) {
      try {
        const parent = botones.parentNode || main;
        if (el.parentNode === parent && botones.nextSibling === el) return true;
        if (botones.nextSibling) parent.insertBefore(el, botones.nextSibling);
        else parent.appendChild(el);
        return true;
      } catch (e) {
        try { main.appendChild(el); return true; } catch (e2) { return false; }
      }
    }

    try { if (el.parentNode !== main) main.appendChild(el); return true; } catch (e) { return false; }
  }

  function asegurarMensaje(idMsg = 'validation_message') {
    const el = _crearMensaje(idMsg);
    if (_ubicarMensaje(idMsg)) {
      if (_observer) { _observer.disconnect(); _observer = null; }
      return el;
    }
    if (!_observer) {
      _observer = new MutationObserver((mutations, obs) => {
        if (_ubicarMensaje(idMsg)) { obs.disconnect(); _observer = null; }
      });
      _observer.observe(document.body, { childList: true, subtree: true });
    }
    return el;
  }

  // SINCRONIZAR DIAS DESDE LA PRIMERA FILA
  function sincronizarDiasDesdePrimeraFila(idTabla = 'id_tabla1', idDias = 'id_dias_habiles') {
    const tabla = document.getElementById(idTabla);
    const diasEl = document.getElementById(idDias);
    if (!diasEl) return;
    if (!tabla) { diasEl.value = ''; return; }

    try {
      const tbody = tabla.tBodies && tabla.tBodies.length ? tabla.tBodies[0] : tabla;
      const filas = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.querySelectorAll('td').length > 0);
      if (!filas || filas.length === 0) { diasEl.value = ''; return; }
      const primera = filas[0];
      if (!primera) { diasEl.value = ''; return; }

      const numInputs = Array.from(primera.querySelectorAll('input[type="number"]'));
      let asiVal = '', inaVal = '';
      if (numInputs.length >= 2) {
        asiVal = numInputs[0].value; inaVal = numInputs[1].value;
      } else if (numInputs.length === 1) {
        asiVal = numInputs[0].value;
        const other = primera.querySelectorAll('input[type="text"], input:not([type])');
        inaVal = (other && other[0]) ? other[0].value : '';
      } else {
        const cells = primera.querySelectorAll('td');
        if (cells.length >= 5) {
          asiVal = cells[3] ? cells[3].textContent.trim() : '';
          inaVal = cells[4] ? cells[4].textContent.trim() : '';
        }
      }

      if ((asiVal === '' || asiVal === null) && (inaVal === '' || inaVal === null)) {
        diasEl.value = '';
        return;
      }
      const candidato = aNumeroSeguro(asiVal) + aNumeroSeguro(inaVal);
      diasEl.value = candidato > 0 ? candidato : '';
    } catch (e) {
      diasEl.value = '';
    }
  }

  // VALIDAR FILAS CON DIAS HABILES
  function validarFilasContraDias(idTabla = 'id_tabla1', idDias = 'id_dias_habiles') {
    const t = document.getElementById(idTabla);
    if (!t) return { ok: false, reason: 'tabla_no_encontrada', detalles: [] };

    const tbody = t.tBodies && t.tBodies.length ? t.tBodies[0] : t;
    const filas = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.querySelectorAll('td').length > 0);
    const diasEl = document.getElementById(idDias);
    const dias = diasEl ? aNumeroSeguro(diasEl.value) : 0;
    if (!diasEl || dias <= 0) return { ok: false, reason: 'dias_no_definidos', detalles: [] };

    const invalidas = [];
    filas.forEach((fila, idx) => {
      let asi = 0, ina = 0;
      const inputsNum = Array.from(fila.querySelectorAll('input[type="number"]'));
      if (inputsNum.length >= 2) {
        asi = aNumeroSeguro(inputsNum[0].value); ina = aNumeroSeguro(inputsNum[1].value);
      } else {
        const cells = fila.querySelectorAll('td');
        if (cells.length >= 5) {
          asi = aNumeroSeguro(cells[3].textContent); ina = aNumeroSeguro(cells[4].textContent);
        } else {
          invalidas.push({ fila: idx + 1, tipo: 'suma_incorrecta', asi, ina, suma: asi+ina });
          return;
        }
      }
      if ((asi + ina) !== dias) invalidas.push({ fila: idx + 1, tipo: 'suma_incorrecta', asi, ina, suma: asi+ina });
    });

    return { ok: invalidas.length === 0, reason: invalidas.length ? 'filas_invalidas' : null, detalles: invalidas };
  }

  // MOSTRAR VALIDACIONES
  let mostrarValidaciones = false;

  function validarPaginaAsistencia(idTabla = 'id_tabla1', idDias = 'id_dias_habiles', idMsg = 'validation_message') {
    sincronizarDiasDesdePrimeraFila(idTabla, idDias);
    const resultado = validarFilasContraDias(idTabla, idDias);
    const msgEl = asegurarMensaje(idMsg);

    if (!mostrarValidaciones) {
      const todas = document.querySelectorAll(`#${idTabla} tr:not(:first-child)`);
      todas.forEach(r => {
        r.classList.remove('invalid-row');
        r.querySelectorAll('input[type="number"], select, input').forEach(i => limpiarError(i));
      });
      if (msgEl) { msgEl.style.display = 'none'; msgEl.className = ''; msgEl.textContent = ''; }
      return { ok: true, suppressed: true };
    }

    const todas = document.querySelectorAll(`#${idTabla} tr:not(:first-child)`);
    todas.forEach(r => {
      r.classList.remove('invalid-row');
      r.querySelectorAll('input[type="number"], select, input').forEach(i => limpiarError(i));
    });

    if (!resultado.ok) {
      if (resultado.reason === 'dias_no_definidos') {
        if (msgEl) { msgEl.className = 'error'; msgEl.textContent = 'ERROR: Días hábiles no definidos. Complete la PRIMERA fila.'; msgEl.style.display = 'block'; }
        todas.forEach(r => r.classList.add('invalid-row'));
        const primera = document.querySelector(`#${idTabla} tr:not(:first-child)`);
        if (primera) primera.querySelectorAll('input[type="number"], select, input').forEach(i => marcarError(i));
        return { ok: false, resultado };
      } else {
        const totalInv = resultado.detalles.length;
        let mensaje = `ERROR: ${totalInv} fila(s) inválida(s). Revise las filas marcadas.`;
        if (msgEl) { msgEl.className = 'error'; msgEl.textContent = mensaje; msgEl.style.display = 'block'; }
        todas.forEach((r, idx) => {
          const existe = resultado.detalles.find(d => d.fila === idx + 1);
          if (existe) { r.classList.add('invalid-row'); r.querySelectorAll('input[type="number"], select, input').forEach(i => marcarError(i)); }
          else r.classList.remove('invalid-row');
        });
        return { ok: false, resultado };
      }
    } else {
      if (msgEl) {
        msgEl.className = 'success';
        msgEl.textContent = 'Validación OK — todas las filas coinciden con los días hábiles.';
        msgEl.style.display = 'block';
        setTimeout(() => { const e = document.getElementById(idMsg); if (e && e.className === 'success') e.style.display = 'none'; }, 1400);
      }
      todas.forEach(r => r.classList.remove('invalid-row'));
      return { ok: true };
    }
  }

  // ACTIVAR VALIDACIONES LUEGO DE LA PRIMERA INTERACCION
  function initTriggerAsistencia(idTabla = 'id_tabla1') {
    const tabla = document.getElementById(idTabla);
    if (!tabla) return;
    const onFirst = () => {
      if (mostrarValidaciones) return;
      mostrarValidaciones = true;
      asegurarMensaje();
      validarPaginaAsistencia(idTabla, 'id_dias_habiles');
    };
    tabla.addEventListener('input', onFirst, { passive: true });
    tabla.addEventListener('change', onFirst, { passive: true });
  }

  // BLOQUEAR CALCULOS SI HAY ERRORES
  function bloquearCalculosSiError(idTabla, idDias, callback) {
    const res = validarPaginaAsistencia(idTabla, idDias);
    if (res && res.suppressed) { if (typeof callback === 'function') callback(); return true; }
    if (!res.ok) return false;
    if (typeof callback === 'function') callback();
    return true;
  }

  // EXPORTAR API
  window.VALIDACIONES.aNumeroSeguro = aNumeroSeguro;
  window.VALIDACIONES.marcarError = marcarError;
  window.VALIDACIONES.limpiarError = limpiarError;
  window.VALIDACIONES.sincronizarDiasDesdePrimeraFila = sincronizarDiasDesdePrimeraFila;
  window.VALIDACIONES.validarFilasContraDias = validarFilasContraDias;
  window.VALIDACIONES.validarPaginaAsistencia = validarPaginaAsistencia;
  window.VALIDACIONES.bloquearCalculosSiError = bloquearCalculosSiError;
  window.VALIDACIONES.iniciarTriggerAsistencia = initTriggerAsistencia;
  window.VALIDACIONES._internals = {
    enable: function(){ mostrarValidaciones = true; },
    disable: function(){ mostrarValidaciones = false; const e=document.getElementById('validation_message'); if(e){ e.style.display='none'; e.className=''; e.textContent=''; } }
  };

  // INICIALIZACION AUTOMATICA AL CARGAR EL DOM
  document.addEventListener('DOMContentLoaded', function(){ asegurarMensaje(); initTriggerAsistencia('id_tabla1'); });

})();