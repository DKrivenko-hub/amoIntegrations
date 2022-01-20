<?php

include_once __DIR__ . '/functions.php';

use \AmoIntegrations\AmoSettings;

$parent_dirs = [
    'domains',
    'binotel',
    'marquiz'
];
$domains = [];

// foreach ($parent_dirs as $parent_dir) {
//     $dirs = scandir(__DIR__ . '/' . $parent_dir);

//     foreach ($dirs as $dir) {
//         if ($dir !== '.' && $dir !== '..') {
//             if(is_dir(__DIR__ . '/' . $parent_dir . '/' . $dir)){
//                 $domains[] = __DIR__ . '/' . $parent_dir . '/' . $dir;
//             }
//             if (dir(__DIR__ . '/' . $parent_dir . '/' . $dir.'/'.'block_configs') === false) {
//                 mkdir($domain . 'block_configs');
//             }
//         }
//     }
// }

$domains = [
    __DIR__."/marquiz/kiev.logicaschool.com/",
    __DIR__."/binotel/egolist/",
    __DIR__."/domains/glassmtech/",
    __DIR__."/domains/freshcode.training/",
];


foreach ($domains as $domain) {
    $dir = scandir($domain . 'block_configs');

    foreach ($dir as $file) {
        if ($file !== '.' && $file !== '..') {
            if (fileatime($domain . 'block_configs/' . $file) > strtotime('+20 minutes')) {
                unlink($domain . 'block_configs/' . $file);
            } else {
                exit;
            }
        }
    }


    // unset($amoSettings);
    $amoSettings = AmoSettings::getInstance($domain . 'amoConfigs.json');
    getAccessToken();
    $amoSettings->destruct();
}
