<?php

namespace BspbSDK\Entity\Request\Part;

use BspbSDK\Entity\Request\AbstractRequestEntity;

class Receipt extends AbstractRequestEntity
{

    /** @var string */
    protected $desc;
    /** @var float */
    protected $price;
    /** @var float */
    protected $quantity;
    /** @var int */
    protected $measure;

    /**
     * @return string
     */
    public function getDesc(): string
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     * @return Receipt
     */
    public function setDesc(string $desc): Receipt
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return Receipt
     */
    public function setPrice(float $price): Receipt
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return Receipt
     */
    public function setQuantity(float $quantity): Receipt
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return int
     */
    public function getMeasure(): int
    {
        return $this->measure;
    }

    /**
     * @param int $measure
     * @return Receipt
     */
    public function setMeasure(int $measure): Receipt
    {
        $this->measure = $measure;
        return $this;
    }



}
