<?php

namespace BspbSDK\Client;

use BspbSDK\Exceptions\BspbApiException;
use BspbSDK\Exceptions\BspbException;
use BspbSDK\Logger\LoggerInterface;

class BSPBApiClient
{

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    private $allowedCodeArr;
    /**
     * @var array
     */
    private $validErrorCodeArr;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $requestType;

    /**
     * @var string
     */
    protected $method = 'unconfiguredrequest';

    /**
     * CurlAdapter constructor.
     * @param int $timeout
     * @throws \Bspblib\SDK\Exceptions\BspbException if curl not installed
     */
    public function __construct(
        string $certPem,
        string $certKey,
        string $merchantId,
        string $password,
        LoggerInterface $logger = null,
        string $clientType = 'Curl'
    )
    {
        $this->allowedCodeArr = [200];
        //$this->validErrorCodeArr = [302, 400, 401, 404, 500];
        $this->logger = $logger;
        $clientType = '\BspbSDK\Client\\' . $clientType;
        if (!class_exists($clientType))
            throw new BspbException('Error: No Client registered [' . $clientType . ']');
        $this->client = new $clientType();
        if (!($this->client instanceof ClientInterface))
            throw new BspbException('Error: The Client must be compatible with the ClientInterface.');
        $this->client
            ->setCertPem($certPem)
            ->setCertKey($certKey)
            ->setAuthData($merchantId, $password);
    }



    /**
     * @param array $data
     * @return array
     */
    private function hiddenPrivateData(array $data): array
    {

        static $hiddenFields = [
            'password', 'accessToken', 'apiKey', 'secretKey', 'token',
            'authToken', 'sessionToken', 'bearerToken', 'refreshToken', 'authCode',
            'login', 'username', 'email', 'phone', 'fullName', 'firstName', 'lastName',
            'passport', 'idNumber', 'cardNumber', 'card details', 'accountNumber', 'iban'
        ];

        foreach($data as $k => $v)
        {
            if(\is_array($v))
            {
                $data[$k] = $this->hiddenPrivateData($v);
            }

            if(\in_array($k, $hiddenFields, false))
            {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * @param array $dataPost
     * @param string $urlImplement
     * @param array $dataGet
     * @return mixed
     * @throws BspbApiException
     * @throws BspbException
     */
    public function post(array $dataPost = [], string $urlImplement = "", array $dataGet = [])
    {

        $this->client->setOpt(CURLOPT_TIMEOUT, 10);
        $getStr = (!empty($dataGet)) ? "?" . http_build_query($dataGet, '', '&') : "";

        $this->client->setUrl($this->getUrl() . $urlImplement . $getStr);
        $this->client->post(json_encode($dataPost, JSON_UNESCAPED_UNICODE));

        if ($this->logger)
        {
            $logUrl = parse_url($this->getUrl() . $urlImplement);

            $message = \sprintf(
                "POST: %s\nRequest: %s\n\n",
                $logUrl['scheme'] . '://' . $logUrl['host'] . ':' . $logUrl['port'] . $logUrl['path'],
                json_encode($this->hiddenPrivateData($dataPost))
            );

            $message .= \sprintf(
                "HttpStatus: %s\nResponse: %s\n",
                $this->client->getCode(),
                \json_encode(
                    $this->hiddenPrivateData(
                        \json_decode($this->client->getResponse(), true) ?? []
                    )
                )
            );

            if($this->client->getCode() !== 200)
            {
                $message .= sprintf(
                    "\nErrorCode: %s\nErrorMessage: %s\n",
                    $this->client->getErrorCode(),
                    $this->client->getErrorMessage()
                );
            }

            $this->logger->log('info', $message);
        }

        $this->afterCheck($dataPost);

        return $this->client->getResponse();
    }

    /**
     * @param string $urlImplement
     * @param array $dataGet
     * @return mixed
     * @throws BspbApiException
     * @throws BspbException
     */
    public function get(string $urlImplement = "", array $dataGet = [])
    {
        $this->client->setUrl($this->getUrl() . $urlImplement);

        $this->client->get($dataGet);

        if ($this->logger)
        {

            $logUrl = parse_url($this->getUrl() . $urlImplement);

            $message = \sprintf(
                "GET: %s\nRequest: %s\n\n",
                $logUrl['scheme'] . '://' . $logUrl['host'] . ':' . $logUrl['port'] . $logUrl['path'],
                json_encode($this->hiddenPrivateData($dataGet))
            );

            $message .= \sprintf(
                "HttpStatus: %s\nResponse: %s\n",
                $this->client->getCode(),
                \json_encode(
                    $this->hiddenPrivateData(
                        \json_decode($this->client->getResponse(), true) ?? []
                    )
                )
            );

            if($this->client->getCode() !== 200)
            {
                $message .= sprintf(
                    "\nErrorCode: %s\nErrorMessage: %s\n",
                    $this->client->getErrorCode(),
                    $this->client->getErrorMessage()
                );
            }

            $this->logger->log('info', $message);
        }

        $this->afterCheck('get request');

        return $this->client->getResponse();
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): BSPBApiClient
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    public function setRequestType(string $requestType): BSPBApiClient
    {
        $this->requestType = $requestType;
        return $this;
    }


    public function getMethod(): string
    {
        return $this->method;
    }


    public function setMethod(string $method): BSPBApiClient
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param $sentData
     * @return void
     * @throws BspbApiException
     * @throws BspbException
     */
    protected function afterCheck($sentData): void
    {
        if ($this->client->getErrorCode() == $this->client->getTimeoutErrCode()) {
            throw new BspbException('BSPB: Connection timed out', $this->client->getErrorCode());
        }
        if (!in_array($this->client->getCode(), $this->allowedCodeArr)) {
            throw new BspbApiException(
                'Request error',
                $this->client->getCode(),
                $this->client->getUrl(),
                $sentData,
                $this->client->getResponse()
            );
        }
    }

}
