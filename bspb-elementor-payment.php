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
 * Админ-страница настроек подключения к банку.
 * Переопределяет дефолты конфига через фильтр 'bspb_config'.
 * ---------------------------------------------------------------------- */
require_once BSPB_EP_DIR . '/admin/class-bspb-settings.php';
Bspb_Settings::init();

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
            'phone'   => __('Введите корректный телефон.', 'bspb'),
            'select'  => __('Выберите значение в списке.', 'bspb'),
        ],
    ]);
});

/* -------------------------------------------------------------------------
 * Рекурсивный поиск данных элемента Elementor по его id.
 * Возвращаем весь узел (а не только settings), чтобы затем создать экземпляр
 * элемента и получить настройки С УЧЁТОМ дефолтов контролов.
 * ---------------------------------------------------------------------- */
function bspb_ep_find_element(array $elements, string $widget_id)
{
    foreach ($elements as $element) {
        if (isset($element['id']) && $element['id'] === $widget_id) {
            return $element;
        }
        if (!empty($element['elements']) && is_array($element['elements'])) {
            $found = bspb_ep_find_element($element['elements'], $widget_id);
            if ($found !== null) {
                return $found;
            }
        }
    }
    return null;
}

/* -------------------------------------------------------------------------
 * Хранилище прайс-листа виджета (index => [price, label]) в transient.
 * Заполняется при render() виджета; читается AJAX-обработчиком. Не зависит от
 * расположения виджета в дереве Elementor.
 * ---------------------------------------------------------------------- */
function bspb_ep_options_key(string $widget_id): string
{
    return 'bspb_ep_opts_' . $widget_id;
}

function bspb_ep_store_options(string $widget_id, array $options, array $extra = []): void
{
    $opts = [];
    foreach ($options as $i => $opt) {
        $opts[$i] = [
            'price' => isset($opt['option_price']) ? (float) $opt['option_price'] : 0.0,
            'label' => isset($opt['option_label']) ? (string) $opt['option_label'] : '',
        ];
    }

    $payload = [
        'options'       => $opts,
        'select_label'  => isset($extra['select_label']) ? (string) $extra['select_label'] : '',
        'select_values' => isset($extra['select_values']) && is_array($extra['select_values'])
            ? array_values($extra['select_values'])
            : [],
    ];

    // Месяц жизни; перезаписывается при каждом рендере виджета.
    set_transient(bspb_ep_options_key($widget_id), $payload, MONTH_IN_SECONDS);
}

function bspb_ep_get_stored_payload(string $widget_id)
{
    $payload = get_transient(bspb_ep_options_key($widget_id));
    if (!is_array($payload) || !isset($payload['options'])) {
        return null;
    }
    return $payload;
}

/* -------------------------------------------------------------------------
 * Ищет данные элемента по widget_id среди нескольких документов-кандидатов.
 * Возвращает [element_data, post_id] либо null. $reason заполняется причиной.
 * ---------------------------------------------------------------------- */
function bspb_ep_locate_element(int $post_id, string $widget_id, &$reason = '')
{
    $documents = \Elementor\Plugin::$instance->documents;

    // Кандидаты: переданный post_id + текущий документ (на случай Theme Builder/loop).
    $candidate_ids = array_unique(array_filter([
        $post_id,
        (int) get_queried_object_id(),
        (int) get_the_ID(),
    ]));

    foreach ($candidate_ids as $cid) {
        $document = $documents->get($cid);
        if (!$document) {
            continue;
        }
        $element_data = bspb_ep_find_element($document->get_elements_data(), $widget_id);
        if ($element_data !== null) {
            return [$element_data, $cid];
        }
    }

    $reason = 'element_not_found (искали в документах: ' . implode(',', $candidate_ids) . ')';
    return null;
}

/* -------------------------------------------------------------------------
 * Возвращает настройки виджета по post_id + widget_id с учётом дефолтов.
 * При неудаче $reason содержит причину (для диагностики).
 * ---------------------------------------------------------------------- */
function bspb_ep_get_widget_settings(int $post_id, string $widget_id, &$reason = '')
{
    $located = bspb_ep_locate_element($post_id, $widget_id, $reason);
    if ($located === null) {
        return null;
    }

    list($element_data) = $located;

    // create_element_instance сливает сохранённые значения с дефолтами контролов
    // и попутно (лениво) регистрирует виджет, если он ещё не зарегистрирован.
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance($element_data);
    if (!$element) {
        $reason = 'create_element_instance вернул null (widgetType='
            . (isset($element_data['widgetType']) ? $element_data['widgetType'] : '?') . ')';
        return null;
    }

    $settings = $element->get_settings_for_display();
    if (empty($settings['payment_options'])) {
        $reason = 'payment_options пуст; ключи settings: ' . implode(',', array_keys((array) $settings));
        return null;
    }

    return $settings;
}

