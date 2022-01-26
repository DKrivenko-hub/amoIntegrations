<?php

namespace AmoIntegrations\Models;

use \AmoIntegrations\Enums\ERequestTypes;

class Contacts extends Model
{
    use \AmoIntegrations\Helper;

    public function __construct()
    {
        parent::__construct();
    }

    public function find(string $value)
    {
        $value = SafeString($value);
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts?query=$value";
        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();

        return $response ?? [];
    }

    public function findById(int $id)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts/$id";

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();

        return $response ?? [];
    }

    public function update(int $id, array $data)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts/$id";

        $amo_data = $this->prepareData($data);

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $this->connection->setData($amo_data, ERequestTypes::PATCH);

        $response = $this->connection->execute();

        return $response ?? [];
    }

    public function add(array $data)
    {
        $url = $this->amoSettings->amo_portal . '/api/v4/contacts';

        $amo_data = $this->prepareData($data);

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $this->connection->setData($amo_data, ERequestTypes::POST);

        $response = $this->connection->execute();

        return $response ?? [];
    }

    private function prepareData(array $data)
    {
        $amo_data = [];
        if (isset($data['responsible_user_id'])) {
            $amo_data[0]['responsible_user_id'] = $data['responsible_user_id'];
        }
        $amo_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] : $data['values']['phone']);
        $amo_data[0]['custom_fields_values'] = [];
        foreach ($this->amoSettings->contacts['cfv'] as $field_type => $cflds) {
            switch ($field_type) {
                case 'enums':
                    $value_key = 'enum_id';
                    break;

                default:
                    $value_key = 'value';
                    break;
            }
            foreach ($cflds as  $key => $f_id) {
                $value = '';
                if (isset($data[$field_type][$key]) &&  !empty($data[$field_type][$key])) {
                    if ($value_key == 'enum_id') {
                        $data[$field_type][$key] = (int)$data[$field_type][$key];
                    }
                    $value =  $data[$field_type][$key];
                } elseif (isset($data[$key]) &&  !empty($data[$key])) {
                    $value = $data[$key];
                }

                if ($value) {
                    $leads_data[0]['custom_fields_values'][] = array(
                        'field_id' => (int)$f_id,
                        'values' => array(
                            array(
                                $value_key => $value
                            )
                        )
                    );
                }
            }
        }
        return $amo_data;
    }
}
