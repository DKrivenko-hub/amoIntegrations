<?php

namespace AmoIntegrations\Models;

use AmoIntegrations\Helper;
use AmoIntegrations\Collections\{Contacts as CollectionsContacts, AmoCollection};
use \AmoIntegrations\Enums\ERequestTypes;

class Contacts extends Model
{
    use Helper;

    public function __construct()
    {
        parent::__construct();
    }

    public function find(string $value = '')
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts";
        if ($value) {
            $value = $this->SafeString($value);
            $url .= "?query=$value";
        }
        $this->connection->SetOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();
        $collection = new AmoCollection('Contacts', $response['response']);
        return $collection ?? [];
    }

    public function findById(int $id)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/contacts/$id";

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();

        $collection = new CollectionsContacts(json_decode($response['response'], true));
        return $collection ?? [];
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

        $collection = new CollectionsContacts(json_decode($response['response'], true));

        return $collection ?? [];
    }

    public function add(array $data)
    {
        $url = $this->amoSettings->amo_portal . '/api/v4/contacts';

        $amo_data[0] = $this->prepareData($data);

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $this->connection->setData($amo_data, ERequestTypes::POST);

        $response = $this->connection->execute();

        $collection = new CollectionsContacts(json_decode($response['response'], true));

        return $collection ?? [];
    }

    private function prepareData(array $data)
    {
        $amo_data = [];
        if (isset($data['responsible_user_id'])) {
            $amo_data['responsible_user_id'] = $data['responsible_user_id'];
        }
        $amo_data['name'] = ($data['name'] ? $data['name'] : $data['phone']);
        $amo_data['custom_fields_values'] = [];
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
                    $amo_data['custom_fields_values'][] = array(
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
