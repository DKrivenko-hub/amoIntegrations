<?php

namespace AmoIntegrations;

use ErrorException;

class AmoSettings
{
    private $client_id = '';
    private $client_secret = '';

    private $amo_portal = '';

    private $token = '';

    //settings for portal
    private $contacts = '';
    private $leads = '';

    public $auth_token = '';
    public $access_token = '';
    public $refresh_token = '';
    public $token_type = '';

    public $expires_in = '';
    public $expires_date = '';




    public function __construct()
    {
        $this->readConfigs();
    }
    function __get($name)
    {
        return $this->{$name};
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
            $json[$prop->getName()] = $this->{$prop->getName()};
        }
        $json = json_encode($json);

        file_put_contents(__DIR__. '/configs.json', $json);
    }

    public function readConfigs()
    {
        if (file_exists(__DIR__ .'/configs.json')) {
            $configs = file_get_contents(__DIR__ . '/configs.json');
            if ($configs) {
                $configs = json_decode($configs, true);
                if ($configs) {
                    foreach ($configs as $name => $val) {
                        $this->{$name} = $val;
                    }
                } else {
                    throw new ErrorException('empty configs');
                }
            } else {
                throw new ErrorException('can`t read configs');
            }
        } else {
            throw new ErrorException('configs.json not exists');
        }
    }
    public function setExpires(int $timestamp)
    {

        $this->expires_date = strtotime((new \DateTime('+' . $timestamp . ' seconds'))->format('d.m.Y H:i:s'));
        $this->expires_in = $timestamp;
    }

    public function getPipeline(string $pipelineName)
    {
        if (isset($this->leads['pipeline'][$pipelineName])) {
            return $this->leads['pipeline'][$pipelineName];
        }
        return $this->leads['pipeline']['default'];
    }
}
