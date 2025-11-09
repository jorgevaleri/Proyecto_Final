// USUARIOS
document.addEventListener('DOMContentLoaded', function () {

  // FORZAR NOVALIDATE EN TODOS LOS FORMS
  document.querySelectorAll('form').forEach(function (f) {
    f.setAttribute('novalidate', 'novalidate');
  });

  // ID DE LA CAJA DE ERRORES
  const ERROR_BOX_ID = 'usuariosFormError';

  // DEVUELVE EL ELEMENTO GLOBAL DE ERRORES
  function getGlobalErrorBox() {
    return document.getElementById(ERROR_BOX_ID);
  }

  // MOSTRAR / BORRAR MENSAJE DE ERROR
  function showFormError(formEl, message) {
    if (!formEl && !getGlobalErrorBox()) { alert(message); return; }

    const globalBox = getGlobalErrorBox();
    if (globalBox) {
      globalBox.innerHTML = '<p class="msg">' + escapeHtml(message) + '</p>';
      globalBox.classList.add('visible');
      globalBox.style.display = 'block';
      try { globalBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { }
      return;
    }

    if (formEl) {
      const msgDiv = formEl.querySelector('.form-validation-message');
      if (msgDiv) {
        msgDiv.innerHTML = '<p class="msg">' + escapeHtml(message) + '</p>';
        msgDiv.style.display = 'block';
        try { msgDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { }
        return;
      }
    }

    if (formEl) {
      let box = formEl.querySelector('.form-error');
      if (!box) {
        box = document.createElement('div');
        box.className = 'form-error';
        box.style.margin = '8px 0';
        box.style.padding = '8px';
        box.style.background = '#fdecea';
        box.style.border = '1px solid #f5c2c0';
        box.style.color = '#611a15';
        box.style.borderRadius = '4px';
        formEl.insertBefore(box, formEl.firstChild);
      }
      box.innerHTML = '<p class="msg">' + escapeHtml(message) + '</p>';
      try { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { }
      return;
    }

    alert(message);
  }

  // LIMPIAR MENSAJE
  function clearFormError(formEl) {
    const globalBox = getGlobalErrorBox();
    if (globalBox) {
      globalBox.innerHTML = '';
      globalBox.classList.remove('visible');
      globalBox.style.display = 'none';
    }
    if (formEl) {
      const msgDiv = formEl.querySelector('.form-validation-message');
      if (msgDiv) { msgDiv.innerHTML = ''; msgDiv.style.display = 'none'; }
      const fallback = formEl.querySelector('.form-error');
      if (fallback) fallback.remove();
    }
  }

  // INSERTAR TEXTO EN INNERHTML EVITANDO XSS
  function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"'`=\/]/g, function (s) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      }[s];
    });
  }

  // TOGGLE DE CONTRASEÑA
  document.body.addEventListener('click', function (ev) {
    const t = ev.target;
    if (t && t.classList && t.classList.contains('toggle-password')) {
      const selector = t.getAttribute('toggle');
      const input = selector ? document.querySelector(selector) : null;
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        t.classList.remove('bi-eye-slash');
        t.classList.add('bi-eye');
      } else {
        input.type = 'password';
        t.classList.remove('bi-eye');
        t.classList.add('bi-eye-slash');
      }
    }
  });

  // VALIDACIONES
  const formAdd = document.getElementById('form-add-user');
  if (formAdd) {
    const persona = formAdd.querySelector('select[name="persona_id"]');
    const email = formAdd.querySelector('input[name="usuarios_email"]');
    const pass = formAdd.querySelector('input[name="usuarios_clave"]');
    const passConf = formAdd.querySelector('input[name="usuarios_clave_conf"]');

    formAdd.addEventListener('submit', function (e) {
      clearFormError(formAdd);

      if (!persona || persona.value.trim() === '') {
        e.preventDefault();
        showFormError(formAdd, 'Seleccioná una persona.');
        return false;
      }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        e.preventDefault();
        showFormError(formAdd, 'Ingresá un email válido.');
        email && email.focus();
        return false;
      }
      if (!pass || pass.value.length < 6) {
        e.preventDefault();
        showFormError(formAdd, 'La contraseña debe tener al menos 6 caracteres.');
        pass && pass.focus();
        return false;
      }
      if (pass.value !== (passConf ? passConf.value : '')) {
        e.preventDefault();
        showFormError(formAdd, 'Las contraseñas no coinciden.');
        passConf && passConf.focus();
        return false;
      }
      return true;
    });

    // AL ESCRIBIR LIMPIAR ERRORES
    formAdd.addEventListener('input', function () { clearFormError(formAdd); });
  }

  const formEdit = document.getElementById('form-edit-user');
  if (formEdit) {
    formEdit.addEventListener('submit', function (e) {
      clearFormError(formEdit);
      const email = formEdit.querySelector('input[name="usuarios_email"]');
      const passActual = formEdit.querySelector('input[name="pass_actual"]');
      const passNueva = formEdit.querySelector('input[name="pass_nueva"]');
      const passConf = formEdit.querySelector('input[name="pass_conf"]');

      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        e.preventDefault();
        showFormError(formEdit, 'Ingresá un email válido.');
        email && email.focus();
        return false;
      }

      const changing = (passActual && passActual.value) || (passNueva && passNueva.value) || (passConf && passConf.value);
      if (changing) {
        if (!passActual || passActual.value.trim() === '') {
          e.preventDefault();
          showFormError(formEdit, 'Completá la contraseña actual para cambiarla.');
          passActual && passActual.focus();
          return false;
        }
        if (!passNueva || passNueva.value.length < 6) {
          e.preventDefault();
          showFormError(formEdit, 'La nueva contraseña debe tener al menos 6 caracteres.');
          passNueva && passNueva.focus();
          return false;
        }
        if (passNueva.value !== (passConf ? passConf.value : '')) {
          e.preventDefault();
          showFormError(formEdit, 'La nueva contraseña y su confirmación no coinciden.');
          passConf && passConf.focus();
          return false;
        }
      }

      return true;
    });

    formEdit.addEventListener('input', function () { clearFormError(formEdit); });
  }

  const formDelete = document.getElementById('form-delete-user');
  if (formDelete) {
    formDelete.addEventListener('submit', function (e) {
      return true;
    });
  }

});