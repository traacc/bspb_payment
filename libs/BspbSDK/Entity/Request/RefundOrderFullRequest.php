<?php

namespace BspbSDK\Entity\Request;

use BspbSDK\Entity\Request\Part\ReceiptCollection;

class RefundOrderFullRequest extends AbstractRequestEntity
{
    protected static $getParametersList = [
        'orderId',
    ];

    /** @var string */
    protected $orderId;

    /** @var string */
    protected $type = 'Refund';
    /** @var string */
    protected $phase = 'Single';
    /** @var float */
    protected $amount;
    /** @var ReceiptCollection|null */
    protected $receipt;

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RefundOrderFullRequest
     */
    public function setOrderId(string $orderId): RefundOrderFullRequest
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RefundOrderFullRequest
     */
    public function setType(string $type): RefundOrderFullRequest
    {
        $this->type = $type;
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
     * @return RefundOrderFullRequest
     */
    public function setPhase(string $phase): RefundOrderFullRequest
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
     * @return RefundOrderFullRequest
     */
    public function setAmount(float $amount): RefundOrderFullRequest
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return ReceiptCollection
     */
    public function getReceipt(): ?ReceiptCollection
    {
        return $this->receipt;
    }

    /**
     * @param ReceiptCollection $receipt
     * @return RefundOrderFullRequest
     */
    public function setReceipt(ReceiptCollection $receipt): RefundOrderFullRequest
    {
        $this->receipt = $receipt;
        return $this;
    }

    public function getAllFields():array
    {
        return [
            'tran' => parent::getAllFields(),
        ];
    }

}
