# CLAUDE.md

Руководство для Claude Code по работе с этим репозиторием.

## Что это

WordPress-плагин **BSPB Elementor Payment**: добавляет виджет Elementor, который
рендерит radio-список вариантов оплаты (название + описание + цена) и кнопку
оплаты через эквайринг Банка Санкт-Петербург. Ссылка на оплату создаётся
официальным SDK банка (`libs/BspbSDK`).

Язык интерфейса и комментариев — русский. Сохраняй этот стиль.

## Архитектура

Поток оплаты:

1. Виджет рендерит варианты как `<input type="radio">` + кнопку (PHP, `render()`).
2. По клику [assets/bspb-payment.js](assets/bspb-payment.js) шлёт в `admin-ajax.php`
   **только индекс** выбранного варианта (`option_index`), `post_id`, `widget_id`,
   nonce и опциональный e-mail. Цену клиент НЕ передаёт.
3. AJAX-обработчик `bspb_ep_ajax_create_payment` ([bspb-elementor-payment.php](bspb-elementor-payment.php))
   перечитывает настройки виджета на сервере, берёт цену по индексу, вызывает
   `getPaymentLink()` и возвращает HPP-URL. JS делает редирект.

### Ключевые файлы

| Файл | Назначение |
|------|-----------|
| [bspb-elementor-payment.php](bspb-elementor-payment.php) | Главный файл плагина: регистрация виджета, enqueue ассетов, AJAX-обработчик, фильтр конфига |
| [widgets/class-bspb-payment-widget.php](widgets/class-bspb-payment-widget.php) | Виджет Elementor `Bspb_Payment_Widget`: контролы и `render()` |
| [admin/class-bspb-settings.php](admin/class-bspb-settings.php) | Админ-страница настроек банка (Settings API), класс `Bspb_Settings` |
| [minimalPayment.php](minimalPayment.php) | `getPaymentLink()` + резолвер конфига `bspb_get_config()`; работает и в CLI |
| [assets/bspb-payment.js](assets/bspb-payment.js) | Клиентская логика оплаты |
| [assets/bspb-payment.css](assets/bspb-payment.css) | Базовые стили (тонкая настройка — вкладка «Стиль») |
| [libs/BspbSDK/](libs/BspbSDK/) | Официальный SDK банка (не редактировать) |

## Важные инварианты

- **Цена только с сервера.** Никогда не доверяй сумме/цене из запроса клиента —
  бери из настроек виджета через `bspb_ep_get_widget_settings()`. Это защита от
  подмены суммы.
- **Настройки виджета читай с учётом дефолтов.** Используй
  `create_element_instance()` + `get_settings_for_display()`, а НЕ сырой
  `get_elements_data()` — Elementor не сохраняет в БД значения контролов,
  оставленные по умолчанию (это была причина бага «Вариант оплаты не найден»).
- **Конфиг банка — через фильтр.** `getPaymentLink()` берёт настройки из
  `bspb_get_config()`, который применяет фильтр `bspb_config`. Админ-страница
  (`Bspb_Settings::filter_config`) переопределяет дефолты из константы
  `BSPB_CONFIG`. Не возвращай хардкод констант — правь через настройки/фильтр.
- **`minimalPayment.php` должен работать в CLI.** Не вызывай в нём WP-функции без
  `function_exists()`-проверки; CLI-блок внизу защищён `PHP_SAPI === 'cli'`.
- **AJAX:** всегда проверяй nonce (`check_ajax_referer('bspb_payment', ...)`),
  санитизируй вход, наружу отдавай общее сообщение об ошибке, детали — в `error_log`.

## Конвенции

- Префикс функций — `bspb_ep_`, опция настроек — `bspb_payment_settings`,
  nonce/локализация — `bspb_payment` / `BSPB_PAYMENT`.
- Экранируй весь вывод (`esc_html`, `esc_attr`, `esc_url`).
- JS — без зависимостей (vanilla, `fetch`); поддержан хук Elementor
  `frontend/element_ready/bspb_payment.default` для редактора.

## Окружение и проверка

- Это WordPress-плагин — полноценно работает только внутри WP с активным Elementor.
- PHP CLI в dev-окружении может отсутствовать; если есть — проверяй синтаксис:
  `php -l <файл>`. Иначе выверяй изменения вручную.
- Реальные креды банка и сертификаты (`crt/cert.pem`, `crt/cert.key`) в репозиторий
  не коммить.

## Установка (для справки)

1. Каталог плагина → `wp-content/plugins/`, активировать.
2. **Настройки → Оплата BSPB**: Merchant ID, пароль, URL возврата, пути к
   сертификатам, режим (тест/бой).
3. На странице добавить виджет «Оплата BSPB» (категория General).
