<?php
// http_response_code(500);

include 'DB_mySQLi.php';
include 'functions.php';

ini_set("log_errors", 1);
ini_set("error_log", "errors.log");

define("DB_HOST", '');
define("DB_NAME", '');
define("DB_USER", '');
define("DB_PASSWORD", '');

$db = new DB_mySQLi(array(
    'db_host' => DB_HOST,
    'db_name' => DB_NAME,
    'db_user' => DB_USER,
    'db_pass' => DB_PASSWORD
));

if (!$db) {
    exit;
}

use AmoIntegrations\AmoSettings;

amo_getAccessToken();

$amoSettings = AmoSettings::getInstance();

if (isset($_GET['fix_client_ids'])) {
    amo_fixClientIds();
}

// $clients = $db->getAllResults("SELECT c2c.crm_client_id, u.*  FROM amo_crm_to_cms_users as c2c JOIN wp_users as u ON c2c.user_id = u.ID WHERE c2c.tracking=1 AND c2c.deleteInAmo=0");

$clients = $db->getAllResults(
    "SELECT     
        (SELECT user_id FROM amo_crm_to_cms_users WHERE crm_client_id = svt.crm_client_id ) as user_id,
         svt.crm_client_id
    FROM amo_site_visit_tracking as svt 
    WHERE svt.is_sync=0
    GROUP BY crm_client_id"
);

if ($clients) {
    foreach ($clients as $client) {
        $user = $db->getAllResults("SELECT * FROM wp_users WHERE ID=$client[user_id]")[0];

        $client_id = $client['crm_client_id'];

        $lastVisit = $db->myQuery("SELECT MAX(svt.created_at) as lastVisit FROM amo_site_visit_tracking as svt LEFT JOIN amo_user_cookies as uc on uc.id=svt.cookie_id WHERE uc.crm_client_id =$client_id LIMIT 1");

        if ($lastVisit->num_rows < 1) {
            continue;
        }

        $lastVisit = $lastVisit->Fetch_Assoc();

        if (strtotime($lastVisit['lastVisit']) > strtotime('-20 minutes')) {
            continue;
        }

        $note = '';
        $check = amo_getClientInfoByEmail($user['user_email']);

        if (!$check ) {
            continue;
            $amo_data = array();

            if (!empty($client['display_name'])) {
                $amo_data[0]['name'] = $user['display_name'];
            } else if ($client['user_nicename']) {
                $amo_data[0]['name'] = $user['user_nicename'];
            } else if ($client['user_login']) {
                $amo_data[0]['name'] = $user['user_login'];
            } else {
                $amo_data[0]['name'] = $user['user_email'];
            }

            $amo_data[0]['custom_fields_values'] = array(
                array(
                    'field_id' => (int)$amoSettings->contacts['email_id'],
                    'values' => array(
                        array(
                            'value' =>  $client['user_email']
                        )
                    )
                )
            );

            $response = amo_curlRequest('/api/v4/contacts', $amo_data);
            $client_id = $response['_embedded']['contacts'][0]['id'];
        }

        $visitings = $db->getAllResults(
            "SELECT svt.*, uc.fb_id, uc.ga_id, COUNT(svt.id) as views
			FROM amo_site_visit_tracking as svt LEFT JOIN amo_user_cookies as uc on uc.id=svt.cookie_id 
			WHERE  uc.crm_client_id ='$client_id' AND svt.is_sync = 0 GROUP BY svt.`page_url`"
        );


        if ($visitings) {
            $maxMin = $db->getAllResults(
                "SELECT MIN(svt.created_at) as `min`, MAX(svt.created_at) as `max`
					FROM amo_site_visit_tracking as svt JOIN amo_user_cookies as uc on uc.id=svt.cookie_id 
					WHERE svt.is_sync=0 AND uc.crm_client_id='$client_id' "
            );

            if ($maxMin) {

                $humanTime = amo_secondsToTime(strtotime($maxMin[0]['max']) - strtotime($maxMin[0]['min']));

                $tmp = '';
                if ((int)$humanTime['h'] > 0) {
                    $tmp .= $humanTime['h'] . ' ч. ';
                }
                $tmp .= $humanTime['m'] . ' мин.';
                $note .= 'Клиент заходил на сайт ' . date('d.m.Y') . '. Провёл ' . $tmp . "\r\nПосещал:\r\n";

            }

            foreach ($visitings as $visit) {

                $visit_data = json_decode($visit['data'], true);
                $tmp = $note . $visit_data['url'] . "  - $visit[views] раз(а)\r\n";
                if (mb_strlen($tmp) > 20000) {
                    amo_addNote($client_id, $note);
                    $note = '';
                } else {
                    $note = $tmp;
                }

            }

            $db->myQuery("UPDATE amo_site_visit_tracking SET is_sync=1 WHERE crm_client_id=$client_id");

        }
        if ($note) {
            amo_addNote($client_id, $note);
        }
    }
    $db->myQuery(
        'DELETE FROM amo_site_visit_tracking  
        WHERE
            (is_sync = 1 AND created_at < "' . date('Y-m-d H:i:s', strtotime("-1 days")) . '") OR 
            ( created_at < "' . date('Y-m-d H:i:s', strtotime("-5 days")) . '")'
    );
}

http_response_code(200);




function amo_addNote($client_id, $note)
{
    $amo_data = array(
        array(
            'entity_id' => (int)$client_id,
            'note_type' => 'common',
            "params" => array(
                'text' => $note,
            )
        )
    );
    // echo json_encode($amo_data);
    amo_curlRequest('/api/v4/contacts/notes ', $amo_data);
}

function amo_fixClientIds(array $client = array())
{
    // $amoSettings = \AmoIntegrations\AmoSettings::getInstance();

    global $db;
    if ($client) {
        $clients = array($client);
    } else {
        $clients = $db->getAllResults('SELECT u.*, c2c.*  FROM   amo_crm_to_cms_users as c2c JOIN wp_users  as u ON u.ID = c2c.user_id WHERE deleteInAmo = 0');
    }
    $counter = 0;

    if ($clients) {
        foreach ($clients as $client) {
            $counter++;
            if ($counter == 5) {
                sleep(1);
            }
            $check_client = amo_getClientInfoByEmail($client['user_email']);

            if ($check_client) {
                if ($check_client['id'] != $client['crm_client_id']) {
                    $db->myQuery(
                        "UPDATE amo_crm_to_cms_users 
                        SET `crm_client_id`  = '$check_client[id]'
                       WHERE crm_client_id ='$client[crm_client_id]'"
                    );

                    $db->myQuery(
                        "UPDATE amo_user_cookies 
                        SET `crm_client_id`='$check_client[id]' 
                        WHERE crm_client_id = $client[crm_client_id]"
                    );

                    $db->myQuery(
                        "UPDATE amo_site_visit_tracking 
                        SET `crm_client_id`='$check_client[id]'
                        WHERE crm_client_id = $client[crm_client_id]"
                    );
                }
            } else {
                $db->myQuery(
                    "UPDATE amo_crm_to_cms_users
                     SET deleteInAmo = 1 
                     WHERE crm_client_id = ' $client[crm_client_id]'"
                );
            }
        }
    }
    exit;
}
