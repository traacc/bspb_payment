<?php
/**
 * Минимальный пример создания ссылки на оплату через эквайринг
 * Банка Санкт-Петербург БЕЗ использования WooCommerce.
 *
 * Используется только официальный SDK из каталога libs/BspbSDK.
 * Совместимость: PHP 7.1 - 8.3
 *
 * Запуск из консоли:   php minimalPayment.php 1500.50
 * Подключение в коде:  require 'minimalPayment.php'; echo getPaymentLink(1500.50);
 */

use BspbSDK\Bspb;
use BspbSDK\Entity\Request\CreateOrderRequest;
use BspbSDK\Entity\Response\CreateOrderResponse;

/* -------------------------------------------------------------------------
 * 1. Автозагрузчик классов SDK (заменяет автозагрузчик WooCommerce-плагина).
 * ---------------------------------------------------------------------- */
spl_autoload_register(function ($className) {
    if (strpos($className, 'BspbSDK') === 0) {
        $classPath = implode(DIRECTORY_SEPARATOR, explode('\\', substr($className, 8)));
        $filePath  = __DIR__ . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR
            . 'BspbSDK' . DIRECTORY_SEPARATOR . $classPath . '.php';
        if (is_readable($filePath) && file_exists($filePath)) {
            require_once $filePath;
        }
    }
});

/* -------------------------------------------------------------------------
 * 2. Настройки магазина. Заполните своими значениями, выданными банком.
 * ---------------------------------------------------------------------- */
const BSPB_CONFIG = [
    // Путь к файлу сертификата *.pem
    'cert_pem'     => __DIR__ . '/crt/cert.pem',
    // Путь к файлу ключа *.key
    'cert_key'     => __DIR__ . '/crt/cert.key',
    // Идентификатор магазина (merchant login), выданный банком
    'merchant'     => 'YOUR_MERCHANT_ID',
    // Пароль для авторизации по JSON-схеме
    'password'     => 'YOUR_PASSWORD',
    // true  — тестовый сервер pgtest.bspb.ru
    // false — боевой сервер pg.bspb.ru
    'is_test'      => true,
    // URL, на который банк вернёт покупателя после оплаты
    'redirect_url' => 'https://example.com/payment-return',
    // Путь к лог-файлу (null — логирование выключено)
    'log_file'     => __DIR__ . '/logs/log.txt',
];

/**
 * Возвращает итоговый конфиг магазина.
 *
 * За основу берутся дефолты из константы BSPB_CONFIG. Если код выполняется
 * внутри WordPress, значения можно переопределить через фильтр 'bspb_config'
 * (например, из админ-страницы плагина). В CLI фильтры недоступны — используются
 * дефолты из константы.
 *
 * @return array
 */
function bspb_get_config(): array
{
    $config = BSPB_CONFIG;

    if (function_exists('apply_filters')) {
        $config = apply_filters('bspb_config', $config);
    }

    return $config;
}

/**
 * Создаёт заказ в платёжной системе и возвращает ссылку на оплату.
 *
 * @param float       $amount      Сумма заказа (в рублях, например 1500.50).
 * @param string|null $description Описание/назначение платежа.
 * @param string|null $email       E-mail покупателя (для отправки чека ОФД).
 *
 * @return string Полная ссылка на платёжную страницу (HPP URL).
 *
 * @throws \RuntimeException Если банк вернул ошибку или не выдал ссылку.
 */
function getPaymentLink(float $amount, ?string $description = null, ?string $email = null): string
{
    if ($amount <= 0) {
        throw new \InvalidArgumentException('Сумма оплаты должна быть больше нуля.');
    }

    // Нормализуем сумму до двух знаков после запятой.
    $amount = round($amount, 2);

    $description = $description ?? ('Оплата на сумму ' . $amount . ' RUB');

    // Получаем актуальный конфиг (дефолты из константы + переопределения из админки).
    $config = bspb_get_config();

    // Инициализируем SDK банка.
    $bspb = new Bspb(
        $config['cert_pem'],
        $config['cert_key'],
        $config['merchant'],
        $config['password'],
        $config['is_test'],
        $config['log_file']            // строка-путь к логу или null
    );

    // Формируем запрос на создание заказа.
    $request = (new CreateOrderRequest())
        ->setAmount($amount)
        ->setTitle($description)
        ->setDescription($description)
        ->setHppRedirectUrl($config['redirect_url']);

    if (!empty($email)) {
        $request->setSrcEmail($email);
    }

    // Отправляем запрос в банк.
    /** @var CreateOrderResponse $response */
    $response = $bspb->createOrder($request);

    if (!$response->isSuccess()) {
        throw new \RuntimeException(
            'Ошибка создания оплаты. Ответ банка: ' . $response->getOrigin()
        );
    }

    // Формируем полную ссылку на оплату (HPP URL + id + password).
    $link = $bspb->createHppUrlByReponse($response);
    if ($link === '') {
        throw new \RuntimeException('Банк не вернул ссылку на оплату.');
    }

    return $link;
}

/* -------------------------------------------------------------------------
 * 3. Пример запуска из командной строки: php minimalPayment.php 1500.50
 * ---------------------------------------------------------------------- */
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $amount = isset($argv[1]) ? (float) $argv[1] : 100.00;
    try {
        $url = getPaymentLink($amount);
        echo "Ссылка на оплату:\n{$url}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, 'Ошибка: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
