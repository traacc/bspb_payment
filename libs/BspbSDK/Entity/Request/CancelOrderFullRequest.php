<?php

namespace BspbSDK\Entity\Request;

use BspbSDK\Entity\Request\Part\ReceiptCollection;

/*
//Request
{
    "tran": {
        "voidKind": "Full",
        "amount": 200.00,
        "phase": "Single"
    }
}
//Response
{
    "tran": {
        "approvedPartial": false,
        "match": {
            "tranActionId": "231216-21225443-003y6q=",
            "ridByPmo": "231216769747838494"
        }
    }
}
*/

class CancelOrderFullRequest extends AbstractRequestEntity
{
    protected static $getParametersList = [
        'orderId',
    ];

    /** @var string */
    protected $orderId;

    /** @var string */
    protected $voidKind = 'Full';
    /** @var string */
    protected $phase = 'Single';
    /** @var float */
    protected $amount;

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return CancelOrderFullRequest
     */
    public function setOrderId(string $orderId): CancelOrderFullRequest
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getVoidKind(): string
    {
        return $this->voidKind;
    }

    /**
     * @param string $voidKind
     * @return CancelOrderFullRequest
     */
    public function setVoidKind(string $voidKind): CancelOrderFullRequest
    {
        $this->voidKind = $voidKind;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhase(): string
    {
        return $this->phase;
    }

    /**
     * @param string $phase
     * @return CancelOrderFullRequest
     */
    public function setPhase(string $phase): CancelOrderFullRequest
    {
        $this->phase = $phase;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return CancelOrderFullRequest
     */
    public function setAmount(float $amount): CancelOrderFullRequest
    {
        $this->amount = $amount;
        return $this;
    }


    public function getAllFields():array
    {
        return [
            'tran' => parent::getAllFields(),
        ];
    }

}