/* -------------------------------------------------------------------------
 * AJAX-обработчик создания оплаты.
 * Цена берётся ТОЛЬКО из настроек виджета на сервере — клиент передаёт лишь
 * индекс выбранного варианта.
 * ---------------------------------------------------------------------- */
function bspb_ep_ajax_create_payment()
{
    check_ajax_referer('bspb_payment', 'nonce');

    $post_id      = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $widget_id    = isset($_POST['widget_id']) ? sanitize_text_field(wp_unslash($_POST['widget_id'])) : '';
    $index        = isset($_POST['option_index']) ? absint($_POST['option_index']) : -1;
    $email        = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone        = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $select_index = (isset($_POST['select_index']) && $_POST['select_index'] !== '')
        ? absint($_POST['select_index']) : -1;

    if (!$post_id || $widget_id === '' || $index < 0) {
        wp_send_json_error(['message' => 'Некорректные параметры запроса.'], 400);
    }

    if (!class_exists('\Elementor\Plugin')) {
        wp_send_json_error(['message' => 'Elementor не активен.'], 500);
    }

    $option        = null;
    $select_label  = '';
    $select_values = [];
    $reason        = '';

    // 1) Основной путь: прайс-лист, сохранённый виджетом при рендере.
    $payload = bspb_ep_get_stored_payload($widget_id);
    if ($payload !== null && isset($payload['options'][$index])) {
        $option        = $payload['options'][$index];
        $select_label  = $payload['select_label'];
        $select_values = $payload['select_values'];
    } else {
        // 2) Фолбэк: перечитать настройки виджета из дерева Elementor.
        $settings = bspb_ep_get_widget_settings($post_id, $widget_id, $reason);
        if ($settings !== null && isset($settings['payment_options'][$index])) {
            $raw = $settings['payment_options'][$index];
            $option = [
                'price' => isset($raw['option_price']) ? (float) $raw['option_price'] : 0.0,
                'label' => isset($raw['option_label']) ? (string) $raw['option_label'] : '',
            ];
            $select_label = isset($settings['select_label']) ? (string) $settings['select_label'] : '';
            if (!empty($settings['enable_select']) && $settings['enable_select'] === 'yes'
                && !empty($settings['select_options'])) {
                foreach ($settings['select_options'] as $so) {
                    $select_values[] = isset($so['select_option_label']) ? (string) $so['select_option_label'] : '';
                }
            }
        } elseif ($reason === '') {
            $reason = $payload === null
                ? 'transient отсутствует и виджет не найден в дереве'
                : 'нет варианта с индексом ' . $index;
        }
    }

    if ($option === null) {
        error_log('[BSPB Payment] Вариант не найден: post_id=' . $post_id
            . ' widget_id=' . $widget_id . ' index=' . $index . ' | ' . $reason);

        $response = ['message' => 'Вариант оплаты не найден.'];
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $response['debug'] = $reason;
        }
        wp_send_json_error($response, 404);
    }

    $price = (float) $option['price'];
    $label = sanitize_text_field($option['label']);

    if ($price <= 0) {
        wp_send_json_error(['message' => 'Неверная сумма оплаты.'], 400);
    }

    // Собираем описание заказа: вариант + выбор в списке + телефон.
    // (В SDK нет отдельных полей телефона/доп.данных — кладём в description.)
    $desc_parts = [];
    if ($label !== '') {
        $desc_parts[] = $label;
    }

    // Значение Select берём ТОЛЬКО из серверного списка по индексу.
    if ($select_index >= 0 && isset($select_values[$select_index]) && $select_values[$select_index] !== '') {
        $sval = sanitize_text_field($select_values[$select_index]);
        $desc_parts[] = ($select_label !== '' ? sanitize_text_field($select_label) . ': ' : '') . $sval;
    }

    // Телефон — свободный ввод; нормализуем (цифры и + ( ) - пробел).
    $phone_clean = trim(preg_replace('/[^\d\+\(\)\-\s]/u', '', $phone));
    if ($phone_clean !== '') {
        $desc_parts[] = 'Тел.: ' . $phone_clean;
    }

    $description = !empty($desc_parts) ? implode('; ', $desc_parts) : null;

    // E-mail передаём в SDK только если он валиден.
    $email = is_email($email) ? $email : null;

    try {
        $url = getPaymentLink($price, $description, $email);
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
