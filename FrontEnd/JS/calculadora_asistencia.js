// CACLULADORA DE ASISTENCIA

// FUNCION ANONIMA
(function () {
  // CONVIERTE EL VALOR A NÚMERO, EVITANDO NaN
  function numeroSeguro(value) {
    if (window.VALIDACIONES && typeof window.VALIDACIONES.aNumeroSeguro === 'function') {
      return window.VALIDACIONES.aNumeroSeguro(value);
    }
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
  }

  // OBTENER FILAS DE DATOS
  function obtenerFilasDatos(tableId) {
    tableId = tableId || 'id_tabla1';
    const table = document.getElementById(tableId);
    if (!table) return [];
    return Array.prototype.slice.call(table.querySelectorAll('tr'), 1);
  }

  // SINCRONIZAR LOS DIAS HABILES DESDE LA PRIMERA FILA
  function sincronizarDiasDesdePrimeraFila(idTabla) {
    idTabla = idTabla || 'id_tabla1';

    // SI EXISTE LA FUNCION EN VALIDACIONES, DELEGAMOS
    if (window.VALIDACIONES && typeof window.VALIDACIONES.sincronizarDiasDesdePrimeraFila === 'function') {
      return window.VALIDACIONES.sincronizarDiasDesdePrimeraFila(idTabla, 'id_dias_habiles');
    }
    const table = document.getElementById(idTabla);
    const diasEl = document.getElementById('id_dias_habiles');
    if (!table || !diasEl) return;

    // SI NO SE CARGAN LOS DATOS, NO SE ESCRIBE NADA
    if (table.rows.length <= 1) { diasEl.value = ''; return; }
    const firstRow = table.rows[1];
    if (!firstRow) { diasEl.value = ''; return; }
    const inputs = firstRow.querySelectorAll('input[type="number"]');
    const asiRaw = inputs[0] ? inputs[0].value : '';
    const inaRaw = inputs[1] ? inputs[1].value : '';
    if ((asiRaw === '' || asiRaw === null) && (inaRaw === '' || inaRaw === null)) {
      diasEl.value = '';
      return;
    }
    diasEl.value = numeroSeguro(asiRaw) + numeroSeguro(inaRaw);
  }

  // ERROR EN RESULTADOS
  function establecerResultadosGuion() {
    const campos = [
      'id_asi_var', 'id_asi_muj', 'id_asi_tot',
      'id_ina_var', 'id_ina_muj', 'id_ina_tot',
      'id_asi_med_var', 'id_asi_med_muj', 'id_asi_med_tot',
      'id_por_var', 'id_por_muj', 'id_por_tot'
    ];
    campos.forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.textContent = '-';
    });
  }

  // FUNCION CENTRAL
  function calcularResultados(idTabla) {
    // ASEGURAMOS QUE DIAS HABILES ESTA SINCRONIZADO ANTES
    sincronizarDiasDesdePrimeraFila(idTabla);

    var rows = obtenerFilasDatos(idTabla);
    var asiMasc = 0, asiFem = 0, inaMasc = 0, inaFem = 0;

    rows.forEach(function (row) {
      var select = row.querySelector('select');
      var inputs = row.querySelectorAll('input[type="number"]');
      var asi = numeroSeguro(inputs[0] ? inputs[0].value : 0);
      var ina = numeroSeguro(inputs[1] ? inputs[1].value : 0);
      var sexo = select ? (select.value || '') : '';

      if (sexo === 'Masculino') { asiMasc += asi; inaMasc += ina; }
      else if (sexo === 'Femenino') { asiFem += asi; inaFem += ina; }
      else {
        // SI NO HAY SEXO SELECCIONADO, NO CALCULAMOS
      }
    });

    var asiTot = asiMasc + asiFem;
    var inaTot = inaMasc + inaFem;
    var diasHabiles = numeroSeguro(document.getElementById('id_dias_habiles')?.value);

    var asiMedMasc = diasHabiles > 0 ? asiMasc / diasHabiles : null;
    var asiMedFem = diasHabiles > 0 ? asiFem / diasHabiles : null;
    var asiMedTot = diasHabiles > 0 ? asiTot / diasHabiles : null;

    var porMasc = (asiMasc + inaMasc) > 0 ? (asiMasc * 100) / (asiMasc + inaMasc) : null;
    var porFem = (asiFem + inaFem) > 0 ? (asiFem * 100) / (asiFem + inaFem) : null;
    var porTot = (asiTot + inaTot) > 0 ? (asiTot * 100) / (asiTot + inaTot) : null;

    function setTexto(id, value) {
      var el = document.getElementById(id);
      if (!el) return;
      el.textContent = (value === null || value === undefined) ? '-' : String(value);
    }

    // PONER LOS TOTALES
    setTexto('id_asi_var', asiMasc);
    setTexto('id_asi_muj', asiFem);
    setTexto('id_asi_tot', asiTot);

    setTexto('id_ina_var', inaMasc);
    setTexto('id_ina_muj', inaFem);
    setTexto('id_ina_tot', inaTot);

    // PONER MEDIAS
    setTexto('id_asi_med_var', asiMedMasc !== null ? Math.round(asiMedMasc) : '-');
    setTexto('id_asi_med_muj', asiMedFem !== null ? Math.round(asiMedFem) : '-');
    setTexto('id_asi_med_tot', asiMedTot !== null ? Math.round(asiMedTot) : '-');

    // PONER PORCENTAJES
    setTexto('id_por_var', porMasc !== null ? (Number(porMasc.toFixed(2)) + '%') : '-');
    setTexto('id_por_muj', porFem !== null ? (Number(porFem.toFixed(2)) + '%') : '-');
    setTexto('id_por_tot', porTot !== null ? (Number(porTot.toFixed(2)) + '%') : '-');
  }

  // FUNCION RECALCULAR
  function recalcularTodo(idTabla) {
    idTabla = idTabla || 'id_tabla1';

    if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
      var ejecutado = window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () {
        calcularResultados(idTabla);
      });

      if (!ejecutado) {
        establecerResultadosGuion();
      }
      return;
    }
    calcularResultados(idTabla);
  }

  // AGREGAR FILAS
  function agregarFila(idTabla) {
    idTabla = idTabla || 'id_tabla1';
    var table = document.getElementById(idTabla);
    if (!table) return;

    // CONTADOR DE FILAS
    var currentRows = table.querySelectorAll('tr').length - 1;
    var newIndex = currentRows + 1;

    // CREA FILA Y CELDAS
    var row = table.insertRow(-1);
    var cellNum = row.insertCell(0);
    var cellSexo = row.insertCell(1);
    var cellAsis = row.insertCell(2);
    var cellInas = row.insertCell(3);
    var cellBtn = row.insertCell(4);

    // NÚMERO DE FILAS
    cellNum.textContent = newIndex;

    // SELECT SEXO
    var select = document.createElement('select');
    select.required = true;
    select.innerHTML = '<option value="">Seleccione</option>' +
      '<option value="Masculino">Masculino</option>' +
      '<option value="Femenino">Femenino</option>';
    cellSexo.appendChild(select);

    // INPUT ASISTENCIA
    var inputAsis = document.createElement('input');
    inputAsis.type = 'number'; inputAsis.min = '0'; inputAsis.value = '';
    inputAsis.style.width = '90%'; inputAsis.style.textAlign = 'center';
    cellAsis.appendChild(inputAsis);

    // INPUT INASISTENCIA
    var inputInas = document.createElement('input');
    inputInas.type = 'number'; inputInas.min = '0'; inputInas.value = '';
    inputInas.style.width = '90%'; inputInas.style.textAlign = 'center';
    cellInas.appendChild(inputInas);

    // BOTON ELIMINAR FILA
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-eliminar';
    btn.title = 'Eliminar fila';
    btn.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
    btn.addEventListener('click', function () { eliminarFila(row); });
    cellBtn.appendChild(btn);

    // LISTENERS AL CAMBIAR SEXO, SE RECALCULA
    select.addEventListener('change', function () {
      if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
        window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
      } else {
        calcularResultados(idTabla);
      }
    });

    // LISTENERS SI SE CAMBIA INPUT DE LA PRIMERA FILA, SE RECALCULA
    inputAsis.addEventListener('input', function () {
      var firstRow = table.querySelectorAll('tr')[1];
      if (firstRow === row) sincronizarDiasDesdePrimeraFila(idTabla);
      if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
        window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
      } else {
        calcularResultados(idTabla);
      }
    });

    inputInas.addEventListener('input', function () {
      var firstRow = table.querySelectorAll('tr')[1];
      if (firstRow === row) sincronizarDiasDesdePrimeraFila(idTabla);
      if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
        window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
      } else {
        calcularResultados(idTabla);
      }
    });

    recalcularTodo(idTabla);
  }

  // RECALCULA EL NUMERO DE FILAS
  function actualizarNumeracion(idTabla) {
    idTabla = idTabla || 'id_tabla1';
    var rows = obtenerFilasDatos(idTabla);
    rows.forEach(function (row, idx) {
      var cellNum = row.cells[0];
      if (cellNum) cellNum.textContent = idx + 1;
    });
  }

  // ELIMINAR FILA
  function eliminarFila(rowOrElement, idTabla) {
    idTabla = idTabla || 'id_tabla1';
    var row = rowOrElement;
    if (!row) return;
    if (row.nodeType !== 1) return;
    if (row.tagName && row.tagName.toLowerCase() !== 'tr') {
      row = row.closest && row.closest('tr');
    }
    if (!row) return;
    row.parentNode.removeChild(row);
    actualizarNumeracion(idTabla);
    sincronizarDiasDesdePrimeraFila(idTabla);

    if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
      window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
    } else {
      calcularResultados(idTabla);
    }
  }

  // VACIAR TABLAS
  function vaciarTabla(idTabla) {
    idTabla = idTabla || 'id_tabla1';
    var table = document.getElementById(idTabla);
    if (!table) return;

    var totalRows = table.rows.length;
    if (totalRows <= 1) {

      // SI ESTA VACIA, SUPRIME LA VALIDACION
      window.VALIDACIONES && window.VALIDACIONES._internals && window.VALIDACIONES._internals.disable();
      window.VALIDACIONES && window.VALIDACIONES.bloquearCalculosSiError && window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
      return;
    }

    if (totalRows === 2) {
      var dataRow = table.rows[1];
      var inputs = dataRow.querySelectorAll('input[type="number"]');
      inputs.forEach(function (i) { i.value = ''; });
      var sel = dataRow.querySelector('select');
      if (sel) sel.selectedIndex = 0;

      // SUPRIME LA VALIDACION AL DEJAR LA TABLA EN ESTADO BASE
      window.VALIDACIONES && window.VALIDACIONES._internals && window.VALIDACIONES._internals.disable();
      sincronizarDiasDesdePrimeraFila(idTabla);
      window.VALIDACIONES && window.VALIDACIONES.bloquearCalculosSiError && window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
      return;
    }

    // ELIMINAMOS TODAS LAS FILAS EXCEPTO LA PRIMERA
    while (table.rows.length > 2) {
      table.deleteRow(table.rows.length - 1);
    }
    var remainingRow = table.rows[1];
    if (remainingRow) {
      var inputs2 = remainingRow.querySelectorAll('input[type="number"]');
      inputs2.forEach(function (i) { i.value = ''; });
      var sel2 = remainingRow.querySelector('select');
      if (sel2) sel2.selectedIndex = 0;
    }

    // SUPRIMIMOS LAS VALIDACIONES
    window.VALIDACIONES && window.VALIDACIONES._internals && window.VALIDACIONES._internals.disable();
    sincronizarDiasDesdePrimeraFila(idTabla);
    window.VALIDACIONES && window.VALIDACIONES.bloquearCalculosSiError && window.VALIDACIONES.bloquearCalculosSiError(idTabla, 'id_dias_habiles', function () { calcularResultados(idTabla); });
  }

  // VALIDACION ANTES DE ENVIAR
  function validarTodo() {
    if (window.VALIDACIONES && typeof window.VALIDACIONES.validarPaginaAsistencia === 'function') {
      if (window.VALIDACIONES._internals && typeof window.VALIDACIONES._internals.enable === 'function') {
        window.VALIDACIONES._internals.enable();
      }
      var res = window.VALIDACIONES.validarPaginaAsistencia('id_tabla1', 'id_dias_habiles');
      return !!(res && res.ok);
    }

    // VALIDACION LOCAL
    sincronizarDiasDesdePrimeraFila('id_tabla1');
    var dias = numeroSeguro(document.getElementById('id_dias_habiles')?.value);
    if (dias <= 0) return false;
    var rows = obtenerFilasDatos('id_tabla1');
    for (var i = 0; i < rows.length; i++) {
      var inputs = rows[i].querySelectorAll('input[type="number"]');
      var asi = numeroSeguro(inputs[0] ? inputs[0].value : 0);
      var ina = numeroSeguro(inputs[1] ? inputs[1].value : 0);
      if ((asi + ina) !== dias) return false;
      var sel = rows[i].querySelector('select');
      if (!sel || !sel.value) return false;
    }
    return true;
  }

  // INICIALIZACION Y EVENTOS GLOBALES
  document.addEventListener('DOMContentLoaded', function () {
    var mainTable = document.getElementById('id_tabla1');
    var dias = document.getElementById('id_dias_habiles');

    // DIAS HABILES NO EDITABLE POR EL USUARIO
    if (dias) {
      dias.readOnly = true;
      dias.style.cursor = 'not-allowed';
      dias.title = 'Se calcula automáticamente a partir de la primera fila';
    }

    // USA LA API DE VALIDACIONES PARA EJECUTAR CALCULOS
    if (mainTable) {
      mainTable.addEventListener('input', function (e) {
        var firstRow = mainTable.querySelectorAll('tr')[1];
        if (firstRow && firstRow.contains(e.target)) {
          var inputs = Array.prototype.slice.call(firstRow.querySelectorAll('input[type="number"]'));
          if (inputs.length >= 2 && (e.target === inputs[0] || e.target === inputs[1])) {
            sincronizarDiasDesdePrimeraFila('id_tabla1');
          }
        }
        if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
          window.VALIDACIONES.bloquearCalculosSiError('id_tabla1', 'id_dias_habiles', function () { calcularResultados('id_tabla1'); });
        } else {
          calcularResultados('id_tabla1');
        }
      });

      mainTable.addEventListener('change', function () {
        if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
          window.VALIDACIONES.bloquearCalculosSiError('id_tabla1', 'id_dias_habiles', function () { calcularResultados('id_tabla1'); });
        } else {
          calcularResultados('id_tabla1');
        }
      });
    }

    // FOCUSOUT VISUAL, CUANDO UN OBJETO PIERDE EL FOCO
    if (window.VALIDACIONES && typeof window.VALIDACIONES.marcarVacioAlPerderFoco === 'function') {
      document.addEventListener('focusout', window.VALIDACIONES.marcarVacioAlPerderFoco);
    }

    // BOTONES AGREGAR / VACIAR
    var btnAgregar = document.getElementById('btnAgregar');
    var btnVaciar = document.getElementById('btnVaciar');
    if (btnAgregar) btnAgregar.addEventListener('click', function () { agregarFila('id_tabla1'); });
    if (btnVaciar) btnVaciar.addEventListener('click', function () { vaciarTabla('id_tabla1'); });

    // SINCRONIZAR Y RECALCULAR
    if (window.VALIDACIONES && typeof window.VALIDACIONES.bloquearCalculosSiError === 'function') {
      window.VALIDACIONES.bloquearCalculosSiError('id_tabla1', 'id_dias_habiles', function () { calcularResultados('id_tabla1'); });
    } else {
      sincronizarDiasDesdePrimeraFila('id_tabla1');
      calcularResultados('id_tabla1');
    }
  });

  // EXPORTACION DE FUNCIONES
  window.agregarFila = agregarFila;
  window.vaciarTabla = vaciarTabla;
  window.eliminarFila = eliminarFila;
  window.recalcularTodo = recalcularTodo;
  window.validarTodo = validarTodo;

})();