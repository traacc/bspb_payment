<?php
/**
 * Админ-страница настроек подключения к эквайрингу Банка Санкт-Петербург.
 * Использует WordPress Settings API. Значения переопределяют дефолты конфига
 * через фильтр 'bspb_config'.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bspb_Settings
{
    /** Имя опции в wp_options. */
    const OPTION = 'bspb_payment_settings';

    /** Слаг страницы настроек. */
    const PAGE = 'bspb-payment-settings';

    /** Группа настроек. */
    const GROUP = 'bspb_payment_group';

    /**
     * Дефолтные значения.
     *
     * @return array
     */
    public static function defaults(): array
    {
        return [
            'merchant'     => '',
            'password'     => '',
            'is_test'      => '1',
            'redirect_url' => home_url('/payment-return'),
            'cert_pem'     => BSPB_EP_DIR . '/crt/cert.pem',
            'cert_key'     => BSPB_EP_DIR . '/crt/cert.key',
            'log_file'     => BSPB_EP_DIR . '/logs/log.txt',
            // Уведомления администратору о созданных оплатах.
            'notify_email' => get_option('admin_email'),
        ];
    }

    /**
     * Текущие настройки (сохранённые + дефолты).
     *
     * @return array
     */
    public static function get(): array
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }
        return array_merge(self::defaults(), $saved);
    }

    /**
     * Регистрация хуков.
     */
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register']);

        // Переопределяем конфиг SDK значениями из админки.
        add_filter('bspb_config', [__CLASS__, 'filter_config']);
    }

    /**
     * Подмена конфига значениями из настроек.
     *
     * @param array $config
     * @return array
     */
    public static function filter_config(array $config): array
    {
        $s = self::get();

        $config['merchant']     = $s['merchant'];
        $config['password']     = $s['password'];
        $config['is_test']      = !empty($s['is_test']);
        $config['redirect_url'] = $s['redirect_url'];
        $config['cert_pem']     = $s['cert_pem'];
        $config['cert_key']     = $s['cert_key'];
        // Пустой путь к логу → логирование выключено (null).
        $config['log_file']     = ($s['log_file'] !== '') ? $s['log_file'] : null;

        return $config;
    }

    /**
     * Пункт меню в «Настройки».
     */
    public static function add_menu(): void
    {
        add_options_page(
            __('Оплата BSPB', 'bspb'),
            __('Оплата BSPB', 'bspb'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Регистрация настроек, секций и полей.
     */
    public static function register(): void
    {
        register_setting(self::GROUP, self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default'           => self::defaults(),
        ]);

        add_settings_section(
            'bspb_main',
            __('Данные магазина', 'bspb'),
            function () {
                echo '<p>' . esc_html__('Значения, выданные банком при подключении эквайринга.', 'bspb') . '</p>';
            },
            self::PAGE
        );

        $fields = [
            'merchant'     => [__('Merchant ID (логин)', 'bspb'), 'text'],
            'password'     => [__('Пароль', 'bspb'), 'password'],
            'is_test'      => [__('Тестовый сервер', 'bspb'), 'checkbox'],
            'redirect_url' => [__('URL возврата после оплаты', 'bspb'), 'url'],
            'cert_pem'     => [__('Путь к сертификату (.pem)', 'bspb'), 'text'],
            'cert_key'     => [__('Путь к ключу (.key)', 'bspb'), 'text'],
            'log_file'     => [__('Путь к лог-файлу (пусто — выкл.)', 'bspb'), 'text'],
        ];

        foreach ($fields as $key => $meta) {
            add_settings_field(
                $key,
                esc_html($meta[0]),
                [__CLASS__, 'render_field'],
                self::PAGE,
                'bspb_main',
                ['key' => $key, 'type' => $meta[1], 'label_for' => self::OPTION . '_' . $key]
            );
        }

        // Секция уведомлений.
        add_settings_section(
            'bspb_notify',
            __('Уведомления', 'bspb'),
            function () {
                echo '<p>' . esc_html__('Письмо администратору при создании ссылки на оплату.', 'bspb') . '</p>';
            },
            self::PAGE
        );

        add_settings_field(
            'notify_email',
            esc_html__('Email для уведомлений (пусто — не слать)', 'bspb'),
            [__CLASS__, 'render_field'],
            self::PAGE,
            'bspb_notify',
            ['key' => 'notify_email', 'type' => 'email', 'label_for' => self::OPTION . '_notify_email']
        );
    }

    /**
     * Рендер одного поля.
     *
     * @param array $args
     */
    public static function render_field(array $args): void
    {
        $settings = self::get();
        $key      = $args['key'];
        $type     = $args['type'];
        $id       = self::OPTION . '_' . $key;
        $name     = self::OPTION . '[' . $key . ']';
        $value    = isset($settings[$key]) ? $settings[$key] : '';

        if ($type === 'checkbox') {
            printf(
                '<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
                esc_attr($id),
                esc_attr($name),
                checked(!empty($value), true, false),
                esc_html__('Использовать pgtest.bspb.ru вместо боевого сервера', 'bspb')
            );
            return;
        }

        $input_type = in_array($type, ['password', 'url', 'email'], true) ? $type : 'text';
        printf(
            '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" autocomplete="off" />',
            esc_attr($input_type),
            esc_attr($id),
            esc_attr($name),
            esc_attr($value)
        );
    }

    /**
     * Санитизация перед сохранением.
     *
     * @param mixed $input
     * @return array
     */
    public static function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $old   = self::get();

        $clean = [];
        $clean['merchant']     = isset($input['merchant']) ? sanitize_text_field($input['merchant']) : '';
        $clean['is_test']      = empty($input['is_test']) ? '' : '1';
        $clean['redirect_url'] = isset($input['redirect_url']) ? esc_url_raw($input['redirect_url']) : '';
        $clean['cert_pem']     = isset($input['cert_pem']) ? sanitize_text_field($input['cert_pem']) : '';
        $clean['cert_key']     = isset($input['cert_key']) ? sanitize_text_field($input['cert_key']) : '';
        $clean['log_file']     = isset($input['log_file']) ? sanitize_text_field($input['log_file']) : '';
        $clean['notify_email'] = isset($input['notify_email']) ? sanitize_email($input['notify_email']) : '';

        // Пустое поле пароля — не затираем сохранённый ранее пароль.
        if (isset($input['password']) && $input['password'] !== '') {
            $clean['password'] = $input['password'];
        } else {
            $clean['password'] = isset($old['password']) ? $old['password'] : '';
        }

        return $clean;
    }

    /**
     * Рендер страницы настроек.
     */
    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Оплата BSPB — настройки', 'bspb'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::GROUP);
                do_settings_sections(self::PAGE);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
