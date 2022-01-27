<?php

include 'init.php';

use AmoIntegrations\AmoSettings;
use AmoIntegrations\Curl;
use AmoIntegrations\Enums\ERequestTypes;

$amoSettings = AmoSettings::getInstance();

if (isset($_GET['client_id']) && $_GET['client_id'] === $amoSettings->client_id) {

    if (isset($_GET['code'])) {

        $amoSettings->auth_token = $_GET['code'];
        $data = array(
            "client_id" => $amoSettings->client_id,
            "client_secret" => $amoSettings->client_secret,
            "grant_type" => "authorization_code",
            "code" => $amoSettings->auth_token,
            "redirect_uri" => "https://$_SERVER[HTTP_HOST]/amoSuccess.php?domain=$domain"
        );

        $curl = Curl::getInstance();
        $curl->SetOptions([
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
            CURLOPT_URL => $amoSettings->amo_portal . '/oauth2/access_token',
            CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $curl->SetData($data, ERequestTypes::POST);


        $response = $curl->execute();

        $response = json_decode($response['response'], flags: JSON_THROW_ON_ERROR);

        $amoSettings->refresh_token = $response->refresh_token;
        $amoSettings->access_token = $response->access_token;
        $amoSettings->token_type = $response->token_type;
        $amoSettings->setExpires($response->expires_in);

        $amoSettings->writeConfigs();
        
    } else {
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
