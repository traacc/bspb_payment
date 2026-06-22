<?php

namespace BspbSDK\Client;

interface ClientInterface
{

	public function __construct();

	public function setOpt(int $opt, $val);

	public function config(array $args);

	public function setUrl(string $url);
	public function getUrl(): string;

    public function setCertPem(string $pem);
    public function setCertKey(string $key);
    public function setAuthData(string $merchantId, string $password);

	public function get(array $data = []);
	public function post(string $data = '');

	public function getResponse();
	public function getCode();
	public function getErrorCode(): int;
	public function getErrorMessage(): ?string;

    public function getTimeoutErrCode(): int;

}
