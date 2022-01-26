<?php 

namespace AmoIntegrations\Interfaces;

interface IConnection extends IQueryable{

    public static function connect();

    public function execute();

    public function processResponse();

    public function __destruct();

}