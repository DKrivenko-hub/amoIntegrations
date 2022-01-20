<?php
define('AMO_INTEGRATIONS_PATH', __DIR__);
include __DIR__ . '/amoConfig.php';

use \AmoIntegrations\AmoSettings;

function getAccessToken()
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
function curlRequest($action, array $data = array(), $request_type = "GET")
{

    $amoSettings = AmoSettings::getInstance();

    $headers = [
        'Authorization: Bearer ' . $amoSettings->access_token,
        'Content-Type:application/json'
    ];

    $response = curl($action, $headers, $amoSettings, $data, $request_type);
    return $response['response'];
}

//TODO: remove amoSettings param. change on getInstance()
function curl($action, $headers, $amoSettings, $data = array(), $request_type = "")
{
    // usleep(200000);
    $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
    /** Устанавливаем необходимые опции для сеанса cURL  */
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $amoSettings->amo_portal . $action);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (!empty($data)) {
        if ($request_type == 'PATCH') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        }
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
    } catch (\Exception $e) {
        global $domain;
        // var_dump($out);
        $error = "\r\n\r\n-----------------------------" . date('d.m.Y H:i:s') . "-----------------------------\r\n\r\n";
        $error .= "data: " . json_encode($data) . "\r\n";
        $error .= "action: " . $action . "\r\n";
        $error .= "error: " . $e->getMessage() . "code: " . $e->getCode() . "\r\n";
        $error .= "response: " . $out . "\r\n";
        $error .= "domain: " . $domain . "\r\n";
        file_put_contents(__DIR__ . '/errors.log', $error, FILE_APPEND);
        sendTgMessage($error);
        die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
    }

    return array('response' => json_decode($out, true), 'code' => $code);
}

function SafeString($data, $limit = 0, $noTags = TRUE)
{
    $search  = array("\\\\",  "\\0",  "\\n",  "\\r",  "\Z",    "\'",  '\"',  '"',       "'");
    $replace = array("\\",    "\0",   "\n",   "\r",   "\x1a",  "'",   '"',   '&quot;',  '&#039;');

    $search  = array("\\\\",  "\\0",  "\\n",  "\\r",  "\Z",    "\'",  '\"');
    $replace = array("\\",    "\0",   "\n",   "\r",   "\x1a",  "'",   '"');

    $data = Str_Replace($search, $replace, Trim($data));


    if ($limit > 0) {
        $data = SubStr($data, 0, $limit);
    }

    if ($noTags != FALSE) {
        $data = Strip_Tags($data);
    }

    return Trim($data);
}

function formatPhone($phone)
{
    return preg_replace('~\D+~', '', $phone);
}
function CompareValues($value1, $value2)
{
    $value1 = mb_substr(strrev($value1), 0, 10);
    $value2 = mb_substr(strrev($value2), 0, 10);

    if (empty($value1) || empty($value2)) {
        return false;
    }

    if ($value1 == $value2) {
        return true;
    }
    return false;
}

function getGAID(array &$data)
{
    if (empty($data['textfields']['gaId'])) {
        if (isset($data['_ga'])) {
            try {
                $tmp = explode('.', $data['_ga']);
                if (count($tmp) == 4) {
                    list($version, $domainDepth, $cid1, $cid2) = $tmp;
                    $data['textfields']['gaId'] = $cid1 . '.' . $cid2;
                }
            } catch (Exception $e) {
            }
        }
    }
}

function checkUserId($responsible_user_id)
{
    $amoSettings = AmoSettings::getInstance();

    $headers = [
        'Authorization: Bearer ' . $amoSettings->access_token,
        'Content-Type:application/json'
    ];

    $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
    /** Устанавливаем необходимые опции для сеанса cURL  */
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
    curl_setopt($curl, CURLOPT_URL, $amoSettings->amo_portal . '/api/v4/users/' . $responsible_user_id);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    try {
        /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
        if (($code >= 200 && $code <= 204)) {
            return (int)$responsible_user_id;
        } else {
            return (int)$amoSettings->default_responsible_user;
        }
    } catch (\Exception $e) {
        global $domain;
        $error = "\r\n\r\n-----------------------------" . date('d.m.Y H:i:s') . "-----------------------------\r\n\r\n";
        // $error .= "data: " . json_encode() . "\r\n";
        $error .= "action: /api/v4/users/" . $responsible_user_id  . "\r\n";
        $error .= "error: " . $e->getMessage() . "code: " . $e->getCode() . "\r\n";
        $error .= "response: " . $out . "\r\n";
        $error .= "domain: " . $domain . "\r\n";
        file_put_contents(__DIR__ . '/errors.log', $error, FILE_APPEND);
        sendTgMessage($error);
        die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
    }
}

function log_form($form)
{
    global $domain;

    require_once AMO_INTEGRATIONS_PATH . '/DB_mySQLi.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

    $db = new DB_mySQLi([
        'db_host' => DB_HOST,
        'db_pass' => DB_PASSWORD,
        'db_user' => DB_USER,
        'db_name' => DB_NAME
    ]);
    if (!is_string($form)) {
        $form = json_encode(array($form));
    }
    $db->myQuery("INSERT INTO forms_log (`domain`, `data`) VALUES('$domain', '$form')");
}

function fixcontinfo($continfo)
{
    if (!empty($continfo[0])) {
        foreach ($continfo[0]['custom_fields_values'] as $cfv_id => $cfv) {
            if ($cfv['values']) {
                foreach ($cfv['values'] as $vals_id => $vals) {
                    if (array_key_exists('enum_code', $vals) && is_null($vals['enum_code'])) {
                        unset($continfo[0]['custom_fields_values'][$cfv_id]['values'][$vals_id]['enum_code']);
                    }
                    if (array_key_exists('field_code', $vals) && is_null($vals['field_code'])) {
                        unset($continfo[0]['custom_fields_values'][$cfv_id]['values'][$vals_id]['field_code']);
                    }
                }
            }
        }
    }
    return $continfo;
}

function sendTgMessage($message)
{

    $text = $message;

    $url  = 'https://api.telegram.org/bot879352609:AAGnLbCX4watFtnWWQPzPLVKS8fb76KIH2A/sendMessage';
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));  
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array(
        "chat_id"    => "-758415180",
        "parse_mode" => 'html',
        "text"       => $text
    ), JSON_UNESCAPED_SLASHES));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $out = curl_exec($curl);

    curl_close($curl);
}
