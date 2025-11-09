// VALIDACIONES FORMACION PROFESIONAL

document.addEventListener('DOMContentLoaded', function () {
  
  // BUSCAMOS FORMULARIO ESCUELA PARA HEREDAR ESTILOS
  const form = document.getElementById('escuelaForm');
  if (!form) return;

  // CONTENEDOR DE ERRORES
  const errorDiv = document.getElementById('formacionProfesionalFormError');

  // INPUTS
  const inputNombre = form.querySelector('input[name="formaciones_profesionales_nombre"]');
  const excludeInput = form.querySelector('input[name="exclude_id"]');

  // MOSTRAR MENSAJE DE ERROR
  function showError(text) {
    if (!errorDiv) { alert(text); return; }
    errorDiv.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'msg';
    p.textContent = text;
    errorDiv.appendChild(p);
    errorDiv.classList.add('visible');
    if (inputNombre) inputNombre.focus();
  }

  // LIMPIAR MENSAJE DE ERROR
  function clearError() {
    if (!errorDiv) return;
    errorDiv.innerHTML = '';
    errorDiv.classList.remove('visible');
  }

  // CHEQUEAR DUPLICADOS
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearError();

    const nombre = inputNombre ? inputNombre.value.trim() : '';

    // NO VACIO
    if (!nombre) {
      showError('Debe ingresar un nombre.');
      return false;
    }

    const data = new URLSearchParams();
    data.append('ajax_action', 'check_nombre');
    data.append('nombre', nombre);
    data.append('exclude_id', excludeInput ? excludeInput.value : '0');

    try {
      const resp = await fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data.toString(),
        credentials: 'same-origin'
      });

      if (!resp.ok) {
        showError('Error de red durante la validación. Intentá nuevamente.');
        return false;
      }

      const json = await resp.json();
      if (!json.ok) {
        showError(json.error || 'Error al validar nombre.');
        return false;
      }

      if (json.exists) {
        showError('Ya existe una formación con ese nombre.');
        return false;
      }

      // ENVIAMOS EL FORMULARIO - TODO OK
      form.submit();

    } catch (err) {
      console.error(err);
      showError('Error de conexión al validar. Intentá nuevamente.');
      return false;
    }
  });

  // LIMPIAR MENSAJE AL ESCRIBIR
  if (inputNombre) inputNombre.addEventListener('input', clearError);
});
