//DECLARO LAS VARIABLES PARA INCREMENTAR LAS FILAS Y QUE SALGAN AL FINAL DE LA TABLA
var total = 1;
var row = 1;

//FUNCION PARA AGREGAR FILAS A LA TABLA
function insertarFila() {
    let id_tabla1 = document.getElementById('id_tabla1').insertRow(letra);
    let num = id_tabla1.insertCell(0);
    let sexo = id_tabla1.insertCell(1);
    let asis = id_tabla1.insertCell(2);
    let inas = id_tabla1.insertCell(3);

    //INCREMENTAR EN 1 EL LUGAR DONDE SALEN LAS NUEVAS FILAS
    ++row

    //ESTRUCTURAS DE LAS FILAS
    num.innerHTML = ++total;
    sexo.innerHTML = `<select required>
                        <option value="">Seleccione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                      </select>`;
    asis.innerHTML = () => {
      for(i=0; i){
        `<input type="number" size="5" style="width: 80%; text-align: center;" min="0">`;
      }
      

    };
    inas.innerHTML = `<input type="number" size="5" style="width: 80%; text-align: center;" min="0">`;
}

//DECLARO VARIABLES PARA LOS CALCULOS
var asi_var = 0;
var asi_muj = 0;
var asi_tot = 0;
var ina_var = 0;
var ina_muj = 0;
var ina_tot = 0;
var asi_med_var = 0;
var asi_med_muj = 0;
var asi_med_tot = 0;
var por_var = 0;
var por_muj = 0;
var por_tot = 0;

