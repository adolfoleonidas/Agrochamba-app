/**
 * AgroChamba - Applicants Metabox JS
 * Maneja las acciones de cambio de estado de postulantes con dropdown contextual
 */
(function($) {
    'use strict';

    var statusLabels = {
        'en_proceso': 'EN PROCESO',
        'entrevista': 'ENTREVISTA',
        'finalista': 'FINALISTA',
        'aceptado': 'CONTRATADO',
        'rechazado': 'NO SELECCIONADO',
        'visto': 'CV VISTO'
    };

    $(document).ready(function() {
        // Toggle dropdown
        $(document).on('click', '.agro-metabox-dropdown__toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $dropdown = $(this).closest('.agro-metabox-dropdown');
            $('.agro-metabox-dropdown').not($dropdown).removeClass('open');
            $dropdown.toggleClass('open');
        });

        // Close dropdowns on outside click
        $(document).on('click', function() {
            $('.agro-metabox-dropdown').removeClass('open');
        });

        // Handle dropdown item click
        $('.agrochamba-applicants-table').on('click', '.agro-metabox-dropdown__item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var $row = $btn.closest('.applicant-row');
            var userId = $row.data('user-id');
            var newStatus = $btn.data('action');
            var label = statusLabels[newStatus] || newStatus;

            if (!confirm('¿Cambiar estado a ' + label + '?')) {
                return;
            }

            $btn.prop('disabled', true).text('...');
            $('.agro-metabox-dropdown').removeClass('open');

            $.ajax({
                url: agrochambaApplicants.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'agrochamba_update_applicant_status',
                    nonce: agrochambaApplicants.nonce,
                    job_id: agrochambaApplicants.jobId,
                    user_id: userId,
                    status_action: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        $row.attr('class', 'applicant-row status-' + response.data.status);
                        $row.find('.applicant-status').html(response.data.badge);
                        if (response.data.actions_html) {
                            $row.find('.applicant-actions').html(response.data.actions_html);
                        } else {
                            $row.find('.applicant-actions').html('<span class="action-done">--</span>');
                        }
                    } else {
                        alert(response.data || agrochambaApplicants.strings.error);
                    }
                },
                error: function() {
                    alert(agrochambaApplicants.strings.error);
                }
            });
        });
    });
})(jQuery);
