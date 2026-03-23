/* =========================================
ESPERAR A QUE CARGUE EL DOM
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    console.log("JS cargado correctamente");

});


/* =========================================
FUNCION GENERICA PARA AGREGAR CAMPOS
========================================= */

function agregarCampo(containerId, inputName) {

    let cont = document.getElementById(containerId);

    if (!cont) {
        console.error("No existe contenedor:", containerId);
        return;
    }

    let row = document.createElement("div");
    row.className = "d-flex mb-2 gap-2";

    let input = document.createElement("input");
    input.type = "text";
    input.name = inputName;
    input.className = "form-control";

    let btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger";
    btn.innerHTML = "✖";

    btn.onclick = function () {
        row.remove();
    };

    row.appendChild(input);
    row.appendChild(btn);

    cont.appendChild(row);
}


/* =========================================
UNIDADES + CONTENIDOS
========================================= */

/* 🔥 IMPORTANTE: NO usar variable global fija */
function getUnidadIndex() {
    return document.querySelectorAll('.unidad-item').length;
}


/* =============================
AGREGAR UNIDAD
============================= */

function agregarUnidad() {

    let container = document.getElementById('unidades-container');

    if (!container) {
        alert("ERROR: no existe #unidades-container");
        console.error("No existe contenedor");
        return;
    }

    let index = document.querySelectorAll('.unidad-item').length;

    let div = document.createElement("div");
    div.className = "card mb-3 p-3 unidad-item";

    div.innerHTML = `
        <div class="d-flex justify-content-between mb-2">
            <strong>Unidad ${index + 1}</strong>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.unidad-item').remove()">✖</button>
        </div>

        <input name="unidad_titulo[]" class="form-control mb-2" placeholder="Título">

        <textarea name="unidad_objetivo[]" class="form-control mb-2" placeholder="Objetivo"></textarea>

        <div class="row mb-2">
            <div class="col"><input name="horas_teoricas[]" class="form-control" placeholder="HT"></div>
            <div class="col"><input name="horas_practicas[]" class="form-control" placeholder="HP"></div>
            <div class="col"><input name="modalidad_unidad[]" class="form-control" placeholder="Modalidad"></div>
        </div>

        <div id="contenidos-${index}"></div>

        <button type="button" class="btn btn-secondary btn-sm mt-2"
            onclick="agregarContenido(${index})">
            + Contenido
        </button>
    `;

    container.appendChild(div);
}


/* =============================
ELIMINAR UNIDAD
============================= */

function eliminarUnidad(btn) {

    let unidad = btn.closest('.unidad-item');

    if (unidad) {
        unidad.remove();
    }
}


/* =============================
AGREGAR CONTENIDO
============================= */

function agregarContenido(index) {

    let container = document.getElementById('contenidos-' + index);

    if (!container) {
        console.error("No existe contenedor de contenidos:", index);
        return;
    }

    let html = `
    <div class="d-flex mb-2 contenido-item gap-2">

        <input name="contenidos[${index}][]" 
               class="form-control"
               placeholder="Contenido">

        <button type="button" class="btn btn-danger btn-sm"
            onclick="this.parentElement.remove()">
            ✖
        </button>

    </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}

function mostrarInputPDF() {
    document.getElementById('pdfInput').click();
}

function importarPDF() {

    let file = document.getElementById('pdfInput').files[0];

    if (!file) return;

    let formData = new FormData();
    formData.append('pdf', file);

    fetch('views/importar_pdf.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            console.log("DATA PDF:", data); // 👈 IMPORTANTE

            if (data.error) {
                alert(data.error);
                return;
            }

            /* CONTEXTO */
            document.querySelector('[name="curso_contexto"]').value = data.contexto;

            /* OBJETIVO */
            document.querySelector('[name="curso_objetivo_general"]').value = data.objetivo;

            /* PERFIL EGRESO */
            let contPerfil = document.getElementById("perfil-egreso-container");
            contPerfil.innerHTML = '';

            data.perfil.forEach(item => {
                let input = document.createElement("input");
                input.name = "perfil_egreso[]";
                input.className = "form-control mb-2";
                input.value = item;

                contPerfil.appendChild(input);
            });

            /* REQUISITOS PREVIOS */
            let contReq = document.getElementById("requisitos-previos-container");
            contReq.innerHTML = '';

            data.requisitos.forEach(item => {
                let input = document.createElement("input");
                input.name = "requisitos_previos[]";
                input.className = "form-control mb-2";
                input.value = item;

                contReq.appendChild(input);
            });

            alert("✅ PDF importado correctamente");

        })
        .catch(err => {
            console.error(err);
            alert("Error al procesar PDF");
        });
}