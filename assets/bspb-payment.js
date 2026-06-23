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
        var phoneField = widget.querySelector('.bspb-phone');
        var selectField = widget.querySelector('.bspb-select');
        if (!button) {
            return;
        }

        if (phoneField) {
            applyPhoneMask(phoneField);
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

            // Доп. поле-список.
            var selectIndex = '';
            if (selectField) {
                selectIndex = selectField.value;
                if (selectField.getAttribute('data-required') === '1' && selectIndex === '') {
                    showMessage(message, BSPB_PAYMENT.i18n.select, true);
                    return;
                }
            }

            // Телефон.
            var phone = phoneField ? phoneField.value.trim() : '';
            if (phoneField) {
                var phoneRequired = phoneField.getAttribute('data-required') === '1';
                var phoneDigits = phone.replace(/\D/g, '');
                if (phoneRequired && phoneDigits === '') {
                    showMessage(message, BSPB_PAYMENT.i18n.phone, true);
                    return;
                }
                // Маска требует полный номер: +7 (XXX) XXX-XX-XX = 11 цифр.
                if (phoneDigits !== '' && phoneDigits.length !== 11) {
                    showMessage(message, BSPB_PAYMENT.i18n.phone, true);
                    return;
                }
            }

            var params = new URLSearchParams();
            params.append('action', 'bspb_create_payment');
            params.append('nonce', BSPB_PAYMENT.nonce);
            params.append('post_id', widget.getAttribute('data-post-id'));
            params.append('widget_id', widget.getAttribute('data-widget-id'));
            params.append('option_index', checked.value);
            params.append('email', email);
            params.append('phone', phone);
            params.append('select_index', selectIndex);

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

    // Маска российского номера: +7 (XXX) XXX-XX-XX.
    // Поле принимает только цифры, форматирование подставляется автоматически.
    function applyPhoneMask(field) {
        field.setAttribute('inputmode', 'tel');
        field.setAttribute('maxlength', '18');
        field.setAttribute('autocomplete', 'tel');

        function format(digits) {
            // Нормализуем ведущую цифру к 7 (8 -> 7), берём максимум 11 цифр.
            if (digits[0] === '8') {
                digits = '7' + digits.slice(1);
            }
            if (digits[0] !== '7') {
                digits = '7' + digits;
            }
            digits = digits.slice(0, 11);

            var rest = digits.slice(1);
            var out = '+7';
            if (rest.length > 0) {
                out += ' (' + rest.slice(0, 3);
            }
            if (rest.length >= 3) {
                out += ') ' + rest.slice(3, 6);
            }
            if (rest.length >= 6) {
                out += '-' + rest.slice(6, 8);
            }
            if (rest.length >= 8) {
                out += '-' + rest.slice(8, 10);
            }
            return out;
        }

        function onInput() {
            var digits = field.value.replace(/\D/g, '');
            field.value = digits === '' ? '' : format(digits);
        }

        field.addEventListener('input', onInput);
        field.addEventListener('focus', function () {
            if (field.value === '') {
                field.value = '+7 (';
            }
        });
        field.addEventListener('blur', function () {
            // Если пользователь ничего не ввёл сверх префикса — очищаем,
            // чтобы placeholder и валидация «обязательно» работали корректно.
            if (field.value.replace(/\D/g, '') === '7') {
                field.value = '';
            }
        });
        // Не даём курсору застрять перед префиксом «+7 (».
        field.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && field.value.replace(/\D/g, '').length <= 1) {
                field.value = '';
                e.preventDefault();
            }
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
