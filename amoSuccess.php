<?php

include 'init.php';

use AmoIntegrations\AmoSettings;

$amoSettings = AmoSettings::getInstance();

if (isset($_GET['client_id']) && $_GET['client_id'] === $amoSettings->client_id) {

    if (isset($_GET['code'])) {
 
        $amoSettings->auth_token = $_GET['code'];

        $data = array(
            "client_id" => $amoSettings->client_id,
            "client_secret" => $amoSettings->client_secret,
            "grant_type" => "authorization_code",
            "code" => $amoSettings->auth_token,
            "redirect_uri" => "https://" . $_SERVER['HTTP_HOST'] . "/amoIntegrations/amoSuccess.php?domain=$domain"
        );

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $amoSettings->amo_portal . '/oauth2/access_token');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
var_dump($out);
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
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Exception $e) {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out);

        $amoSettings->refresh_token = $response->refresh_token;
        $amoSettings->access_token = $response->access_token;
        $amoSettings->token_type = $response->token_type;
        $amoSettings->setExpires($response->expires_in);

        $amoSettings->writeConfigs();
    }
    else{
        echo 'empty code';
        exit;
    }


?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Amo success</title>
    </head>

    <body>
        <script>
            if (window.opener) {
                window.opener.postMessage({
                    'error': undefined,
                    'status': 'ok'
                }, "*");
            }
        </script>
    </body>

    </html>
<?php
} else {
    echo 'Incorrect client_id';
}
