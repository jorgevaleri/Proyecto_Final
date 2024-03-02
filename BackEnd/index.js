//----------TABLA 1----------//
//Declaro las variables para incrementar las filas
let rowNum = 1;

//Funcion para agregar filas a la tabla
function insertarFila() {
  rowNum++;
  let table = document.getElementById('id_tabla1');
  let row = table.insertRow(-1);
  let num = row.insertCell(0);
  let sexo = row.insertCell(1);
  let asis = row.insertCell(2);
  let inas = row.insertCell(3);
  let btn = row.insertCell(4);

  //Estructuras de las filas
  num.innerHTML = rowNum;
  sexo.innerHTML = `<select required onchange="calcularAsistencia(); calcularInsistencia(); calcularAsisMedi(); calcularPorAsis()">
                        <option value="">Seleccione</option>
                        <option id="masculino" value="Masculino">Masculino</option>
                        <option id="femenino" value="Femenino">Femenino</option>
                      </select>`;
  asis.innerHTML = `<input type="number" size="5" style="width: 80%; text-align: center;" min="0" onchange="calcularAsistencia(); calcularInasistencia(); calcularAsisMedi(); calcularPorAsis()">`;
  inas.innerHTML = `<input type="number" size="5" style="width: 80%; text-align: center;" min="0" onchange="calcularAsistencia(); calcularInasistencia(); calcularAsisMedi(); calcularPorAsis()">`;
  btn.innerHTML = "<button onclick='eliminarFila(this.parentNode.parentNode)'><i class='fa-solid fa-circle-xmark' style='color: red; font-size: 20px;'></i></button>";

  //Llamar a las funciones de cálculo
  calcularAsistencia();
  calcularInasistencia();
}

//Funcion para actualizar numeracion
function actualizarNumeracion() {
  let rows = document.querySelectorAll("#id_tabla1 tr:not(:first-child)");
  rowNum = 0;
  rows.forEach((row, index) => {
    row.cells[0].innerHTML = index + 1;
    rowNum++;
  });
}

//Funcion para eliminar filas y llamar a las funciones de calculos
function eliminarFila(row) {
  row.remove();
  actualizarNumeracion();
  calcularAsistencia();
  calcularInasistencia();
  calcularAsisMedi();
  calcularPorAsis();
}

//Funcion para vaciar la tabla y llamar a las funciones de calculos
function limpiarTabla() {
  let table1 = document.getElementById('id_tabla1');
  let rows1 = table1.getElementsByTagName('tr');

  for (let i = 0; i < rows1.length; i++) {
    let cells1 = rows1[i].getElementsByTagName('td');

    for (let j = 0; j < cells1.length; j++) {
      let cell1 = cells1[j];

      if (cell1.getElementsByTagName('input').length > 0) {
        cell1.getElementsByTagName('input')[0].value = "";
      }
    }
  }

  let table2 = document.getElementById('id_tabla2');
  let inputs = table2.getElementsByTagName('input');

  for (let input of inputs) {
    input.value = "";
  }

  //Llamar a las funciones de cálculo 
  calcularAsistencia();
  calcularInasistencia();
  calcularAsisMedi();
  calcularPorAsis();
}

//Evento para actualizar los calculos de asistencia e inasistencia al modificar una celda
document.getElementById('id_tabla1').addEventListener('change', function () {
  calcularAsistencia();
  calcularInasistencia();
  calcularAsisMedi();
  calcularPorAsis();
});




//----------TABLA 2----------//
//Calculos de Asistencia
function calcularAsistencia() {
  let asistenciaMasc = 0;
  let asistenciaFem = 0;

  let rows = document.querySelectorAll("#id_tabla1 tr:not(:first-child)");

  rows.forEach((row) => {
    let selectSexo = row.cells[1].querySelector("select");
    let asistencia = parseInt(row.cells[2].querySelector("input").value);

    if (selectSexo.value === "Masculino") {
      asistenciaMasc += asistencia;
    } else if (selectSexo.value === "Femenino") {
      asistenciaFem += asistencia;
    }
  });

  document.getElementById('id_asi_var').innerHTML = asistenciaMasc;
  document.getElementById('id_asi_muj').innerHTML = asistenciaFem;
  document.getElementById('id_asi_tot').innerHTML = asistenciaMasc + asistenciaFem;
}

