<?php

namespace AmoIntegrations\Models;

use AmoIntegrations\Collections\Contacts as ContactsCollection;
use AmoIntegrations\Collections\Leads as LeadsCollection;
use AmoIntegrations\Enums\ERequestTypes;

class IncomingLeads extends Model
{

    use  \AmoIntegrations\Helper;

    private array $metadata = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function find()
    {
    }

    public function addUnsortedForm($data, ContactsCollection $contact, $lead)
    {
        $action = '/api/v4/leads/unsorted/forms';

        $this->metadata = [
            "ip" => $this->amoSettings->default_ip,
            "form_id" => "",
            "form_sent_at" => time(),
            "form_name" => "",
            "form_page" => "",
            "referer" => ""
        ];

        $amo_data = [];

        $amo_data = $this->prepareData($data);

        $amo_data['_embedded']['leads'][0] = $lead;
        $amo_data['_embedded']['contacts'][0] = $this->fixNullFields($contact->getData());

        $collection = $this->add($action, [$amo_data]);

        return $collection;
    }

    private function add($action, $data)
    {
        $url = $this->amoSettings->amo_portal . $action;

        $this->connection->setOptions([
            CURLOPT_URL => $url,
        ]);

        $this->setDefaultOptions();

        $this->connection->setData($data, ERequestTypes::POST);

        $response = $this->connection->execute();

        $data = json_decode($response['response'], true);
        var_dump($data['_embedded']['unsorted'][0]['_embedded']['leads'][0]);
        $collection = new LeadsCollection($data['_embedded']['unsorted'][0]['_embedded']['leads'][0]);

        return $collection ?? [];
    }


    public function prepareData($data)
    {

        $pipeline = $this->amoSettings->getPipeline($data['pipeline_name']);

        $amo_data = [
            "source_name" => $data['source_name'],
            "source_uid" => md5(time()),
            "pipeline_id" => (int)$pipeline['pipeline_id'],
            "created_at" => time(),
        ];
        foreach ($this->metadata as $k => $v) {
            if (isset($data[$k]) && !empty($data[$k])) {
                $amo_data['metadata'][$k] = $this->SafeString($data[$k]);
            } else if (!empty($v)) {
                $amo_data['metadata'][$k] = $v;
            }
        }

        return $amo_data;
    }
}
