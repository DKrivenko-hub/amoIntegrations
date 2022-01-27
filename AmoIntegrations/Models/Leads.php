<?php

namespace AmoIntegrations\Models;

use \AmoIntegrations\Enums\ERequestTypes;

class Leads extends Model
{
    use  \AmoIntegrations\Helper;

    public function __construct()
    {
        parent::__construct();
    }

    public function find(string $value = null)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/leads";
        if ($value) {
            $value = SafeString($value);
            $url .= "?query=$value";
        }
        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();

        return $response ?? [];
    }

    public function findById(int $id)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/leads/$id";

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $response = $this->connection->execute();

        return $response ?? [];
    }

    public function update(int $id, array $data)
    {
        $url = $this->amoSettings->amo_portal . "/api/v4/leads/$id";

        $amo_data = $this->prepareData($data);
        var_dump($amo_data);
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
        $url = $this->amoSettings->amo_portal . '/api/v4/leads';

        $amo_data[0] = $this->prepareData($data);

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
        $pipeline = $this->amoSettings->getPipeline($data['pipeline_name']);

        $amo_data = array();
        $amo_data['name']  = ($data['name'] ? $data['name'] . ' ' . $data['phone'] : $data['phone']);     
        
        $amo_data['pipeline_id'] = (int)$pipeline['pipeline_id'];

        if (isset($data['responsible_user_id'])) {
            $amo_data['responsible_user_id'] = $data['responsible_user_id'];
        }

        if (isset($data['lead_price'])) {
            $amo_data['price'] = (int)$data['lead_price'];
        }

        if (count($this->amoSettings->leads['cfv'])) {

            foreach ($this->amoSettings->leads['cfv'] as $field_type => $cflds) {

                switch ($field_type) {
                    case 'enums':
                        $value_key = 'enum_id';
                        break;

                    default:
                        $value_key = 'value';
                        break;
                }

                foreach ($cflds as $f_id => $keys) {
                    foreach ($keys as $key) {
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
            }
        }

        if (isset($data['tag_name'])) {
            if (is_array($data['tag_name'])) {
                foreach ($data['tag_name'] as $tag) {
                    $amo_data[0]['_embedded']['tags'][] = array(
                        'name' => $tag
                    );
                }
            } else {
                $amo_data['_embedded'] = array(
                    'tags' => array(
                        array(
                            'name' => $data['tag_name']
                        )
                    )
                );
            }
        } else {
            $amo_data['_embedded'] = array(
                'tags' => array(
                    array(
                        'name' => 'заявка с сайта'
                    )
                )
            );
        }
        return $amo_data;
    }
}
