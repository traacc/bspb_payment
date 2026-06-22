<?php

namespace BspbSDK\Controllers;

use BspbSDK\Client\BSPBApiClient;
use BspbSDK\Entity\Request\AbstractRequestEntity;
use BspbSDK\Entity\Response\AbstractResponse;
use BspbSDK\Entity\Response\AbstractResponseEntity;
use BspbSDK\Entity\Response\ErrorResponse;
use BspbSDK\Exceptions\BspbApiException;
use BspbSDK\Exceptions\BspbBadResponseException;
use BspbSDK\Exceptions\BspbException;

class MainController
{
    /**
     * @var array
     */
    private $data = [];

    /** @var BSPBApiClient */
    protected $apiClient;

    public function __construct(BSPBApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function exec(AbstractRequestEntity $requestData):AbstractResponse
    {
        $this->prepareUrl($requestData);
        $this->prepareData($requestData);
        $responseClass = '\BspbSDK\Entity\Response\\'.ucfirst($this->apiClient->getMethod()).'Response';
        try {
            $response = new $responseClass($this->request());
            $response->setSuccess(true);
        } catch (BspbApiException $bspbApiException) {
            try {
                $response = new ErrorResponse($bspbApiException->getResponse());
                $response->setSuccess(false);
            } catch (BspbBadResponseException $bspbBadResponseException) {
                $response = new ErrorResponse(json_encode([
                    'error' => $bspbBadResponseException->getMessage(),
                ]));
                $response->setContent( $bspbBadResponseException->getContent() );
                $response->setSuccess(false);
            }
        } catch (BspbException $bspbException) {
            $response = new ErrorResponse(json_encode([
                'error' => '[Code ' . $bspbException->getCode() . '] ' . $bspbException->getMessage(),
            ]));
            $response->setSuccess(false);
        }
        $this->setResponse($response)->setFields();
        return $this->getResponse();
    }

    protected function request()
    {
        switch ($this->apiClient->getRequestType()) {
            case 'GET':
                return $this->apiClient->get('',$this->getData());
            case 'POST':
                return $this->apiClient->post($this->getData());
            default:
                throw new BspbException('Type request not set!');
        }
    }

    private function prepareUrl(AbstractRequestEntity $object): void
    {
        $this->apiClient->setUrl( $object->fillUrlParams($this->apiClient->getUrl()) );
    }

    private function prepareData(AbstractRequestEntity $object): void
    {
        $this->setData($object->getAllFields());
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return MainController
     */
    public function setData(array $data): MainController
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return AbstractResponse
     */
    public function getResponse(): AbstractResponse
    {
        return $this->response;
    }

    /**
     * @param AbstractResponse $response
     * @return MainController
     */
    public function setResponse(AbstractResponse $response): MainController
    {
        $this->response = $response;
        return $this;
    }

    protected function setFields()
    {
        /**@var AbstractResponseEntity $response */
        $response = $this->getResponse();
        if ($response) $response->setFields($response->getDecoded());
    }

}
