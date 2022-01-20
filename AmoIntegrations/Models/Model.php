<?

namespace AmoIntegrations\Models;

use AmoIntegrations\AmoSettings;
use \AmoIntegrations\Curl;
use \AmoIntegrations\ERequestTypes;

class Model
{

    protected Curl $curl;

    protected AmoSettings $amoSettings;

    protected $headers;


    public function __construct()
    {
        $this->curl = Curl::getInstance();
        $this->amoSettings = AmoSettings::getInstance();

        $this->headers = [
            'Authorization: Bearer ' . $this->amoSettings->access_token,
            'Content-Type:application/json'
        ];

        // $this->curl->setHeaders($this->headers);
    }


    protected function fixNullFields($data)
    {
        foreach ($data as $k => &$v) {
            if (is_array($v)) {
                $v = $this->fixNullFields($v);
            } else {
                if (is_null($v)) {
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }
}
