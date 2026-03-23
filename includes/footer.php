    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables Core + Bootstrap 5 -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
            if($('#sidebar').hasClass('active')) {
                $('#sidebar').css('margin-left', '-250px');
            } else {
                $('#sidebar').css('margin-left', '0');
            }
        });
        $('table.datatable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [],
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }
        });
        function ensureDiagnosticModal() {
            if (!$('#diagnosticModal').length) {
                var modalHtml = '' +
                  '<div class="modal fade" id="diagnosticModal" tabindex="-1" aria-hidden="true">' +
                  '  <div class="modal-dialog modal-lg">' +
                  '    <div class="modal-content">' +
                  '      <div class="modal-header">' +
                  '        <h5 class="modal-title">Diagnóstico de correo</h5>' +
                  '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
                  '      </div>' +
                  '      <div class="modal-body">' +
                  '        <div class="mb-3">' +
                  '          <label class="form-label">Resultado</label>' +
                  '          <textarea class="form-control" id="diagnosticResult" rows="8" readonly></textarea>' +
                  '        </div>' +
                  '        <div id="diagnosticStatus" class="small text-muted"></div>' +
                  '      </div>' +
                  '      <div class="modal-footer">' +
                  '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>' +
                  '      </div>' +
                  '    </div>' +
                  '  </div>' +
                  '</div>';
                $('body').append(modalHtml);
            }
        }
        $(document).on('click', '.btn-diagnostic', function (e) {
            e.preventDefault();
            ensureDiagnosticModal();
            var $btn = $(this);
            var $row = $btn.closest('tr');
            var $table = $btn.closest('table');
            var mode = ($table.data('mode') || '').toString();
            var id = $btn.data('id');
            var email = $btn.data('email');
            var modalEl = document.getElementById('diagnosticModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            $('#diagnosticResult').val('Procesando diagnóstico para ' + (email || 'contacto') + ' ...');
            $('#diagnosticStatus').text('');
            modal.show();
            var endpoint = (window.location.pathname.indexOf('/views/') !== -1) ? 'diagnostico.php' : 'views/diagnostico.php';
            $.ajax({
                url: endpoint,
                method: 'POST',
                data: { id: id },
                dataType: 'json'
            }).done(function (resp) {
                if (resp && resp.need_confirm) {
                    var msg = 'Se encontraron ' + (resp.count || 0) + ' contactos con el dominio @' + (resp.domain || '') + '. ¿Validar todos con el mismo resultado?';
                    var applyAll = window.confirm(msg) ? 1 : 0;
                    $.ajax({
                        url: endpoint,
                        method: 'POST',
                        data: { id: id, apply_all: applyAll },
                        dataType: 'json'
                    }).done(function (resp2) {
                        if (resp2 && resp2.ok) {
                            var details = resp2.details || '';
                            try { if (typeof details === 'string') { details = JSON.parse(details); } if (typeof details === 'object') { details = JSON.stringify(details, null, 2); } } catch (e) {}
                            $('#diagnosticResult').val(details);
                            $('#diagnosticStatus').text('Estado: ' + (resp2.status || '') + ' • Aplicado a: ' + (resp2.applied_to || ''));
                            if ($row && $row.length) {
                                $row.removeClass('row-dx-valid row-dx-invalid');
                                if (resp2.status === 'valido') { $row.addClass('row-dx-valid'); }
                                else if (resp2.status === 'invalido') { $row.addClass('row-dx-invalid'); }
                                if (mode === 'pending' && (resp2.status === 'valido' || resp2.status === 'invalido')) {
                                    try { var dt = $table.DataTable(); dt.row($row).remove().draw(false); } catch (e) { $row.remove(); }
                                }
                            }
                        } else {
                            $('#diagnosticResult').val('No fue posible completar el diagnóstico.');
                            $('#diagnosticStatus').text('');
                        }
                    }).fail(function () {
                        $('#diagnosticResult').val('Error al consultar el servicio de diagnóstico.');
                        $('#diagnosticStatus').text('');
                    });
                } else if (resp && resp.ok) {
                    var details = resp.details || '';
                    try {
                        if (typeof details === 'string') {
                            details = JSON.parse(details);
                        }
                        if (typeof details === 'object') {
                            details = JSON.stringify(details, null, 2);
                        }
                    } catch (e) {
                        // dejar como string si no es JSON válido
                    }
                    $('#diagnosticResult').val(details);
                    $('#diagnosticStatus').text('Estado: ' + (resp.status || ''));
                    if ($row && $row.length) {
                        $row.removeClass('row-dx-valid row-dx-invalid');
                        if (resp.status === 'valido') {
                            $row.addClass('row-dx-valid');
                        } else if (resp.status === 'invalido') {
                            $row.addClass('row-dx-invalid');
                        }
                        if (mode === 'pending' && (resp.status === 'valido' || resp.status === 'invalido')) {
                            try {
                                var dt = $table.DataTable();
                                dt.row($row).remove().draw(false);
                            } catch (e) {
                                $row.remove();
                            }
                        }
                    }
                } else {
                    $('#diagnosticResult').val('No fue posible completar el diagnóstico.');
                    $('#diagnosticStatus').text('');
                }
            }).fail(function () {
                $('#diagnosticResult').val('Error al consultar el servicio de diagnóstico.');
                $('#diagnosticStatus').text('');
            });
        });
        $(document).on('click', '.btn-view-diagnostic', function (e) {
            e.preventDefault();
            ensureDiagnosticModal();
            var details = $(this).data('details') || '';
            var status = $(this).data('status') || '';
            var modalEl = document.getElementById('diagnosticModal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            try {
                var parsed = typeof details === 'string' ? JSON.parse(details) : details;
                if (typeof parsed === 'object') {
                    details = JSON.stringify(parsed, null, 2);
                }
            } catch (e) {
                // dejar como está si no es JSON
            }
            $('#diagnosticResult').val(details);
            $('#diagnosticStatus').text('Estado: ' + status);
            modal.show();
        });
    });
</script>

</body>
</html>
