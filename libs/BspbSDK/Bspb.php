<?php
/**
 * Официальный SDK банка Санкт-Петербург
 *
 * SDK предназначена для работы с серверами по схеме JSON. Cовместимость: PHP 7.1 - 8.3
 * Текущие доступные методы: создание ордера, запрос информации, полная отмена оплаты, полный возврат оплаты
 *
 */

namespace BspbSDK;

use BspbSDK\Client\BSPBApiClient;
use BspbSDK\Controllers\MainController;
use BspbSDK\Entity\Request\CancelOrderFullRequest;
use BspbSDK\Entity\Request\CreateOrderRequest;
use BspbSDK\Entity\Request\GetOrderInfoRequest;
use BspbSDK\Entity\Request\Part\ReceiptCollection;
use BspbSDK\Entity\Request\RefundOrderFullRequest;
use BspbSDK\Entity\Response\AbstractResponse;
use BspbSDK\Entity\Response\CreateOrderResponse;
use BspbSDK\Entity\Response\ErrorResponse;
use BspbSDK\Logger\LoggerInterface;
use BspbSDK\Logger\Routes\FileRoute;
use BspbSDK\Logger\Logger;

class Bspb
{
    private const SERVER = [
        'TEST' => 'https://pgtest.bspb.ru',
        'WORK' => 'https://pg.bspb.ru',
    ];

    private const URL_MAP = [
        'createOrder' => [
            'REQUEST_TYPE' => 'POST',
            'URL' => ':5443/order',
        ],
        'getOrderInfo' => [
            'REQUEST_TYPE' => 'GET',
            'URL' => ':5443/order/{orderId}?{orderPassword}',
        ],
        'cancelOrderFull' => [
            'REQUEST_TYPE' => 'POST',
            'URL' => ':5443/order/{orderId}/exec-tran',
        ],
        'refundOrderFull' => [
            'REQUEST_TYPE' => 'POST',
            'URL' => ':5443/order/{orderId}/exec-tran',
        ],

    ];

    /** @var null|LoggerInterface  */
    private $logger;
    /** @var string */
    private $clientType;
    /** @var MainController */
    private $controller;

    /** @var bool  */
    private $isTestMode;

    /** @var string */
    private $certPem;
    /** @var string */
    private $certKey;
    /** @var string */
    private $merchantId;
    /** @var string */
    private $password;

    /**
     * @param string $certPem path to PEM file
     * @param string $certKey path to PEM file
     * @param string $merchantId merchant login
     * @param string $merchantPassword merchant password
     * @param bool $isTest is test mode
     * @param string | LoggerInterface $logger LoggerInterface OR path to log file
     * @param string $clientType type of API client (default Curl)
     */
    public function __construct(
        string $certPem,
        string $certKey,
        string $merchantId,
        string $merchantPassword,
        bool $isTest = false,
        $logger = null,
        string $clientType = 'Curl'
    )
    {
        if (!empty($logger)) {
            if (gettype($logger) === 'string') {
                $route = new FileRoute($logger);
                $route->enable();
                $this->logger = new Logger([$route]);
            }
            if ($logger instanceof LoggerInterface) {
                $this->logger = $logger;
            }
        }
        $this->clientType = $clientType;

        $this->isTestMode = $isTest === true;
        $this->certPem = $certPem;
        $this->certKey = $certKey;
        $this->merchantId = $merchantId;
        $this->password = $merchantPassword;

    }

    private function prepareRequest(string $methodName)
    {
        $client = (new BSPBApiClient(
            $this->certPem,
            $this->certKey,
            $this->merchantId,
            $this->password,
            $this->logger,
            $this->clientType
        ))
        ->setUrl(  $this->getBaseServerUrl() . self::URL_MAP[$methodName]['URL'] )
        ->setMethod($methodName)
        ->setRequestType(self::URL_MAP[$methodName]['REQUEST_TYPE']);

        $this->controller = new MainController($client);
    }

    /**
     * @param CreateOrderRequest $createOrder
     * @return AbstractResponse
     */
    public function createOrder(CreateOrderRequest $createOrder): AbstractResponse
    {
        $this->prepareRequest(__FUNCTION__);
        return $this->controller->exec($createOrder);
    }

    /**
     * @param GetOrderInfoRequest $getOrderInfo
     * @return AbstractResponse
     */
    public function getOrderInfo(GetOrderInfoRequest $getOrderInfo): AbstractResponse
    {
        $this->prepareRequest(__FUNCTION__);
        return $this->controller->exec($getOrderInfo);
    }

    /**
     * @param CancelOrderFullRequest $cancelOrderFull
     * @return AbstractResponse
     */
    public function cancelOrderFull(CancelOrderFullRequest $cancelOrderFull): AbstractResponse
    {
        $this->prepareRequest(__FUNCTION__);
        return $this->controller->exec($cancelOrderFull);
    }

    /**
     * @param RefundOrderFullRequest $refundOrderFull
     * @return AbstractResponse
     */
    public function refundOrderFull(RefundOrderFullRequest $refundOrderFull): AbstractResponse
    {
        $this->prepareRequest(__FUNCTION__);
        return $this->controller->exec($refundOrderFull);
    }

    /**
     * @param CancelOrderFullRequest|RefundOrderFullRequest $refundRequest
     * @param \DateTime $createdDateTime
     * @return AbstractResponse|ErrorResponse
     * @throws Exceptions\BspbBadResponseException
     */
    public function cancelOrRefundOrder($refundRequest, \DateTime $createdDateTime)//: AbstractResponse
    {
        if ( !($refundRequest instanceof CancelOrderFullRequest) && !($refundRequest instanceof RefundOrderFullRequest) )
            return (new ErrorResponse(json_encode([
                'error' => 'Request object must be CancelOrderFullRequest or RefundOrderFullRequest object!',
            ])))->setSuccess(false)->setContent('');
        $request = (new CancelOrderFullRequest())
            ->setAmount( $refundRequest->getAmount() )
            ->setOrderId( $refundRequest->getOrderId() );

        $now = new \DateTime();
        $now->setTime(0,0,0);
        $createdDateTime->setTime(0,0,0);
        $diff = $createdDateTime->diff($now);
        if ($diff->d > 0) {
            $request = (new RefundOrderFullRequest())
                ->setAmount( $refundRequest->getAmount() )
                ->setOrderId( $refundRequest->getOrderId() );
            if (
                ($refundRequest instanceof RefundOrderFullRequest)
                && ($refundRequest->getReceipt() instanceof ReceiptCollection)
            ) {
                $request->setReceipt( $refundRequest->getReceipt());
            }
            return $this->refundOrderFull($request);
        }
        return $this->cancelOrderFull($request);
    }

    private function getBaseServerUrl()
    {
        return $this->isTestMode ? self::SERVER['TEST'] : self::SERVER['WORK'];
    }

    /**
     * @param string $hppUrl base HppUrl from createOrder response
     * @param $id Order ID from createOrder response
     * @param string $password Order password from createOrder response
     * @return string
     */
    public function createHppUrlByParams(string $hppUrl, $id, string $password):string
    {
        return $hppUrl . '?id=' . $id . '&password=' . $password;
    }

    /**
     * @param CreateOrderResponse $response
     * @return string
     */
    public function createHppUrlByReponse(CreateOrderResponse $response):string
    {
        if (!empty($response->getId()) && !empty($response->getPassword())) return $response->getHppUrl() . '?id=' . $response->getId() . '&password=' . $response->getPassword();
        return '';
    }

}
