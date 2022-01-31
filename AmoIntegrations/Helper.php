<?php

namespace AmoIntegrations;

use \AmoIntegrations\Enums\ERequestTypes;
use \AmoIntegrations\Curl;

trait Helper
{
    public function sendTgMessage($message)
    {

        $text = $message;

        $curl = Curl::getInstance();
        $url  = 'https://api.telegram.org/bot111111111111/sendMessage';

        $curl->SetData(json_encode([
            "chat_id"    => "-1111111111",
            "parse_mode" => 'html',
            "text"       => $text
        ], JSON_UNESCAPED_SLASHES), ERequestTypes::POST);

        $curl->SetOptions(
            [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            ]
        );

        $response = $curl->execute();
        return $response;
    }

    function formatPhone($phone)
    {
        return preg_replace('~\D+~', '', $phone);
    }

    function CompareValues($value1, $value2)
    {
        if (empty($value1) || empty($value2)) {
            return false;
        }

        if ($value1 == $value2) {
            return true;
        }
        return false;
    }

    public function getGAID(array &$data)
    {
        if (empty($data['textfields']['gaId'])) {
            if (isset($data['_ga'])) {
                try {
                    $tmp = explode('.', $data['_ga']);
                    if (count($tmp) == 4) {
                        list($version, $domainDepth, $cid1, $cid2) = $tmp;
                        $data['textfields']['gaId'] = $cid1 . '.' . $cid2;
                    }
                } catch (\Exception $e) {
                }
            }
        }
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


    public function getDirs($parent_dir)
    {
        $dir_key = basename($parent_dir);
        $result = [];
        foreach (scandir($parent_dir) as $filename) {
            if ($filename[0] === '.') continue;
            $filePath = $parent_dir . '/' . $filename;
            if (is_dir($filePath)) {
                $result[$dir_key][$filename] = $this->getDirs($filePath)[$filename];
            } else {
                $result[$dir_key][] = $filename;
            }
        }
        return $result;
    }
}
