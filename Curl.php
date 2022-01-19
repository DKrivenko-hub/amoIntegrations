<?php


namespace AmoIntegrations;

use CurlHandle;

class Curl
{

    private CurlHandle $connect;

    private array $default_options = [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_HEADER => true
    ];

    private array $client_options = [];

    public function __construct()
    {
        $this->connect = curl_init();
    }

    public function setOptions(array $options)
    {
        if (!empty($options)) {
            $this->client_options = array_merge($this->client_options, $options);

            return true;
        }
        return false;
    }

    public function setTypeRequest(ERequestTypes $requestType)
    {
        if ($requestType !== 'GET') {
            $this->client_options[CURLOPT_CUSTOMREQUEST] = $requestType;
            return true;
        }
        return false;
    }

    public function setHeaders(array $headers)
    {
        if (!empty($headers)) {
            $this->client_options[CURLOPT_HTTPHEADER] = $headers;
            return true;
        }
        return false;
    }

    public function SetData($data, ?ERequestTypes $requestType)
    {
        if (!empty($data)) {
            $this->client_options[CURLOPT_CUSTOMREQUEST] = $requestType;
            $this->client_options[CURLOPT_POSTFIELDS] = !is_string($data) ? json_encode($data) : $data;
            return true;
        }
        return false;
    }

    public function execute(): array
    {

        $options = $this->default_options;

        if (!empty($this->client_options)) {
            $options = array_merge($this->default_options, $this->client_options);
        }

        curl_setopt_array($this->connect, $options);

        $out = curl_exec($this->connect);

        return ['response' => $out, 'code' => curl_getinfo($this->connect, CURLINFO_RESPONSE_CODE)];
    }

    public function __destruct()
    {
        curl_close($this->connect);
    }
}


class ERequestTypes
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
}
