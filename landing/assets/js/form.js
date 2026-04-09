document.addEventListener('DOMContentLoaded', function () {
    console.log("Landing de curso cargada");
    const form = document.getElementById('leadForm');
    const messageBox = document.getElementById('leadFormMessage');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        messageBox.innerHTML = '<div class="alert alert-info">Enviando...</div>';

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                messageBox.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                form.reset();
            } else {
                messageBox.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        } catch (error) {
            messageBox.innerHTML = '<div class="alert alert-danger">Error al enviar el formulario.</div>';
        }
    });
});