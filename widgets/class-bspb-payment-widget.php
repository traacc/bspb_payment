<?php
/**
 * Виджет Elementor: варианты оплаты (radio + описание + цена) и кнопка оплаты.
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;

class Bspb_Payment_Widget extends Widget_Base
{
    public function get_name()
    {
        return 'bspb_payment';
    }

    public function get_title()
    {
        return __('Оплата BSPB', 'bspb');
    }

    public function get_icon()
    {
        return 'eicon-price-list';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['payment', 'оплата', 'bspb', 'эквайринг', 'radio'];
    }

    public function get_script_depends()
    {
        return ['bspb-payment'];
    }

    /* ---------------------------------------------------------------------
     * Контролы редактора.
     * ------------------------------------------------------------------ */
    protected function register_controls()
    {
        /* ---------- Секция: Варианты оплаты ---------- */
        $this->start_controls_section('section_options', [
            'label' => __('Варианты оплаты', 'bspb'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $repeater = new Repeater();

        $repeater->add_control('option_label', [
            'label'       => __('Название', 'bspb'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Тариф', 'bspb'),
            'label_block' => true,
        ]);

        $repeater->add_control('option_description', [
            'label'   => __('Описание', 'bspb'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Описание варианта оплаты', 'bspb'),
            'rows'    => 3,
        ]);

        $repeater->add_control('option_price', [
            'label'   => __('Цена (RUB)', 'bspb'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 0,
            'step'    => 0.01,
            'default' => 1000,
        ]);

        $repeater->add_control('option_default', [
            'label'        => __('Выбран по умолчанию', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('payment_options', [
            'label'       => __('Варианты', 'bspb'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                [
                    'option_label'       => __('Базовый', 'bspb'),
                    'option_description' => __('Базовый набор услуг.', 'bspb'),
                    'option_price'       => 1000,
                    'option_default'     => 'yes',
                ],
                [
                    'option_label'       => __('Расширенный', 'bspb'),
                    'option_description' => __('Расширенный набор услуг.', 'bspb'),
                    'option_price'       => 2500,
                    'option_default'     => '',
                ],
            ],
            'title_field' => '{{{ option_label }}} — {{{ option_price }}} ₽',
        ]);

        $this->add_control('price_suffix', [
            'label'   => __('Суффикс цены', 'bspb'),
            'type'    => Controls_Manager::TEXT,
            'default' => '₽',
        ]);

        $this->end_controls_section();

        /* ---------- Секция: Кнопка и e-mail ---------- */
        $this->start_controls_section('section_button', [
            'label' => __('Кнопка оплаты', 'bspb'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('button_text', [
            'label'   => __('Текст кнопки', 'bspb'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Оплатить', 'bspb'),
        ]);

        $this->add_control('collect_email', [
            'label'        => __('Запрашивать e-mail', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Для отправки чека ОФД.', 'bspb'),
        ]);

        $this->add_control('email_placeholder', [
            'label'     => __('Placeholder e-mail', 'bspb'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('E-mail для чека', 'bspb'),
            'condition' => ['collect_email' => 'yes'],
        ]);

        $this->add_control('collect_phone', [
            'label'        => __('Запрашивать телефон', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
            'separator'    => 'before',
        ]);

        $this->add_control('phone_required', [
            'label'        => __('Телефон обязателен', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['collect_phone' => 'yes'],
        ]);

        $this->add_control('phone_placeholder', [
            'label'     => __('Placeholder телефона', 'bspb'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Телефон', 'bspb'),
            'condition' => ['collect_phone' => 'yes'],
        ]);

        $this->end_controls_section();

        /* ---------- Секция: Настраиваемое Select-поле ---------- */
        $this->start_controls_section('section_select', [
            'label' => __('Доп. поле (выпадающий список)', 'bspb'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('enable_select', [
            'label'        => __('Показывать поле-список', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('select_label', [
            'label'       => __('Подпись поля', 'bspb'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Выберите вариант', 'bspb'),
            'label_block' => true,
            'condition'   => ['enable_select' => 'yes'],
        ]);

        $this->add_control('select_required', [
            'label'        => __('Обязательное', 'bspb'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Да', 'bspb'),
            'label_off'    => __('Нет', 'bspb'),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['enable_select' => 'yes'],
        ]);

        $select_repeater = new Repeater();
        $select_repeater->add_control('select_option_label', [
            'label'       => __('Пункт списка', 'bspb'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Вариант', 'bspb'),
            'label_block' => true,
        ]);

        $this->add_control('select_options', [
            'label'       => __('Пункты списка', 'bspb'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $select_repeater->get_controls(),
            'default'     => [
                ['select_option_label' => __('Вариант 1', 'bspb')],
                ['select_option_label' => __('Вариант 2', 'bspb')],
            ],
            'title_field' => '{{{ select_option_label }}}',
            'condition'   => ['enable_select' => 'yes'],
        ]);

        $this->end_controls_section();

        /* ---------- Секция: Стиль ---------- */
        $this->start_controls_section('section_style', [
            'label' => __('Стиль', 'bspb'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('heading_options_style', [
            'label' => __('Варианты', 'bspb'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_control('accent_color', [
            'label'     => __('Цвет акцента (radio)', 'bspb'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#2563eb',
            'selectors' => [
                '{{WRAPPER}} .bspb-option input[type=radio]' => 'accent-color: {{VALUE}};',
                '{{WRAPPER}} .bspb-option.is-selected'        => 'border-color: {{VALUE}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'option_border',
            'selector' => '{{WRAPPER}} .bspb-option',
        ]);

        $this->add_control('option_radius', [
            'label'      => __('Скругление вариантов', 'bspb'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 50]],
            'default'    => ['unit' => 'px', 'size' => 8],
            'selectors'  => [
                '{{WRAPPER}} .bspb-option' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('heading_button_style', [
            'label'     => __('Кнопка', 'bspb'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('button_bg', [
            'label'     => __('Фон кнопки', 'bspb'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#2563eb',
            'selectors' => ['{{WRAPPER}} .bspb-pay-button' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('button_color', [
            'label'     => __('Цвет текста кнопки', 'bspb'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .bspb-pay-button' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'button_typography',
            'selector' => '{{WRAPPER}} .bspb-pay-button',
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * Рендер на фронтенде.
     * ------------------------------------------------------------------ */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $options  = isset($settings['payment_options']) ? $settings['payment_options'] : [];

        if (empty($options)) {
            return;
        }

        $widget_id = $this->get_id();
        $post_id   = get_the_ID();
        $suffix    = isset($settings['price_suffix']) ? $settings['price_suffix'] : '₽';
        $radio_name = 'bspb_option_' . $widget_id;

        // Индекс варианта, выбранного по умолчанию (первый с флагом, иначе 0).
        $default_index = 0;
        foreach ($options as $i => $opt) {
            if (!empty($opt['option_default'])) {
                $default_index = $i;
                break;
            }
        }

        // Конфигурация доп. поля-списка.
        $select_enabled = (!empty($settings['enable_select']) && $settings['enable_select'] === 'yes');
        $select_label   = isset($settings['select_label']) ? $settings['select_label'] : '';
        $select_options = ($select_enabled && !empty($settings['select_options'])) ? $settings['select_options'] : [];
        $select_values  = [];
        foreach ($select_options as $so) {
            $select_values[] = isset($so['select_option_label']) ? (string) $so['select_option_label'] : '';
        }

        // Сохраняем прайс-лист и допустимые значения поля-списка на сервере.
        // AJAX-обработчик возьмёт цену/значение отсюда по widget_id — не зависит
        // от того, где размещён виджет (страница, шаблон Theme Builder, Loop).
        if (function_exists('bspb_ep_store_options')) {
            bspb_ep_store_options($widget_id, $options, [
                'select_label'  => $select_label,
                'select_values' => $select_values,
            ]);
        }

        $select_name = 'bspb_select_' . $widget_id;
        ?>
        <div class="bspb-payment-widget"
             data-post-id="<?php echo esc_attr($post_id); ?>"
             data-widget-id="<?php echo esc_attr($widget_id); ?>">

            <div class="bspb-options" role="radiogroup">
                <?php foreach ($options as $i => $opt) :
                    $label = isset($opt['option_label']) ? $opt['option_label'] : '';
                    $desc  = isset($opt['option_description']) ? $opt['option_description'] : '';
                    $price = isset($opt['option_price']) ? (float) $opt['option_price'] : 0;
                    $checked = ($i === $default_index);
                    $input_id = $radio_name . '_' . $i;
                    ?>
                    <label class="bspb-option<?php echo $checked ? ' is-selected' : ''; ?>" for="<?php echo esc_attr($input_id); ?>">
                        <input type="radio"
                               id="<?php echo esc_attr($input_id); ?>"
                               name="<?php echo esc_attr($radio_name); ?>"
                               value="<?php echo esc_attr($i); ?>"
                               <?php checked($checked); ?> />
                        <span class="bspb-option-body">
                            <span class="bspb-option-head">
                                <span class="bspb-option-label"><?php echo esc_html($label); ?></span>
                                <span class="bspb-option-price">
                                    <?php echo esc_html(number_format_i18n($price, ($price == (int) $price) ? 0 : 2)); ?>
                                    <?php echo esc_html($suffix); ?>
                                </span>
                            </span>
                            <?php if ($desc !== '') : ?>
                                <span class="bspb-option-desc"><?php echo esc_html($desc); ?></span>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if ($select_enabled && !empty($select_values)) : ?>
                <label class="bspb-field bspb-field-select">
                    <?php if ($select_label !== '') : ?>
                        <span class="bspb-field-label"><?php echo esc_html($select_label); ?></span>
                    <?php endif; ?>
                    <select class="bspb-select"
                            data-required="<?php echo !empty($settings['select_required']) && $settings['select_required'] === 'yes' ? '1' : ''; ?>">
                        <option value=""><?php echo esc_html($select_label !== '' ? $select_label : __('— выберите —', 'bspb')); ?></option>
                        <?php foreach ($select_values as $si => $sval) : ?>
                            <option value="<?php echo esc_attr($si); ?>"><?php echo esc_html($sval); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

            <?php if (!empty($settings['collect_email']) && $settings['collect_email'] === 'yes') : ?>
                <input type="email"
                       class="bspb-email"
                       placeholder="<?php echo esc_attr($settings['email_placeholder']); ?>" />
            <?php endif; ?>

            <?php if (!empty($settings['collect_phone']) && $settings['collect_phone'] === 'yes') : ?>
                <input type="tel"
                       class="bspb-phone"
                       data-required="<?php echo !empty($settings['phone_required']) && $settings['phone_required'] === 'yes' ? '1' : ''; ?>"
                       placeholder="<?php echo esc_attr($settings['phone_placeholder']); ?>" />
            <?php endif; ?>

            <button type="button" class="bspb-pay-button">
                <?php echo esc_html($settings['button_text']); ?>
            </button>

            <p class="bspb-message" role="alert" aria-live="polite"></p>
        </div>
        <?php
    }
}
