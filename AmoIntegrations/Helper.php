<?php 

namespace AmoIntegrations;

trait Helper
{
    public function sendTgMessage($message)
    {

        $text = $message;

        $curl = Curl::getInstance();
        $url  = 'https://api.telegram.org/bot879352609:AAGnLbCX4watFtnWWQPzPLVKS8fb76KIH2A/sendMessage';

        $curl->isSslVerify();

        $curl->SetData(json_encode([
            "chat_id"    => "-758415180",
            "parse_mode" => 'html',
            "text"       => $text
        ], JSON_UNESCAPED_SLASHES), ERequestTypes::POST);

        $this->curl->SetOptions(
            [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            ]
        );

        $response = $curl->execute();
        return $response;
    }
}