//Calculos de Inasistencia
function calcularInasistencia() {
  let inasistenciaMasc = 0;
  let inasistenciaFem = 0;

  let rows = document.querySelectorAll("#id_tabla1 tr:not(:first-child)");

  rows.forEach((row) => {
    let selectSexo = row.cells[1].querySelector("select");
    let inasistencia = parseInt(row.cells[3].querySelector("input").value);

    if (selectSexo.value === "Masculino") {
      inasistenciaMasc += inasistencia;
    } else if (selectSexo.value === "Femenino") {
      inasistenciaFem += inasistencia;
    }
  });

  document.getElementById('id_ina_var').innerHTML = inasistenciaMasc;
  document.getElementById('id_ina_muj').innerHTML = inasistenciaFem;
  document.getElementById('id_ina_tot').innerHTML = inasistenciaMasc + inasistenciaFem;
}

//Calculos de Asistencia Media
function calcularAsisMedi() {
  let asistenciaMasc = 0;
  let asistenciaFem = 0;
  let asismediaMasc = 0;
  let asismediaFem = 0;
  let diasHabiles = parseInt(document.getElementById('id_dias_habiles').value);

  let rows = document.querySelectorAll("#id_tabla1 tr:not(:first-child)");

  rows.forEach((row) => {
    let selectSexo = row.cells[1].querySelector("select");
    let asistencia = parseInt(row.cells[2].querySelector("input").value);

    if (selectSexo.value === "Masculino") {
      asistenciaMasc += asistencia;
    } else if (selectSexo.value === "Femenino") {
      asistenciaFem += asistencia;
    }
  });

  asismediaMasc = asistenciaMasc / diasHabiles;
  asismediaFem = asistenciaFem / diasHabiles;
  let asismediaTotal = (asistenciaMasc + asistenciaFem) / diasHabiles;

  document.getElementById('id_asi_var').innerHTML = asistenciaMasc;
  document.getElementById('id_asi_muj').innerHTML = asistenciaFem;
  document.getElementById('id_asi_tot').innerHTML = asistenciaMasc + asistenciaFem;

  document.getElementById('id_asi_med_var').innerHTML = Math.round(asismediaMasc);
  document.getElementById('id_asi_med_muj').innerHTML = Math.round(asismediaFem);
  document.getElementById('id_asi_med_tot').innerHTML = Math.round(asismediaTotal);
}

//Calculos de Porcentaje de Asistencia
function calcularPorAsis() {
  let asistenciaMasc = 0;
  let asistenciaFem = 0;
  let inasistenciaMasc = 0;
  let inasistenciaFem = 0;
  let porasiMasc = 0;
  let porasiFem = 0;

  let rows = document.querySelectorAll("#id_tabla1 tr:not(:first-child)");

  rows.forEach((row) => {
    let selectSexo = row.cells[1].querySelector("select");
    let asistencia = parseInt(row.cells[2].querySelector("input").value);
    let inasistencia = parseInt(row.cells[3].querySelector("input").value);

    if (selectSexo.value === "Masculino") {
      asistenciaMasc += asistencia;
    } else if (selectSexo.value === "Femenino") {
      asistenciaFem += asistencia;
    }

    if (selectSexo.value === "Masculino") {
      inasistenciaMasc += inasistencia;
    } else if (selectSexo.value === "Femenino") {
      inasistenciaFem += inasistencia;
    }
  });

  porasiMasc = (asistenciaMasc * 100) / (asistenciaMasc + inasistenciaMasc);
  porasiFem = (asistenciaFem * 100) / (asistenciaFem + inasistenciaFem);
  let porasiTotal = ((asistenciaMasc + asistenciaFem) * 100) / (asistenciaMasc + asistenciaFem + inasistenciaMasc + inasistenciaFem);

  document.getElementById('id_asi_var').innerHTML = asistenciaMasc;
  document.getElementById('id_asi_muj').innerHTML = asistenciaFem;
  document.getElementById('id_asi_tot').innerHTML = asistenciaMasc + asistenciaFem;

  document.getElementById('id_ina_var').innerHTML = inasistenciaMasc;
  document.getElementById('id_ina_muj').innerHTML = inasistenciaFem;
  document.getElementById('id_ina_tot').innerHTML = inasistenciaMasc + inasistenciaFem;

  document.getElementById('id_por_var').innerHTML = Math.round(porasiMasc) + "%";
  document.getElementById('id_por_muj').innerHTML = Math.round(porasiFem) + "%";
  document.getElementById('id_por_tot').innerHTML = Math.round(porasiTotal) + "%";
}

//VALIDACION DE INPUTS Y SELECT
document.addEventListener("focusout" || "click", function (e) { 
  if (e.target.tagName === "INPUT" || e.target.tagName === "SELECT") { 
    if (e.target.value === "" || e.target.selectedIndex === 0) { 
      e.target.style.border = "1px solid red"; 
    } else { 
      e.target.style.border = "1px solid white"; 
    } 
  } 
});