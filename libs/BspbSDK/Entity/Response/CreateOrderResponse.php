<?php

namespace BspbSDK\Entity\Response;

class CreateOrderResponse extends AbstractResponse
{

    protected $id;
    protected $password;
    protected $hppUrl;
    protected $accessToken;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return CreateOrderResponse
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @return CreateOrderResponse
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHppUrl()
    {
        return $this->hppUrl;
    }

    public function getHppUrlFull()
    {
        return $this->hppUrl . '?id=' . $this->id . '&password=' . $this->password;
    }

    /**
     * @param mixed $hppUrl
     * @return CreateOrderResponse
     */
    public function setHppUrl($hppUrl)
    {
        $this->hppUrl = $hppUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param mixed $accessToken
     * @return CreateOrderResponse
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }


    public function setFields($fields)
    {
        if (is_object($fields) && isset($fields->order)) return parent::setFields($fields->order);
        return parent::setFields($fields);
    }


}
