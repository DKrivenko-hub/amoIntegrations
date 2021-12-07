<?php


// $data = json_decode(file_get_contents("php://input"), true);
$data = json_decode($_POST['form'], true);
if(empty($data)){
    $data = file_get_contents('php://input');
    parse_str(file_get_contents('php://input', $data));
    $data = json_decode($data, true);
}
file_put_contents(__DIR__.'/debug1.log', json_encode($data));

// var_dump(($data));
// exit;
include __DIR__ . '/functions.php';
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
if (!isset($data['textfields']['from_page']) || empty($data['textfields']['from_page'])) {
    $data['textfields']['from_page'] = 'https://prostohouse.com/';
}

if (!isset($data['form_name']) || empty($data['form_name'])) {
    $data['form_name'] = 'Callback';
}

if (!isset($data['values'])) {
    $data['values'] = array(
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'] ?? ''
    );
}

// include_once __DIR__ . '/utm-lib.php';
if (empty($data['utm'])) {
    $UTM_Array = $_SESSION;
}

$UTM_Array = $data['utm'];


if (!empty($data) && isset($data['values'])) {

    $amoSettings = new \AmoIntegrations\AmoSettings();

    if (!empty($UTM_Array)) {
        foreach ($amoSettings->leads['cfv']['textfields'] as $k => $v) {
            if (isset($UTM_Array[$k])) {
                if (!empty($UTM_Array[$k])) {
                    $data['textfields'][$k] = $UTM_Array[$k];
                }
            }
        }
    }

    $pipeline = $amoSettings->getPipeline($data['pipeline_name']);

    $amo_data = array();
    $company_id = 0;
    $contact_id = 0;
    $continfo = 0;

    if (isset($data['company_name'])) {
        $data['company_name'] = SafeString($data['company_name']);
        $action = '/api/v4/companies?query=' . $data['company_name'];

        $response = curlRequest($action);
        if ($response) {
            foreach ($response['_embedded']['companies'] as $item) {
                if ($item['name'] === $data['company_name']) {
                    $company_id = $item['id'];
                    break;
                }
            }
        }

        if (!$company_id) {
            $action = '/api/v4/companies';
            $amo_data = array(
                array(
                    'name' => $data['company_name'],
                )
            );
            $response = curlRequest($action, $amo_data);
            if ($response) {
                $company_id = $response['_embedded']['companies'][0]['id'];
            }
        }
    }

    $action = '/api/v4/contacts?query=' . $data['values']['phone'];

    $response = curlRequest($action);

    if ($response) {
        foreach ($response['_embedded']['contacts'] as $item) {
            if (isset($item['custom_fields_values'])) {
                foreach ($item['custom_fields_values'] as $cfv) {
                    if ($cfv['field_id'] == $amoSettings->contacts['phone_id']) {
                        foreach ($cfv['values'] as $v) {
                            if (ComparePhones($v['value'], $data['values']['phone'])) {
                                $contact_id = $item['id'];
                                $continfo = array($item);
                                break 3;
                            }
                        }
                    } else if ($cfv['field_id'] == $amoSettings->contacts['email_id']) {
                        foreach ($cfv['values'] as $v) {
                            if ($v == $data['values']['email']) {
                                $contact_id = $item['id'];
                                $continfo = array($item);
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    }

    if (!$contact_id) {
        $amo_data = array();
        $amo_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] : $data['values']['phone']);
        $amo_data[0]['custom_fields_values'] = array(
            array(
                'field_id' => (int)$amoSettings->contacts['phone_id'],
                'values' => array(
                    array(
                        'value' =>  $data['values']['phone']
                    )
                )
            )
        );
        if ($company_id) {
            $amo_data[0]['_embedded']['companies'][0]['id'] = $company_id;
        }

        $response = curlRequest('/api/v4/contacts', $amo_data);

        $contact_id = $response['_embedded']['contacts'][0]['id'];
        $continfo = $response['_embedded']['contacts'];
    } else {
        if ($company_id && !isset($continfo[0]['_embedded']['companies'][0]['id'])) {

            $action = '/api/v4/companies/' . $company_id . '/link';
            $amo_data = array(
                array(
                    'to_entity_id' => $contact_id,
                    'to_entity_type' => 'contacts'
                ),
            );
            $response = curlRequest($action, $amo_data);
        }
    }



    //lead
    $leads_data = array();
    $leads_data[0]['name'] = ($data['values']['name'] ? $data['values']['name'] . ' ' . $data['values']['phone'] : $data['values']['phone']);

    $leads_data[0]['pipeline_id'] = (int)$pipeline['pipeline_id'];

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

            foreach ($cflds as $f_name => $f_id) {
                if (isset($data[$field_type][$f_name]) &&  !empty($data[$field_type][$f_name])) {
                    if ($value_key == 'enum_id') {
                        $data[$field_type][$f_name] = (int)$data[$field_type][$f_name];
                    }
                    $leads_data[0]['custom_fields_values'][] = array(
                        'field_id' => (int)$f_id,
                        'values' => array(
                            array(
                                $value_key => $data[$field_type][$f_name]
                            )
                        )
                    );
                }
            }
        }
        if (!empty($tmp)) {
            $leads_data[0]['custom_fields_values'] = $tmp;
        }
    }




    //check tags

    $leads_data[0]['_embedded'] = array(
        'tags' => array(
            array(
                'name' => 'Завяка с сайта'
            )
        )
    );


    $action = '/api/v4/leads';
    if ((int)$pipeline['status_id'] < 1) {
        $action = '/api/v4/leads/unsorted/forms';
        $amo_data = array(
            array(
                'source_uid' => md5(time()),
                'source_name' => $data['source_name'],
                '_embedded' => array(
                    'leads' => $leads_data,
                    'contacts' => $continfo
                ),
                'metadata' => array(
                    'ip' => str_replace(' ', '', $data['remote_addr']),
                    'form_id' => $data['formId'],
                    'form_sent_at' => time(),
                    'form_name' => $data['form_name'],
                    // 'form_page' => $data['from_page'],
                    'form_page' => 'Новая заявка',
                    'referer' => $data['textfields']['from_page']
                )
            )
        );
    } else {
        $amo_data[0]['status_id'] = $pipeline['status_id'];
    }
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


    if ($lead_id) {

        $note = '';

        foreach ($amoSettings->leads['notes'] as $id => $title) {
            if (isset($data[$id]) && !empty($data[$id])) {
                $note .= $title . ': ' . $data[$id] . '; ';
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

    $log = "'\r\n\r\n----------------------------------" . date('d.m.Y H:i:s') . "--------------------------------\r\n\r\n";
    $log .= json_encode($data);
    $log .= "\r\n\r\n\r\n";
    file_put_contents(__DIR__ . '/successSend.log', $log, FILE_APPEND);
}
