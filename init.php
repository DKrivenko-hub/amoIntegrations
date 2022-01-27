<?php

global $domain;

if (isset($_GET['domain'])) {
    switch ($_GET['domain']) {
        case 'server11.pp.ua':
            $domain = 'server111.pp.ua';
            define('AMO_DOMAIN_PATH', __DIR__ . '/domains/sites/server111.pp.ua');
            break;
    }
}

require_once __DIR__ . '/AmoIntegrations/AmoAutoload.php';

if (!isset($domain) || empty($domain)) {
    $request_uri = explode('/', $_SERVER['REQUEST_URI']);
    array_pop($request_uri);
    $request_uri = implode('/', $request_uri);
    if ($request_uri) {
        $domain = basename($request_uri);
        define('AMO_DOMAIN_PATH', __DIR__ . $request_uri);
    } else {
        echo 'Incorrect domain';
        exit;
    }
}


try {
    $amoSettings = \AmoIntegrations\AmoSettings::getInstance();
} catch (\Throwable $th) {
    throw new ErrorException('config not exists');
}
