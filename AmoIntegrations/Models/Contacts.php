<?php

namespace AmoIntegrations\Models;

class Contacts extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function find(string $value)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts?query=$value";
        $this->curl->setOptions([
            CURLOPT_URL=>$url,
        ]);
        $this->curl->setHeaders($this->headers);
        $this->curl->isSslVerify();
            // $response = $this->execute();

        return $response ?? [];
    }

    public function findById(int $id)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts/$id";

        // $response = $this->execute();

        return $response ?? [];
    }

    public function update(int $id, array $data)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts/$id";

        $data = $this->prepareData($data);
    }

    public function add(array $data)
    {
        $url = $this->amoSettings->amo_portal . '/api/v4/contacts';

        $data = $this->prepareData($data);

    }

    private function prepareData(array $data)
    {
        $amo_data = [];
        if (isset($data['responsible_user_id'])) {
            $amo_data[0]['responsible_user_id'] = $data['responsible_user_id'];
        }
        $amo_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] : $data['values']['phone']);
        $amo_data[0]['custom_fields_values'] = [];
        foreach ($this->amoSettings->contacts as $key => $id) {
            if (isset($data['values'][$key]) && !empty($data['values'][$key])) {
                $amo_data[0]['custom_fields_values'][] = [
                    'field_id' => (int)$id,
                    'values' => [
                        [
                            'value' =>  $data['values'][$key]
                        ]
                    ]
                ];
            } else if (isset($data[$key]) && !empty($data[$key])) {
                $amo_data[0]['custom_fields_values'][] = [
                    'field_id' => (int)$id,
                    'values' => [
                        [
                            'value' =>  $data[$key]
                        ]
                    ]
                ];
            }
        }
        return $amo_data;
    }
}
