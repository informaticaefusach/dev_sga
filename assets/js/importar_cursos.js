document.addEventListener("DOMContentLoaded", function () {

    var form = document.getElementById("importForm");
    var result = document.getElementById("importResult");

    if (!form || !result) return;

    var alertBox = result.querySelector(".alert");
    var previewHead = document.querySelector("#previewTable thead");
    var previewBody = document.querySelector("#previewTable tbody");

    form.addEventListener("submit", function (e) {

        e.preventDefault();

        var formData = new FormData(form);

        fetch(form.action, {
            method: "POST",
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                result.classList.remove("d-none");

                alertBox.className = "alert";
                alertBox.classList.add(data.ok ? "alert-success" : "alert-danger");

                alertBox.textContent = data.message ||
                    (data.ok ? "Importación realizada correctamente" : "Error al importar");

                if (data.preview && previewHead && previewBody) {

                    previewHead.innerHTML = "";
                    previewBody.innerHTML = "";

                    /* HEADERS */

                    if (data.preview.headers && data.preview.headers.length) {

                        var tr = document.createElement("tr");

                        data.preview.headers.forEach(function (header) {

                            var th = document.createElement("th");
                            th.textContent = header;

                            tr.appendChild(th);

                        });

                        previewHead.appendChild(tr);

                    }

                    /* ROWS */

                    if (data.preview.rows && data.preview.rows.length) {

                        data.preview.rows.forEach(function (row) {

                            var tr = document.createElement("tr");

                            row.forEach(function (cell) {

                                var td = document.createElement("td");
                                td.textContent = cell;

                                tr.appendChild(td);

                            });

                            previewBody.appendChild(tr);

                        });

                    }

                }

            })
            .catch(function () {

                result.classList.remove("d-none");

                alertBox.className = "alert alert-danger";
                alertBox.textContent = "No fue posible procesar el archivo.";

            });

    });

});