function ingresarRB() {
    if (document.getElementById('directorid').checked) {
        //alert("Director seleccionado");
        location.href = "director.html";
    }

    if (document.getElementById('docenteid').checked) {
        //alert("Docente seleccionado");
        location.href = "docente-registro.html";
    }
}