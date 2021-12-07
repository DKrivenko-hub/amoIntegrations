<?php

$url = '';
parseUtm($url);

function parseUtm($url = '')
{
    if (!empty($url)) {
        parse_str($url, $get);
    } else {
        $get = $_GET;
    }

    $HTTP_REFERER = '' . urlDecode((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-'));

    if (!isset($_SESSION) or !$_SESSION) {
        session_start();
    }
    #================================================================================


    #================================================================================
    $UTM_Array = array(
        'HTTP_REFERER' => '',
        'RefererHost' => '',
        'utm_source' => '',
        'utm_medium' => '',
        'utm_campaign' => '',
        'utm_term' => '',
        'utm_content' => ''
    );
    foreach ($UTM_Array as $key => $value) {
        $UTM_Array[$key] = (isset($get[$key]) ? Trim($get[$key]) : (isset($_SESSION[$key]) ? Trim($_SESSION[$key]) : ''));
    }
    unset($value);
    #================================================================================


    #================================================================================
    $sourceGroup = 'default';

    // 1. Try from _SESSION
    if (isset($_SESSION['sourceGroup']) and !empty($_SESSION['sourceGroup'])) {
        $sourceGroup = $_SESSION['sourceGroup'];
    }

    // 2. Try from UTM
    fixUTM_Array($sourceGroup, $UTM_Array);


    $cookieTime = time() + (86400 * 30); // 86400 = 1 day
    $UTM_Array2 = array('RefererHost', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');

    foreach ($UTM_Array2 as $key2) {
        if (!empty($UTM_Array[$key2])) {
            $_SESSION[$key2] = $UTM_Array[$key2];
        }
    }
    unset($key2);
    unset($UTM_Array2);


    if ($sourceGroup != 'default') {
        $_SESSION['sourceGroup'] = $sourceGroup;
    }
    unset($cookieTime);
    #================================================================================
    if (empty($UTM_Array['utm_source']) || empty($UTM_Array['utm_campaign']) || isset($_SESSION['sp_utm_date'])) {
        return $UTM_Array;
    }
    $_SESSION['sp_utm_date'] = time();
    return $UTM_Array;
}

function fixUTM_Array(&$sourceGroup, &$UTM_Array)
{
    $Referer = '-';
    $RefererHost = $_SERVER['SERVER_NAME'];

    if (isset($_SERVER['HTTP_REFERER']) and !empty($_SERVER['HTTP_REFERER'])) {
        $Referer = urlDecode($_SERVER['HTTP_REFERER']);

        $uri = parse_URL($Referer);
        $RefererHost = (isset($uri['host']) ? str_Replace('www.', '', strToLower($uri['host'])) : '-');

        if (empty($UTM_Array['HTTP_REFERER']) and ($RefererHost != $_SERVER['HTTP_HOST'])) {
            $UTM_Array['HTTP_REFERER'] = $Referer;
        }
    }


    if (isset($_GET['gclid'])) {
        $UTM_Array['utm_source'] = 'google';
        $UTM_Array['utm_medium'] = 'cpc';
        $sourceGroup = 'GoogleCPC';  #GoogleCPC

    } else if (isset($_GET['utm_group']) and !empty($_GET['utm_group'])) {
        $UTM_Array['utm_source'] = 'google';
        $UTM_Array['utm_medium'] = 'cpc';
        $sourceGroup = 'GoogleCPC';  #GoogleCPC

    } else if (isset($_GET['placement']) and ($_GET['placement'] == 'www.youtube.com')) {
        $UTM_Array['utm_source'] = 'google';
        $UTM_Array['utm_medium'] = 'cpc';
        $sourceGroup = 'GoogleCPC';  #GoogleCPC

    } else if ((($UTM_Array['utm_source'] == 'adwords.google.com'))
        or (($UTM_Array['utm_source'] == 'merchants.google.com'))
        or (($UTM_Array['utm_medium'] == 'cpc') and (($UTM_Array['utm_source'] == 'google')))
    ) {
        $sourceGroup = 'GoogleCPC';  #GoogleCPC

    } else if (
        isset($_GET['yclid'])
        or (($UTM_Array['utm_medium'] == 'cpc') and ($UTM_Array['utm_source'] == 'yandex'))
        or ($UTM_Array['utm_source'] == 'eLama-yandex')
    ) {
        $UTM_Array['utm_source'] = 'yandex';
        $UTM_Array['utm_medium'] = 'cpc';
        $sourceGroup = 'YandexCPC';  #YandexCPC

    } else if (
        ($RefererHost == 'track.price.ru') or

        ($UTM_Array['utm_source'] == 'irr') or
        ($UTM_Array['utm_source'] == 'begun') or
        ($UTM_Array['utm_source'] == 'priceru') or
        ($UTM_Array['utm_source'] == 'track.price.ru')
    ) {
        // $sourceGroup = 'BDO';  #BDO

    } else if (($RefererHost != '-') and ($RefererHost != $_SERVER['HTTP_HOST'])) {

        if (!isset($UTM_Array['utm_source']) || empty($UTM_Array['utm_source'])) {
            $UTM_Array['utm_source'] = $RefererHost;
            $UTM_Array['utm_medium'] = 'referral';
        }
    }

    $UTM_Array['RefererHost'] = $RefererHost;
}
  