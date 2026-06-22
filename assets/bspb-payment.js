/**
 * Фронтенд-логика виджета оплаты BSPB.
 * Отправляет в admin-ajax только индекс выбранного варианта — цена берётся
 * на сервере из настроек виджета (защита от подмены суммы).
 */
(function () {
    'use strict';

    function init(widget) {
        var button = widget.querySelector('.bspb-pay-button');
        var message = widget.querySelector('.bspb-message');
        var emailField = widget.querySelector('.bspb-email');
        if (!button) {
            return;
        }

        // Подсветка выбранного варианта.
        widget.addEventListener('change', function (e) {
            if (e.target && e.target.matches('input[type=radio]')) {
                widget.querySelectorAll('.bspb-option').forEach(function (opt) {
                    opt.classList.remove('is-selected');
                });
                var label = e.target.closest('.bspb-option');
                if (label) {
                    label.classList.add('is-selected');
                }
            }
        });

        button.addEventListener('click', function () {
            var checked = widget.querySelector('input[type=radio]:checked');
            if (!checked) {
                showMessage(message, BSPB_PAYMENT.i18n.choose, true);
                return;
            }

            var email = emailField ? emailField.value.trim() : '';
            if (emailField && email !== '' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                showMessage(message, BSPB_PAYMENT.i18n.email, true);
                return;
            }

            var params = new URLSearchParams();
            params.append('action', 'bspb_create_payment');
            params.append('nonce', BSPB_PAYMENT.nonce);
            params.append('post_id', widget.getAttribute('data-post-id'));
            params.append('widget_id', widget.getAttribute('data-widget-id'));
            params.append('option_index', checked.value);
            params.append('email', email);

            button.disabled = true;
            showMessage(message, BSPB_PAYMENT.i18n.wait, false);

            fetch(BSPB_PAYMENT.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.success && res.data && res.data.url) {
                        window.location.href = res.data.url;
                    } else {
                        var msg = (res && res.data && res.data.message) || BSPB_PAYMENT.i18n.error;
                        if (res && res.data && res.data.debug) {
                            msg += ' [' + res.data.debug + ']';
                        }
                        showMessage(message, msg, true);
                        button.disabled = false;
                    }
                })
                .catch(function () {
                    showMessage(message, BSPB_PAYMENT.i18n.error, true);
                    button.disabled = false;
                });
        });
    }

    function showMessage(el, text, isError) {
        if (!el) {
            return;
        }
        el.textContent = text;
        el.classList.toggle('is-error', !!isError);
    }

    function initAll() {
        document.querySelectorAll('.bspb-payment-widget').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Поддержка перерисовки в редакторе Elementor.
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend) {
                elementorFrontend.hooks.addAction('frontend/element_ready/bspb_payment.default', function ($scope) {
                    var node = ($scope && $scope[0]) ? $scope[0] : $scope;
                    var widget = node.querySelector ? node.querySelector('.bspb-payment-widget') : null;
                    init(widget || node);
                });
            }
        });
    }
})();
