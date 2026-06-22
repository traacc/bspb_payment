<?php
/**
 * Plugin Name: BSPB Elementor Payment
 * Description: Виджет Elementor: radio-список вариантов (описание + цена) и кнопка оплаты через эквайринг Банка Санкт-Петербург.
 * Version:     1.0.0
 * Author:      —
 * Requires PHP: 7.1
 *
 * Зависит от minimalPayment.php (функция getPaymentLink) и каталога libs/BspbSDK.
 */

if (!defined('ABSPATH')) {
    exit; // Прямой доступ запрещён.
}

define('BSPB_EP_DIR', __DIR__);
define('BSPB_EP_URL', plugin_dir_url(__FILE__));

/* -------------------------------------------------------------------------
 * Подключаем функцию создания ссылки на оплату.
 * Внутри minimalPayment.php CLI-блок защищён проверкой PHP_SAPI, поэтому
 * подключение в контексте WordPress безопасно.
 * ---------------------------------------------------------------------- */
require_once BSPB_EP_DIR . '/minimalPayment.php';

/* -------------------------------------------------------------------------
 * Регистрация виджета Elementor.
 * ---------------------------------------------------------------------- */
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once BSPB_EP_DIR . '/widgets/class-bspb-payment-widget.php';
    $widgets_manager->register(new \Bspb_Payment_Widget());
});

/* -------------------------------------------------------------------------
 * Подключение скрипта на фронтенде + передача ajaxurl и nonce.
 * ---------------------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {
    wp_register_script(
        'bspb-payment',
        BSPB_EP_URL . 'assets/bspb-payment.js',
        [],
        '1.0.0',
        true
    );
    wp_enqueue_style(
        'bspb-payment',
        BSPB_EP_URL . 'assets/bspb-payment.css',
        [],
        '1.0.0'
    );
    wp_localize_script('bspb-payment', 'BSPB_PAYMENT', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bspb_payment'),
        'i18n'     => [
            'choose'  => __('Выберите вариант оплаты.', 'bspb'),
            'wait'    => __('Создаём ссылку на оплату…', 'bspb'),
            'error'   => __('Не удалось создать оплату. Попробуйте позже.', 'bspb'),
            'email'   => __('Введите корректный e-mail.', 'bspb'),
        ],
    ]);
});

/* -------------------------------------------------------------------------
 * Рекурсивный поиск настроек элемента Elementor по его id.
 * Нужен, чтобы взять ЦЕНУ из сохранённых на сервере данных, а не с клиента.
 * ---------------------------------------------------------------------- */
function bspb_ep_find_element_settings(array $elements, string $widget_id)
{
    foreach ($elements as $element) {
        if (isset($element['id']) && $element['id'] === $widget_id) {
            return isset($element['settings']) ? $element['settings'] : null;
        }
        if (!empty($element['elements']) && is_array($element['elements'])) {
            $found = bspb_ep_find_element_settings($element['elements'], $widget_id);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

/* -------------------------------------------------------------------------
 * AJAX-обработчик создания оплаты.
 * Цена берётся ТОЛЬКО из настроек виджета на сервере — клиент передаёт лишь
 * индекс выбранного варианта.
 * ---------------------------------------------------------------------- */
function bspb_ep_ajax_create_payment()
{
    check_ajax_referer('bspb_payment', 'nonce');

    $post_id   = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $widget_id = isset($_POST['widget_id']) ? sanitize_text_field(wp_unslash($_POST['widget_id'])) : '';
    $index     = isset($_POST['option_index']) ? absint($_POST['option_index']) : -1;
    $email     = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (!$post_id || $widget_id === '' || $index < 0) {
        wp_send_json_error(['message' => 'Некорректные параметры запроса.'], 400);
    }

    if (!class_exists('\Elementor\Plugin')) {
        wp_send_json_error(['message' => 'Elementor не активен.'], 500);
    }

    $document = \Elementor\Plugin::$instance->documents->get($post_id);
    if (!$document) {
        wp_send_json_error(['message' => 'Документ не найден.'], 404);
    }

    $settings = bspb_ep_find_element_settings($document->get_elements_data(), $widget_id);
    if ($settings === null || empty($settings['payment_options'][$index])) {
        wp_send_json_error(['message' => 'Вариант оплаты не найден.'], 404);
    }

    $option = $settings['payment_options'][$index];
    $price  = isset($option['option_price']) ? (float) $option['option_price'] : 0.0;
    $label  = isset($option['option_label']) ? sanitize_text_field($option['option_label']) : '';

    if ($price <= 0) {
        wp_send_json_error(['message' => 'Неверная сумма оплаты.'], 400);
    }

    // E-mail передаём в SDK только если он валиден.
    $email = is_email($email) ? $email : null;

    try {
        $url = getPaymentLink($price, $label !== '' ? $label : null, $email);
        wp_send_json_success(['url' => $url]);
    } catch (\Throwable $e) {
        // Подробности — в лог, пользователю — общее сообщение.
        if (function_exists('error_log')) {
            error_log('[BSPB Payment] ' . $e->getMessage());
        }
        wp_send_json_error(['message' => 'Ошибка создания оплаты.'], 500);
    }
}
add_action('wp_ajax_bspb_create_payment', 'bspb_ep_ajax_create_payment');
add_action('wp_ajax_nopriv_bspb_create_payment', 'bspb_ep_ajax_create_payment');
