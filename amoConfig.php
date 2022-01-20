<?php


namespace AmoIntegrations;

class AmoSettings
{
    private $client_id = '';
    private $client_secret = '';

    private $amo_portal = '';

    private $token = '';

    //settings for portal
    private $contacts = '';
    private $leads = array();
    private $notes = '';

    public $auth_token = '';
    public $access_token = '';
    public $refresh_token = '';
    public $token_type = '';

    private $path = '';

    public $expires_in = '';
    public $expires_date = '';

    private static $instance;

    public function __construct($path = '')
    {
        global $domain;
        $this->path = $path ?? AMO_INTEGRATIONS_PATH . '/' . $domain . '/amoConfigs.json';
        $this->readConfigs();
    }
    function __get($name)
    {
        if ($name !== 'instance') {
            return $this->{$name};
        }
    }
    function __set($name, $value)
    {
        user_error("Can't set property: " . __CLASS__ . "->$name");
    }

    public function writeConfigs()
    {
        $json = array();
        $reflect = new \ReflectionClass(__CLASS__);
        $props   = $reflect->getProperties();
        foreach ($props as $prop) {
            if ($prop->getName() !== 'instance' && $prop->getName() !== 'path') {
                $json[$prop->getName()] = $this->{$prop->getName()};
            }
        }
        $json = json_encode($json);

        file_put_contents($this->path, $json);
    }

    public function readConfigs()
    {

        if (file_exists($this->path)) {
            $configs = file_get_contents($this->path);
            if ($configs) {
                $configs = json_decode($configs, true);
                if ($configs) {
                    foreach ($configs as $name => $val) {
                        if ($val) {
                            $this->{$name} = $val;
                        } else {
                            $this->{$name} = array();
                        }
                    }
                }
            }
        }
    }
    public function setExpires(int $timestamp)
    {

        $this->expires_date = strtotime((new \DateTime('+' . $timestamp . 'seconds'))->format('d.m.Y H:i:s'));
        $this->expires_in = $timestamp;
    }

    public function getPipeline(string $pipelineName)
    {
        if (isset($this->leads['pipeline'][$pipelineName])) {
            return $this->leads['pipeline'][$pipelineName];
        }
        return $this->leads['pipeline']['default'];
    }
    public function setPipeline(int $pipelineId, $pipeline_stage = 0, string $pipelineName = '')
    {
        if ($pipelineId > 1) {
            if (!$pipelineName) {
                $pipelineName = 'default';
            }
            // var_dump($this->leads);
            $this->leads['pipeline'][$pipelineName] = array(
                'status_id' => $pipeline_stage,
                'pipeline_id' => $pipelineId
            );
        }
    }

    public static function getInstance($path = '')
    {
        if (empty(self::$instance)) {
            self::$instance = new AmoSettings($path);
        }
        return self::$instance;
    }
    public function destruct()
    {
        self::$instance = NULL;
    }
}
