<?php

namespace BspbSDK\Entity\Request;

use BspbSDK\Entity\Request\Part\ReceiptCollection;

class CreateOrderRequest extends AbstractRequestEntity
{

    /** @var string */
    protected $typeRid = 'Purchase';
    /** @var float */
    protected $amount;
    /** @var string */
    protected $currency = 'RUB';
    /** @var string */
    protected $language = 'ru';
    /** @var string */
    protected $title;
    /** @var string */
    protected $description;
    /** @var string */
    protected $hppRedirectUrl;

    /** @var string | null */
    protected $srcEmail;

    /** @var ReceiptCollection | null */
    protected $receipt;

    /**
     * @return string
     */
    public function getTypeRid(): string
    {
        return $this->typeRid;
    }

    /**
     * @param string $typeRid
     * @return CreateOrderRequest
     */
    public function setTypeRid(string $typeRid): CreateOrderRequest
    {
        $this->typeRid = $typeRid;
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
     * @return CreateOrderRequest
     */
    public function setAmount(float $amount): CreateOrderRequest
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return CreateOrderRequest
     */
    public function setCurrency(string $currency): CreateOrderRequest
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return CreateOrderRequest
     */
    public function setLanguage(string $language): CreateOrderRequest
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return CreateOrderRequest
     */
    public function setTitle(string $title): CreateOrderRequest
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return CreateOrderRequest
     */
    public function setDescription(string $description): CreateOrderRequest
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getHppRedirectUrl(): string
    {
        return $this->hppRedirectUrl;
    }

    /**
     * @param string $hppRedirectUrl
     * @return CreateOrderRequest
     */
    public function setHppRedirectUrl(string $hppRedirectUrl): CreateOrderRequest
    {
        $this->hppRedirectUrl = $hppRedirectUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSrcEmail(): ?string
    {
        return $this->srcEmail;
    }

    /**
     * @param string|null $srcEmail
     * @return CreateOrderRequest
     */
    public function setSrcEmail(?string $srcEmail): CreateOrderRequest
    {
        $this->srcEmail = $srcEmail;
        return $this;
    }

    /**
     * @return ReceiptCollection|null
     */
    public function getReceipt(): ?ReceiptCollection
    {
        return $this->receipt;
    }

    /**
     * @param ReceiptCollection|null $receipt
     * @return CreateOrderRequest
     */
    public function setReceipt(?ReceiptCollection $receipt): CreateOrderRequest
    {
        $this->receipt = $receipt;
        return $this;
    }


    public function getAllFields():array
    {
        return [
            'order' => parent::getAllFields(),
        ];
    }


}
