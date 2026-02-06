/**
 * AgroChamba - Applicants Metabox JS
 * Maneja las acciones de aceptar/rechazar postulantes
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Manejar clic en botones de acción
        $('.agrochamba-applicants-table').on('click', '.accept-btn, .reject-btn', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $row = $btn.closest('.applicant-row');
            var userId = $row.data('user-id');
            var action = $btn.data('action');
            var confirmMsg = action === 'accept'
                ? agrochambaApplicants.strings.confirmAccept
                : agrochambaApplicants.strings.confirmReject;

            if (!confirm(confirmMsg)) {
                return;
            }

            // Deshabilitar botones
            $row.find('.button').prop('disabled', true);
            $btn.text(agrochambaApplicants.strings.updating);

            $.ajax({
                url: agrochambaApplicants.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'agrochamba_update_applicant_status',
                    nonce: agrochambaApplicants.nonce,
                    job_id: agrochambaApplicants.jobId,
                    user_id: userId,
                    status_action: action
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar UI
                        $row.removeClass('status-pendiente status-visto')
                            .addClass('status-' + response.data.status);
                        $row.find('.applicant-status').html(response.data.badge);
                        $row.find('.applicant-actions').html('<span class="action-done">✓</span>');

                        // Actualizar estadísticas
                        updateStats(action);
                    } else {
                        alert(response.data || agrochambaApplicants.strings.error);
                        $row.find('.button').prop('disabled', false);
                        resetButton($btn, action);
                    }
                },
                error: function() {
                    alert(agrochambaApplicants.strings.error);
                    $row.find('.button').prop('disabled', false);
                    resetButton($btn, action);
                }
            });
        });

        function resetButton($btn, action) {
            var icon = action === 'accept' ? 'yes' : 'no';
            $btn.html('<span class="dashicons dashicons-' + icon + '"></span>');
        }

        function updateStats(action) {
            // Actualizar contadores
            var $pending = $('.stat-pending .stat-number');
            var $viewed = $('.stat-viewed .stat-number');
            var $target = action === 'accept' ? $('.stat-accepted .stat-number') : $('.stat-rejected .stat-number');

            var pendingCount = parseInt($pending.text()) || 0;
            var viewedCount = parseInt($viewed.text()) || 0;
            var targetCount = parseInt($target.text()) || 0;

            // Reducir de pendiente o visto
            if (pendingCount > 0) {
                $pending.text(pendingCount - 1);
            } else if (viewedCount > 0) {
                $viewed.text(viewedCount - 1);
            }

            // Aumentar aceptado o rechazado
            $target.text(targetCount + 1);
        }
    });
})(jQuery);
