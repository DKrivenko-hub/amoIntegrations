<?php
include __DIR__ . '/amoConfig.php';

use \AmoIntegrations\AmoSettings;

function amo_getAccessToken()
{
    $amoSettings = AmoSettings::getInstance();
    $data = array(
        'client_id' => $amoSettings->client_id,
        'client_secret' => $amoSettings->client_secret,
        'grant_type' => 'refresh_token',
        'refresh_token' => $amoSettings->refresh_token,
    );
    $response = curl('/oauth2/access_token', array('Content-Type:application/json'), $amoSettings, $data);

    $amoSettings->refresh_token = $response['response']['refresh_token'];
    $amoSettings->access_token = $response['response']['access_token'];
    $amoSettings->token_type = $response['response']['token_type'];

    $amoSettings->setExpires((int)$response['response']['expires_in']);

    $amoSettings->writeConfigs();
}

function amo_curlRequest($action, array $data = array())
{
    $amoSettings = AmoSettings::getInstance();
    $headers = [
        'Authorization: Bearer ' . $amoSettings->access_token,
        'Content-Type:application/json'
    ];

    $response = curl($action, $headers, $data);
    return $response['response'];
}


function amo_curl($action, $headers, $data = array())
{
    $amoSettings = AmoSettings::getInstance();
    $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
    /** Устанавливаем необходимые опции для сеанса cURL  */
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $amoSettings->amo_portal . $action);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if ($data) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
    $code = (int)$code;
    $errors = [
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];

    try {
        /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
        if (($code < 200 || $code > 204)) {
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
        }
    } catch (\Throwable $e) {
        file_put_contents(__DIR__ . '/debug.log', "\r\n------------------------" . date('d.m.Y H:i:s') . "--------------------\r\n" . 'data: ' . json_encode($data) . "\r\n action: " . $action . "response: " . $e->getMessage() . " code: $code \r\n\r\n\r\n", FILE_APPEND);
    }


    return array('response' => json_decode($out, true), 'code' => $code);
}

function amo_SafeString($data, $limit = 0, $noTags = TRUE)
{
    $search = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"', '"', "'");
    $replace = array("\\", "\0", "\n", "\r", "\x1a", "'", '"', '&quot;', '&#039;');

    $search = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
    $replace = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');

    $data = Str_Replace($search, $replace, Trim($data));


    if ($limit > 0) {
        $data = SubStr($data, 0, $limit);
    }

    if ($noTags != FALSE) {
        $data = Strip_Tags($data);
    }

    return Trim($data);
}

function amo_formatPhone($phone)
{
    return preg_replace('~\D+~', '', $phone);
}

function amo_ComparePhones($phone1, $phone2)
{
    $phone1 = mb_substr(strrev(formatPhone($phone1)), 0, 10);
    $phone2 = mb_substr(strrev(formatPhone($phone2)), 0, 10);

    if (empty($phone1) || empty($phone2)) {
        return false;
    }

    if ($phone1 == $phone2) {
        return true;
    }
    return false;
}
