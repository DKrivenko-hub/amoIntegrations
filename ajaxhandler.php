<?php

include_once __DIR__ . '/functions.php';

$data['phone'] =  formatPhone($data['phone']);

if (strlen($data['phone']) < 7) {
    echo 'short phone';
    return;
}

if (!isset($data['pipeline_name']) || empty($data['pipeline_name'])) {
    $data['pipeline_name'] = 'default';
}


if (!isset($data['source_name']) || empty($data['source_name'])) {
    $data['source_name'] = 'FromSite';
}

if (!isset($data['form_name']) || empty($data['form_name'])) {
    $data['form_name'] = 'Callback';
}

if (!isset($data['remote_addr']) || empty($data['remote_addr'])) {
    $data['remote_addr'] = $_SERVER['REMOTE_ADDR'];
}

$responsible_user_id = 0;
if (isset($data['responsible_user_id']) && !empty($data['responsible_user_id'])) {
    $responsible_user_id = (int)$data['responsible_user_id'];
}

if (!isset($data['values'])) {
    $data['values'] = array(
        'name' => $data['name'] ?? '',
        'phone' => $data['phone'],
        'email' => $data['email'] ?? ''
    );
}

$UTM_Array = array();
if (isset($data['utm'])) {
    $UTM_Array = $data['utm'];
}



