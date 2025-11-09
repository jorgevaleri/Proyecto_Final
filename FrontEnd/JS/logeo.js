// LOGEO

document.addEventListener('DOMContentLoaded', function () {

  // SELECTORES RAPIDOS
  const qs = selector => document.querySelector(selector);
  const qsa = selector => Array.from(document.querySelectorAll(selector));

  // ELEMENTOS DEL FORMULARIO
  const formulario = qs('#form1');
  const inputCorreo = qs('#usuarios_email');
  const inputClave = qs('#usuarios_clave');
  const togglePwd = qs('#togglePassword');
  const chkRecordar = qs('#rememberMe');

  // CONTENEDOR DE MENSAJES
  const contenedorMsgs = function asegurador() {
    let el = qs('#login_validation_message');
    if (!el) {
      el = document.createElement('div');
      el.id = 'login_validation_message';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      if (formulario) {
        const boton = formulario.querySelector('.boton');
        if (boton && boton.parentNode) boton.parentNode.insertBefore(el, boton);
        else formulario.appendChild(el);
      } else {
        document.body.appendChild(el);
      }
    }
    return el;
  }();

  // HELPER VISUALES PARA MARCAR INPUTOS INVALIDOS
  function marcarInvalido(el) {
    if (!el) return;
    el.classList.add('error');
    el.setAttribute('aria-invalid', 'true');
  }
  function limpiarMarca(el) {
    if (!el) return;
    el.classList.remove('error');
    el.removeAttribute('aria-invalid');
  }

  // MOSTRAR LISTAS DE ERRORES
  function mostrarErrores(listaMensajes, textoServidor) {
    const el = contenedorMsgs;
    if (!el) return;
    if (textoServidor && textoServidor.trim()) {
      el.innerHTML = '<div class="server-error">' + textoServidor + '</div>';
    } else if (Array.isArray(listaMensajes) && listaMensajes.length) {
      el.innerHTML = '<ul class="login-errs">' + listaMensajes.map(m => '<li>' + m + '</li>').join('') + '</ul>';
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

  // OCULTAR ERRORES
  function ocultarErrores() {
    const el = contenedorMsgs;
    if (!el) return;
    el.innerHTML = '';
    el.style.display = 'none';
    el.className = '';
  }

  // OJO PARA MOSTRAR / OCULATAR CONTRASEÑA
  if (togglePwd && inputClave) {
    togglePwd.addEventListener('click', function () {
      const tipo = inputClave.getAttribute('type') === 'password' ? 'text' : 'password';
      inputClave.setAttribute('type', tipo);
      togglePwd.classList.toggle('fa-eye');
      togglePwd.classList.toggle('fa-eye-slash');
    });
  }

  // RECORDARME USANDO LOCALSTORAGE
  try {
    const guardado = localStorage.getItem('savedEmail');
    if (guardado && inputCorreo && chkRecordar) {
      inputCorreo.value = guardado;
      chkRecordar.checked = true;
    }
  } catch (e) { }

  if (inputCorreo && chkRecordar) {
    chkRecordar.addEventListener('change', function () {
      try {
        if (chkRecordar.checked) localStorage.setItem('savedEmail', inputCorreo.value);
        else localStorage.removeItem('savedEmail');
      } catch (e) { }
    });
    inputCorreo.addEventListener('input', function () {
      try {
        if (chkRecordar.checked) localStorage.setItem('savedEmail', inputCorreo.value);
      } catch (e) { }
    });
  }

  // MOSTRAR ERRORES POR SERVIDOR (SI EXISTEN)
  (function mostrarServidorSiExiste() {
    const server = contenedorMsgs.querySelector('.server-error');
    if (server && server.textContent.trim()) {
      mostrarErrores([], server.textContent.trim());
    } else {
      ocultarErrores();
    }
  })();

  // VALIDACION DEL FORMULARIO
  if (formulario && inputCorreo && inputClave) {
    formulario.addEventListener('submit', function (e) {

      // LIMPIAR MARCAS PREVIAS
      limpiarMarca(inputCorreo);
      limpiarMarca(inputClave);
      ocultarErrores();

      const errores = [];

      // EMAIL OBLIGATORIO
      const valCorreo = (inputCorreo.value || '').trim();
      if (!valCorreo) {
        errores.push('El correo es obligatorio.');
        marcarInvalido(inputCorreo);
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valCorreo)) {
        errores.push('Formato de correo inválido.');
        marcarInvalido(inputCorreo);
      }

      // CONTRASEÑA OBLIGATORIA
      if (!inputClave.value || inputClave.value.trim() === '') {
        errores.push('La contraseña es obligatoria.');
        marcarInvalido(inputClave);
      }

      if (errores.length) {
        mostrarErrores(errores, null);
        e.preventDefault();
        return false;
      }

      // SI NO HAY ERRORES SE ENVIA EL FORMULARIO
      return true;
    });

    // AL ESCRIBIR, LIMPIAR MARCAS Y PANEL SI NO QUEDAN ERRORES
    [inputCorreo, inputClave].forEach(function (inp) {
      inp.addEventListener('input', function () {
        limpiarMarca(inp);
        const restantes = qsa('.login-page .content .field input.error').length;
        if (restantes === 0) ocultarErrores();
      });
      inp.addEventListener('change', function () {
        limpiarMarca(inp);
        const restantes = qsa('.login-page .content .field input.error').length;
        if (restantes === 0) ocultarErrores();
      });
    });
  }

});