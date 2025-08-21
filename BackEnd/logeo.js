document.addEventListener('DOMContentLoaded', () => {
  console.log('logeo.js cargado');  // <--- Verifica que sale en la consola

  // ——— 1) Toggle de contraseña ———
  const pwdInput = document.getElementById('usuarios_clave');
  const toggle   = document.getElementById('togglePassword');
  if (pwdInput && toggle) {
    toggle.addEventListener('click', () => {
      const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
      pwdInput.setAttribute('type', type);
      toggle.classList.toggle('fa-eye');
      toggle.classList.toggle('fa-eye-slash');
    });
  }

  // ——— 2) Recordarme (localStorage) ———
  const emailInput = document.getElementById('usuarios_email');
  const chkRemember = document.getElementById('rememberMe');
  const savedEmail = localStorage.getItem('savedEmail');
  if (savedEmail && emailInput && chkRemember) {
    emailInput.value = savedEmail;
    chkRemember.checked = true;
  }

  // Guardar/limpiar al cambiar el checkbox
  if (emailInput && chkRemember) {
    chkRemember.addEventListener('change', () => {
      if (chkRemember.checked) localStorage.setItem('savedEmail', emailInput.value);
      else localStorage.removeItem('savedEmail');
    });
    // Y mientras tipeas, si está marcado, guarda automáticamente
    emailInput.addEventListener('input', () => {
      if (chkRemember.checked) localStorage.setItem('savedEmail', emailInput.value);
    });
  }

  // ——— 3) Validación de formulario ———
  const form = document.getElementById('form1');
  const errEmail = document.getElementById('error_email');
  const errPwd   = document.getElementById('error_clave');

  if (form && emailInput && pwdInput && errEmail && errPwd) {
    form.addEventListener('submit', function(e) {
      let valid = true;

      // Email obligatorio + patrón
      const emailVal = emailInput.value.trim();
      if (!emailVal) {
        valid = false;
        emailInput.classList.add('error');
        errEmail.textContent = 'El correo es obligatorio.';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        valid = false;
        emailInput.classList.add('error');
        errEmail.textContent = 'Formato de correo inválido.';
      } else {
        emailInput.classList.remove('error');
        errEmail.textContent = '';
      }

      // Contraseña no vacía
      if (!pwdInput.value) {
        valid = false;
        pwdInput.classList.add('error');
        errPwd.textContent = 'La contraseña es obligatoria.';
      } else {
        pwdInput.classList.remove('error');
        errPwd.textContent = '';
      }

      if (!valid) {
        console.log('Validación fallida, bloqueo envío');
        e.preventDefault();
      }
    });
  }
});
