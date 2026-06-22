<?php

namespace BspbSDK\Entity\Response;

use BspbSDK\Exceptions\BspbBadResponseException;

abstract class AbstractResponse extends AbstractResponseEntity
{
    /**
     * @var string
     */
    protected $origin;
    /**
     * @var mixed
     */
    protected $decoded;
    /**
     * @var bool
     */
    protected $Success;

    /**
     * AbstractResponse constructor.
     * @param $json
     * @throws BspbBadResponseException
     */
    function __construct($responseData)
    {
        $this->origin = $responseData;
        if (empty($responseData)) {
            throw new BspbBadResponseException('Empty server answer ' . __CLASS__);
        }
        $this->setDecoded(json_decode($responseData));
        if (is_null($this->decoded)) {
            $exception = (new BspbBadResponseException('Incorrect server answer ' . __CLASS__))->setContent($responseData);
            throw $exception;
        }
    }

    /**
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     * @return AbstractResponse
     */
    public function setOrigin(string $origin): AbstractResponse
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDecoded()
    {
        return $this->decoded;
    }

    /**
     * @param mixed $decoded
     * @return AbstractResponse
     */
    public function setDecoded($decoded)
    {
        $this->decoded = $decoded;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->Success;
    }

    /**
     * @param bool $Success
     * @return AbstractResponse
     */
    public function setSuccess(bool $Success): AbstractResponse
    {
        $this->Success = $Success;
        return $this;
    }
}
