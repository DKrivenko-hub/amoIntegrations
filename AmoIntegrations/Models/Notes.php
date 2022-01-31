<?php

namespace AmoIntegrations\Models;

use AmoIntegrations\Enums\{EEntityTypes, ERequestTypes};
use AmoIntegrations\Interfaces\IEntity;

class Notes extends Model
{

    private array $params = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function find()
    {
    }

    public function AddCommonNote($data, IEntity $entity)
    {
        $amo_data = [
            'entity_id' => $entity->getId(),
            'note_type' => 'common',
            'params' => []
        ];

        $note = '';
        if (count($this->amoSettings->notes[$entity->getType()])) {
            foreach ($this->amoSettings->notes[$entity->getType()] as $field_title => $n_keys) {

                foreach ($n_keys as $n_key) {
                    if (isset($data[$n_key]) && !empty($data[$n_key])) {
                        $note .= $n_key . ': ' . $data[$n_key] . '; ';
                    }
                }
            }
        }

        if ($note) {
            $amo_data['params']['text'] = $note;
        }
         
        $collection = $this->add('/api/v4/' . $entity->getType() . '/notes', [$amo_data]);

        return $collection ?? [];
    }

    private function add($action, $amo_data)
    {
        $url = $this->amoSettings->amo_portal . $action;
        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $this->connection->setData($amo_data, ERequestTypes::POST);

        $response = $this->connection->execute();

        return $response;
    }
}
