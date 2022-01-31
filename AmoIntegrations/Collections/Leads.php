<?php

namespace AmoIntegrations\Collections;

use AmoIntegrations\Interfaces\IEntity;
use AmoIntegrations\Enums\EEntityTypes;
use AmoIntegrations\{Helper, AmoSettings};

class Leads implements IEntity
{

    use Helper;

    private AmoSettings $amoSettings;

    private $data = [];

    private int $id;

    public function __construct($data)
    {
        $this->id = (int)$data['id'];
        $this->data = $data;
        $this->amoSettings = AmoSettings::getInstance();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return EEntityTypes::Leads->value;
    }

    public function find($key, $value)
    {
        if (
            !isset($this->amoSettings->contacts['cfv']['textfields'][$key]) ||
            !isset($this->data['custom_fields_values'])
        ) {
            return false;
        }

        if ($key == 'phone') {
            $value = $this->formatPhone($value);
        }

        foreach ($this->data['custom_fields_values'] as $cfv) {
            if ($cfv['field_id'] == $this->amoSettings->leads['cfv']['textfields'][$key]) {
                foreach ($cfv['values'] as $v) {
                    if ($key == 'phone') {
                        $v['value'] == $this->formatPhone($v['value']);
                    }
                    if ($this->CompareValues($v['value'], $value)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getData()
    {
        return $this->data;
    }
}
