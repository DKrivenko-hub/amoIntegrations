<?php


global $domain;


if (isset($_GET['domain'])) {
    switch ($_GET['domain']) {
        case 'maksbartko.com.ua':
        case 'maksbartko':
            $domain = 'maksbartko.com.ua';
            $path = 'domains/maksbartko.com.ua';
            break;
        case 'kiev.logicaschool.com':
            $domain = $_GET['domain'];
            $path = 'marquiz/' . $_GET['domain'];
            break;
        case 'egolist':
            $domain = $_GET['domain'];
            $path = 'binotel/' . $_GET['domain'];
            break;
        case 'zona-comforta.in.ua':
        case 'freshcode.training':
        case 'glassmtech':
            $domain = $_GET['domain'];
            $path = 'domains/' . $_GET['domain'];
            break;
    }
}
include_once __DIR__ . '/functions.php';

if (strpos($_SERVER['HTTP_REFERER'], '/domains/') === FALSE) {
    if (!isset($domain) || empty($domain)) {
        echo 'Incorrect domain';
        exit;
    } else if (!file_exists(AMO_INTEGRATIONS_PATH .'/' . $path . '/amoConfigs.json')) {
        echo 'Configs not exists';
        exit;
    }
}

try {
   $amoSettings = \AmoIntegrations\AmoSettings::getInstance(AMO_INTEGRATIONS_PATH .'/' . $path . '/amoConfigs.json');
} catch (\Throwable $th) {
    throw new ErrorException('config not exists');
}
