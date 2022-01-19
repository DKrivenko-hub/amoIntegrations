<?

namespace AmoIntegrations\Models;

use AmoIntegrations\AmoSettings;
use \AmoIntegrations\Curl;

class Model{

    private $curl;

    protected $amoSettings;

    public function __construct(){
        $this->curl = new Curl();
        $this->amoSettings = AmoSettings::getInstance();
    }

    protected function processData(){

    }
}