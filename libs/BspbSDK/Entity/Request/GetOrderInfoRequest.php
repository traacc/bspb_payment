<?php

namespace BspbSDK\Entity\Request;

class GetOrderInfoRequest extends AbstractRequestEntity
{
    protected static $getParametersList = [
        'orderId',
        'orderPassword',
    ];

    /** @var string */
    protected $orderId;
    /** @var string */
    protected $orderPassword;

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return GetOrderInfoRequest
     */
    public function setOrderId(string $orderId): GetOrderInfoRequest
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderPassword(): string
    {
        return $this->orderPassword;
    }

    /**
     * @param string $orderPassword
     * @return GetOrderInfoRequest
     */
    public function setOrderPassword(string $orderPassword): GetOrderInfoRequest
    {
        $this->orderPassword = $orderPassword;
        return $this;
    }



}
