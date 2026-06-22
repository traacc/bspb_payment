<?php

namespace BspbSDK\Client;

use BspbSDK\Exceptions\BspbException;

class Curl implements ClientInterface
{
    /**
     * @var null | resource
     */
    private $client;

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var mixed
     */
    private $response;
    /**
     * @var null|int
     */
    private $code;
    /**
     * @var int
     */
    private $curlErrorCode = 0;

    /**
     * @var string|null
     */
    private $curlErrorMessage = null;

    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new BspbException('No CURL library');
        }
        $timeout = 60; //sec
        $this->client = curl_init();
        curl_setopt_array($this->client,[
            CURLOPT_TIMEOUT_MS => $timeout * 1000,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json'
            ],
        ]);
    }

    public function setCertPem(string $pem)
    {
        $this->setOpt(CURLOPT_SSLCERT, $pem);
        return $this;
    }

    public function setCertKey(string $key)
    {
        $this->setOpt(CURLOPT_SSLKEY, $key);
        return $this;
    }

    public function setAuthData(string $merchantId, string $password)
    {
        $this->setOpt(CURLOPT_USERPWD, 'TerminalSys/'.$merchantId.':'.$password);
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function config(array $args)
    {
        curl_setopt_array($this->client, $args);
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function get(array $data = [])
    {
        if ($data) {
            if (strpos($this->url, '?') !== false) {
                $this->url = substr($this->url, 0, strpos($this->url, '?'));
            }
            $this->url .= '?' . http_build_query($data,'','&');
        }
        $this->request();
        return $this;
    }

    /**
     * @param string $data
     * @return $this
     */
    public function post(string $data = '')
    {
        $this->setOpt(CURLOPT_POST, TRUE);
        if ($data) {
            $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }
        $this->request();
        return $this;
    }

    /**
     * @param int $opt
     * @param mixed $val
     * @return $this
     */
    public function setOpt(int $opt, $val)
    {
        curl_setopt($this->client, $opt, $val);
        return $this;
    }

    /**
     * @param bool $close
     * @return $this
     */
    private function request()
    {
        $this->setOpt(CURLOPT_URL, $this->url);
        $this->response = curl_exec($this->client);
        $this->code = curl_getinfo($this->client, CURLINFO_HTTP_CODE);

        if ($this->code !== 200)
        {
            $this->curlErrorCode = curl_errno($this->client);
            $this->curlErrorMessage = curl_error($this->client);
        }

        curl_close($this->client);
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->curlErrorCode;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->curlErrorMessage;
    }

    public function getTimeoutErrCode():int
    {
        return CURLE_OPERATION_TIMEDOUT;
    }


}
