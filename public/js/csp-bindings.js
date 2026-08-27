// CSP-safe event bindings.
// Replaces inline onsubmit/onclick/onchange handler attributes (which the
// Content-Security-Policy blocks) with delegated listeners.
(function () {
    'use strict';

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-confirm]');
        if (form && !confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });

    document.addEventListener('change', function (e) {
        var target = e.target;
        if (target.classList.contains('js-auto-submit')) {
            target.form.submit();
        }
    });

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-print');
        if (trigger) {
            window.print();
            return;
        }

        trigger = e.target.closest('.js-logout-submit');
        if (trigger) {
            e.preventDefault();
            trigger.closest('form').submit();
            return;
        }

        trigger = e.target.closest('.js-logout-form');
        if (trigger) {
            e.preventDefault();
            var logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                logoutForm.submit();
            }
            return;
        }

        trigger = e.target.closest('[data-csp-target]');
        if (trigger) {
            var target = document.getElementById(trigger.getAttribute('data-csp-target'));
            if (target) {
                if (trigger.classList.contains('js-hide')) {
                    target.classList.add('hidden');
                } else {
                    target.classList.toggle('hidden');
                }
            }
        }
    });
})();