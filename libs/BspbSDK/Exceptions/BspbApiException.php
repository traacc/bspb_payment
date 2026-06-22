<?php

namespace BspbSDK\Exceptions;

class BspbApiException extends BspbException
{
    /**
     * @var string
     */
    protected $url;
    protected $request;
    protected $response;

    /**
     * @param $message
     * @param $code
     * @param $url
     * @param $request
     * @param $response
     */
    public function __construct($message = false, $code = false, $url = "", $request = false, $response = false)
    {
        parent::__construct($message, $code);
        $this->url = $url;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

}
