(function ($) {
    'use strict';

    $('[data-auto-submit="1"]').on('change', function () {
        this.form.submit();
    });

    $(document).on('submit', 'form[data-confirm]', function (event) {
        var message = $(this).data('confirm') || '¿Confirmar acción?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
})(jQuery);
