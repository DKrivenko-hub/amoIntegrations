<?php

// namespace AmoIntegrations;

include_once __DIR__.'/AmoIntegrations/AmoAutoload.php';

$amo_settings = \AmoIntegrations\AmoSettings::getInstance();
use AmoIntegrations\AmoSettings;
class Test
{
    use \AmoIntegrations\Helper;
}


$test = new Test();
echo '<pre>';
$domain = 'server111.pp.ua';
$dirs = $test->getDirs($_SERVER['DOCUMENT_ROOT'] . '/domains');
foreach ($dirs as $site_type => $sites) {
    foreach ($sites as $path => $site) {
        if (strpos($path, $domain) !== false && ($key = array_search('amoConfigs.json', $site)) !== false) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . "/$site_type/$path/$site[$key]";
            break 2;
        }
    }
}
