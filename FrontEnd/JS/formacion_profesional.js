// FORMACION PROFESIONAL

document.addEventListener('DOMContentLoaded', function () {

  // MOSTRAR ALERTAS CON SWEETALERT2
  const params = new URLSearchParams(window.location.search);
  const msg = params.get('msg');

  if (msg) {
    const items = {
      guardado: {
        icon: 'success',
        title: 'Guardado',
        text: 'La formación profesional fue creada correctamente.'
      },
      editado: {
        icon: 'success',
        title: 'Actualizado',
        text: 'Los cambios fueron guardados correctamente.'
      },
      eliminado: {
        icon: 'success',
        title: 'Eliminado',
        text: 'La formación fue eliminada.'
      },
      restaurado: {
        icon: 'success',
        title: 'Restaurado',
        text: 'La formación fue restaurada.'
      }
    };

    if (items[msg]) {
      Swal.fire(items[msg]);
    }
    params.delete('msg');
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, document.title, newUrl);
  }

  // CONFIRMAR ANTES DE ELIMINAR
  if (window.location.search.includes('action=delete')) {
    const form = document.querySelector('form.botones');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        Swal.fire({
          title: '¿Estás seguro?',
          text: "Esta acción moverá la formación a eliminados.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    }
  }
});