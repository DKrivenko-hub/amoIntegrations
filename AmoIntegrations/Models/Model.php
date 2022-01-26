<?

namespace AmoIntegrations\Models;

use AmoIntegrations\AmoSettings;
use AmoIntegrations\Curl;

class Model
{

    protected $connect;

    protected AmoSettings $amoSettings;

    protected $headers = [];

    public function __construct()
    {
        $this->connection = Curl::getInstance();

        $this->amoSettings = AmoSettings::getInstance();

        $this->headers = [
            'Authorization: Bearer ' . $this->amoSettings->access_token,
            'Content-Type:application/json'
        ];
    }

    protected function setDefaultOptions()
    {
        $this->connection->setOptions([
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
        ]);

        $this->connection->setHeaders($this->headers);

        $this->connection->isSslVerify();
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
