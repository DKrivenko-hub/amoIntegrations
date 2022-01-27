<?php

// var_dump($_SERVER);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

use \AmoIntegrations\AmoSettings;

$amoSettings = AmoSettings::getInstance();
var_dump($_SERVER);
