<?

namespace AmoIntegrations\Models;

use AmoIntegrations\AmoSettings;
use \AmoIntegrations\Curl;

class Model{

    private Curl $curl;

    protected AmoSettings $amoSettings;

    protected $headers;

    public function __construct(){
        $this->curl = new Curl();
        $this->amoSettings = AmoSettings::getInstance();

        $this->headers = [
            'Authorization: Bearer ' . $this->amoSettings->access_token,
            'Content-Type:application/json'
        ];

        $this->curl->setHeaders($this->headers);

    }

    protected function processResponse($response){
        
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
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if (($code < 200 || $code > 204)) {
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Throwable $e) {
            file_put_contents(__DIR__ . '/debug.log', "\r\n------------------------" . date('d.m.Y H:i:s') . "--------------------\r\n" . 'data: ' . json_encode($data) . "\r\n action: " . $action . "response: " . $e->getMessage() . " code: $code \r\n\r\n\r\n", FILE_APPEND);
            die();
        }
    
    
    }

    protected function execute(){
        $response = $this->curl->execute();
        $response = $this->processResponse($response);

        return $response;
    }
}