<?php

namespace BspbSDK\Entity\Response;

use BspbSDK\Entity\Response\Part\OrderInfoType;

class GetOrderInfoResponse extends AbstractResponse
{

    protected $id;
    protected $typeRid;
    protected $status;
    protected $prevStatus;
    protected $lastStatusLogin;
    protected $amount;
    protected $currency;
    protected $createTime;
    protected $title;
    protected $expTime;
    /** @var OrderInfoType */
    protected $type;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return GetOrderInfoResponse
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeRid()
    {
        return $this->typeRid;
    }

    /**
     * @param mixed $typeRid
     * @return GetOrderInfoResponse
     */
    public function setTypeRid($typeRid)
    {
        $this->typeRid = $typeRid;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return GetOrderInfoResponse
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrevStatus()
    {
        return $this->prevStatus;
    }

    /**
     * @param mixed $prevStatus
     * @return GetOrderInfoResponse
     */
    public function setPrevStatus($prevStatus)
    {
        $this->prevStatus = $prevStatus;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastStatusLogin()
    {
        return $this->lastStatusLogin;
    }

    /**
     * @param mixed $lastStatusLogin
     * @return GetOrderInfoResponse
     */
    public function setLastStatusLogin($lastStatusLogin)
    {
        $this->lastStatusLogin = $lastStatusLogin;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     * @return GetOrderInfoResponse
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     * @return GetOrderInfoResponse
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param mixed $createTime
     * @return GetOrderInfoResponse
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return GetOrderInfoResponse
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpTime()
    {
        return $this->expTime;
    }

    /**
     * @param mixed $expTime
     * @return GetOrderInfoResponse
     */
    public function setExpTime($expTime)
    {
        $this->expTime = $expTime;
        return $this;
    }

    /**
     * @return OrderInfoType
     */
    public function getType(): OrderInfoType
    {
        return $this->type;
    }

    /**
     * @param OrderInfoType $type
     * @return GetOrderInfoResponse
     */
    public function setType($type): GetOrderInfoResponse
    {
        $this->type = (new OrderInfoType())->setFields($type);
        return $this;
    }

    public function setFields($fields)
    {
        if (is_object($fields) && isset($fields->order)) return parent::setFields($fields->order);
        return parent::setFields($fields);
    }

    public function isOrderPaid():bool
    {
        return $this->status === 'FullyPaid';
    }

}