if (!empty($data) && isset($data['values'])) {

    $amoSettings = \AmoIntegrations\AmoSettings::getInstance();
    global $domain;
    if (
        $domain !== 'kiev.logicaschool.com' &&
        $domain !== 'glassmtech' &&
        $domain !== 'freshcode.training'
    ) {
        getAccessToken();
    }

    if (!empty($UTM_Array)) {
        foreach ($UTM_Array as $key => $item) {
            if (isset($amoSettings->leads['cfv']['textfields'][$key]) && $item) {
                $data['textfields'][$key] = $item;
            }
        }
    }
    $pipeline = $amoSettings->getPipeline($data['pipeline_name']);

    $amo_data = array();
    $action = '/api/v4/contacts?query=' . $data['values']['phone'];

    $response = curlRequest($action);
    $contact_id = 0;
    $continfo = 0;



    if ($response) {
        foreach ($response['_embedded']['contacts'] as $item) {
            if (isset($item['custom_fields_values'])) {
                foreach ($item['custom_fields_values'] as $cfv) {

                    if ($cfv['field_id'] == $amoSettings->contacts['phone']) {
                        $data['values']['phone'] == formatPhone($data['values']['phone']);
                        foreach ($cfv['values'] as $v) {
                            $v['value'] == formatPhone($v['value']);
                            if (CompareValues($v['value'], $data['values']['phone'])) {
                                $contact_id = $item['id'];
                                $continfo = array($item);
                                break 3;
                            }
                        }
                    }/*  else if ($cfv['field_id'] == $amoSettings->contacts['email_id']) {
                        foreach ($cfv['values'] as $key => $value) {
                            if (CompareValues($v['value'], $data['values']['email'])) {
                                $contact_id = $item['id'];
                                $continfo = array($item);
                                break 3;
                            }
                        }
                    } */
                }
            }
        }
    }

    if (!$contact_id) {
        $amo_data = array();
        if ($responsible_user_id) {
            $amo_data[0]['responsible_user_id'] = $responsible_user_id;
        }
        $amo_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] : $data['values']['phone']);
        $amo_data[0]['custom_fields_values'] = array();
        foreach ($amoSettings->contacts as $key => $id) {
            if (isset($data['values'][$key]) && !empty($data['values'][$key])) {
                $amo_data[0]['custom_fields_values'][] = array(
                    'field_id' => (int)$id,
                    'values' => array(
                        array(
                            'value' =>  $data['values'][$key]
                        )
                    )
                );
            } else if (isset($data[$key]) && !empty($data[$key])) {
                $amo_data[0]['custom_fields_values'][] = array(
                    'field_id' => (int)$id,
                    'values' => array(
                        array(
                            'value' =>  $data[$key]
                        )
                    )
                );
            }
        }

        $response = curlRequest('/api/v4/contacts', $amo_data);

        $contact_id = $response['_embedded']['contacts'][0]['id'];
        $continfo = $response['_embedded']['contacts'];
    }

    $continfo = fixcontinfo($continfo);
    
    $leads_data = array();
    $leads_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] . ' ' . $data['values']['phone'] : $data['values']['phone']);

    $leads_data[0]['pipeline_id'] = (int)$pipeline['pipeline_id'];

    if (isset($data['lead_price'])) {
        // $leads_data['price'] = (int)$data['lead_price'];
    }

    if (count($amoSettings->leads['cfv'])) {
        $tmp = array();

        foreach ($amoSettings->leads['cfv'] as $field_type => $cflds) {

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
        }
        if (!empty($tmp)) {
            $leads_data[0]['custom_fields_values'] = $tmp;
        }
    }
    if (isset($data['tag_name'])) {
        if (is_array($data['tag_name'])) {
            foreach ($data['tag_name'] as $tag) {
                $leads_data[0]['_embedded']['tags'][] = array(
                    'name' => $tag
                );
            }
        } else {
            $leads_data[0]['_embedded'] = array(
                'tags' => array(
                    array(
                        'name' => $data['tag_name']
                    )
                )
            );
        }
    } else {
        $leads_data[0]['_embedded'] = array(
            'tags' => array(
                array(
                    'name' => 'заявка с Тильды'
                )
            )
        );
    }

    if ($responsible_user_id) {
        $leads_data[0]['responsible_user_id'] = $responsible_user_id;
    }

    $action = '/api/v4/leads';
    if ((int)$pipeline['status_id'] < 1) {
        if (!isset($metadata_arr)) {
        }
        $metadata_arr = [
            'ip' => str_replace(' ', '', $data['remote_addr']),
            'form_id' => $data['formid'],
            'form_sent_at' => time(),
            'form_name' => $data['form_name'],
            // 'form_page' => $data['from_page'],
            'form_page' => 'Новая заявка',
            'referer' => $data['textfields']['from_page']
        ];
        $action = '/api/v4/leads/unsorted/forms';
        $amo_data = array(
            array(
                'source_uid' => md5(time()),
                'pipeline_id' => $pipeline['pipeline_id'],
                'source_name' => $data['source_name'],
                '_embedded' => array(
                    'leads' => $leads_data,
                    'contacts' => $continfo
                ),
                'metadata' => $metadata_arr
            )
        );
    } else {
        $amo_data = $leads_data;
        $amo_data[0]['status_id'] = (int)$pipeline['status_id'];
    }
    // var_dump($amo_data);
    $response = curlRequest($action, $amo_data);

    if ($pipeline['status_id']) {
        $lead_id = $response['_embedded']['leads'][0]['id'];
        $action = '/api/v4/leads/' . $lead_id . '/link';

        $amo_data = array();
        $amo_data[0]['to_entity_id'] = $contact_id;
        $amo_data[0]['to_entity_type'] = 'contacts';
        $response = curlRequest($action, $amo_data);
    } else {
        $lead_id = $response['_embedded']['unsorted'][0]['_embedded']['leads'][0]['id'];
    }

    $log = "'\r\n\r\n----------------------------------" . date('d.m.Y H:i:s') . "--------------------------------\r\n\r\n";
    $log .= json_encode($data);
    $log .= "\r\n\r\n\r\n";
    file_put_contents(__DIR__ . '/successSend.log', $log, FILE_APPEND);
    // var_dumP($data);
    if ($lead_id) {

        $note = '';

        foreach ($amoSettings->notes['leads'] as $n_name => $n_keys) {
            foreach ($n_keys as $n_key) {
                if (isset($data[$n_key]) && !empty($data[$n_key])) {
                    $note .= $n_key . ': ' . $data[$n_key] . ' ';
                }
            }
        }
        if ($note) {
            $amo_data = array(
                array(
                    'entity_id' => (int)$lead_id,
                    'note_type' => 'common',
                    "params" => array(
                        'text' => $note,
                    )
                )
            );

            $response = curlRequest('/api/v4/leads/notes ', $amo_data);
        }
    }
}





///////////////////////////////////////////////////////////////////////////////////////////////


    //tasks
    /*
        $lead = curlRequest('/api/v4/leads/' . $update_lead_id);

        $note = 'Новая заявка. Форма - ' . $data['form_name'];

        if (empty($lead)) return;

        $responsible_user_id = (int)$lead['responsible_user_id'];


        $amo_data = array(
            array(
                // 'responsible_user_id'=> $responsible_user_id,
                'entity_id' => $update_lead_id,
                'entity_type' => 'leads',
                'text' => 'Новая заявка с сайта',
                'task_type_id' => 1,
                'complete_till' => strtotime('+1 days')
            )
        );
        $response = curlRequest('/api/v4/tasks', $amo_data);

    */