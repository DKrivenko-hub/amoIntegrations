<?php


namespace AmoIntegrations;

use CurlHandle;
use AmoIntegrations\Enums\ERequestTypes;


class Curl
{
    use Helper;

    private CurlHandle $connect;

    private AmoSettings $amoSettings;

    private string $last_action = '';

    private static $instance;

    private array $default_options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYHOST => 1,
        CURLOPT_SSL_VERIFYPEER => 2,
    ];

    private array $client_options = [];


    private function __construct()
    {
        $this->amoSettings = AmoSettings::getInstance();
        $this->connect = curl_init();
    }

    public function SetOptions(array $options)
    {
        if (!empty($options)) {
            $this->client_options = $options + $this->client_options;
            return true;
        }
        return false;
    }

    public function SetTypeRequest(ERequestTypes $requestType)
    {
        $requestType = $requestType->value;
        if ($requestType !== 'GET') {
            $this->client_options[CURLOPT_CUSTOMREQUEST] = $requestType;
            return true;
        }
        return false;
    }

    public function SetHeaders(array $headers)
    {
        if (!empty($headers)) {
            $this->client_options[CURLOPT_HTTPHEADER] = $headers;
            return true;
        }
        return false;
    }

    public function SetData($data, ERequestTypes $requestType = null)
    {
        if (!empty($data)) {
            if (!empty($requestType)) {
                $requestType = $requestType->value;
            }
            if (empty($requestType)) {
                $requestType = ERequestTypes::POST->value;
            }
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
            $options =  $this->client_options + $this->default_options;
        }

        if (!isset($options[CURLOPT_URL])) {
            throw new \Exception('empty url');
        }

        $this->last_action = $options[CURLOPT_URL];

        curl_setopt_array($this->connect, $options);

        $out = curl_exec($this->connect);

        $response =  [
            'response' => $out,
            'code' => curl_getinfo($this->connect, CURLINFO_RESPONSE_CODE)
        ];

        curl_reset($this->connect);

        return $this->processResponse($response);
    }


    public function processResponse($response)
    {
        $code = (int)$response['code'];
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            /** ???????? ?????? ???????????? ???? ???????????????? - ???????????????????? ?????????????????? ???? ????????????  */
            if (($code < 200 || $code > 204)) {
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Throwable $e) {
            $error = "\r\n------------------------" . date('d.m.Y H:i:s') . "--------------------\r\n" . 'data: ' . json_encode($response['response']) . "\r\n action: " . $this->last_action . "response: " . $e->getMessage() . " code: $code \r\n\r\n\r\n";
            file_put_contents(__DIR__ . '/debug.log', $error, FILE_APPEND);
            $this->sendTgMessage($error);
            throw $e;
        }
        return $response;
    }

    public function __destruct()
    {
        curl_close($this->connect);
    }


    /** 
     *  @return Curl
     **/
    public static function getInstance(): Curl
    {
        if (is_null(self::$instance)) {
            self::$instance = new Curl();
        }
        return self::$instance;
    }
}
