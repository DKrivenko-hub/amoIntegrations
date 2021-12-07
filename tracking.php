<?php

include_once ABSPATH . 'amoIntegrations/functions.php';
include_once ABSPATH . 'amoIntegrations/utm-lib.php';

add_action('wp_loaded', function () {
    $url = filterUrl($_SERVER['REQUEST_URI']);

    if (!$url) {
        return;
    }
    $user = wp_get_current_user();
    if (
        user_can($user->ID, 'manage_options') ||
        $_SERVER['REQUEST_METHOD'] == "POST" ||
        is_feed() || wp_doing_ajax()
    ) {
        return;
    }

    amo_startSession();

    $cookie_id = 0;
    if (isset($_SESSION['crm_cookie_id']) && $_SESSION['crm_cookie_id']) {
        $cookie_id = $_SESSION['crm_cookie_id'];
    }

    global $wpdb;
    if (!$cookie_id) {

        $fb_id = $_COOKIE['_fbp'] ?? '-';
        $ga_id = $_COOKIE['_ga'] ?? '-';

        $check = $wpdb->get_row("SELECT * FROM amo_user_cookies WHERE fb_id ='$fb_id' OR ga_id = '$ga_id' ORDER BY created_at DESC LIMIT 1");

        if ($check) {
            $_SESSION['crm_cookie_id'] = $check->id;
            $cookie_id = $check->id;
        }
    }

    if ($cookie_id) {
        include_once ABSPATH . 'amoIntegrations/utm-lib.php';

        if (empty($UTM_Array)) {
            $UTM_Array = $_SESSION;
        }

        $client_id = $wpdb->get_var('SELECT crm_client_id FROM amo_user_cookies WHERE id = "' . $cookie_id . '"');
        $headers = getallheaders();
        if ($client_id) {
            $insert = array(
                'cookie_id' => $cookie_id,
                'crm_client_id' => $client_id,
                'data' => json_encode(array(
                    'utm' => $UTM_Array,
                    'url' => "https://smebanking.news" . $_SERVER['REQUEST_URI'],
                    'title' => get_the_title(),
                )),
                'page_url' => mb_substr($_SERVER['REQUEST_URI'], 0, 255),
                'headers' => json_encode($headers),
            );

            $wpdb->insert(
                'amo_site_visit_tracking',
                $insert
            );
        }
    }
}, 50);

add_action('wp_login', function ($user_login, $user) {

    if (!$user) {
        return;
    }
    if (
        user_can($user->ID, 'manage_options') ||
        is_feed()
    ) {
        return;
    }

    amo_startSession();

    $cookie_id = 0;
    $client_id = 0;

    if (isset($_SESSION['crm_cookie_id']) && (int)$_SESSION['crm_cookie_id'] > 0) {
        $cookie_id = $_SESSION['crm_cookie_id'];
    }
    $user_id = $user->ID;

    global $wpdb;
    include_once ABSPATH . 'amoIntegrations/functions.php';
    $amoSettings = \AmoIntegrations\AmoSettings::getInstance();

    if (!$cookie_id) {
        $old_client = $wpdb->get_var("SELECT `crm_client_id` FROM `amo_crm_to_cms_users` WHERE `user_id` = '$user_id' AND tracking=1 AND deleteInAmo=0");
        if ($old_client) {
            $check = amo_getClientByID($old_client);
            if (!$check) {
                $new_client = amo_getClientInfoByEmail($user->user_email);
                if (isset($new_client['id'])) {
                    $wpdb->update(
                        'amo_crm_to_cms_users',
                        array('crm_client_id' => $new_client['id']),
                        array('user_id' => $user_id)
                    );
                    $wpdb->update(
                        'amo_user_cookies',
                        array('crm_client_id' => $new_client['id']),
                        array('crm_client_id' => $old_client)
                    );
                    $wpdb->update(
                        'amo_site_visit_tracking',
                        array('crm_client_id' => $new_client['id']),
                        array('crm_client_id' => $old_client)
                    );
                    $client_id = $new_client['id'];
                } else {
                    $wpdb->update(
                        'amo_crm_to_cms_users',
                        array('deleteInAmo' => 1),
                        array('user_id' => $user_id)
                    );
                }
            } else {
                $client_id = $old_client;
            }
        } else {
            $check_client = amo_getClientInfoByEmail($user->user_email);

            if(empty($check_client)){
                $amo_data = array();

                if (!empty($user->display_name)) {
                    $amo_data[0]['name'] = $user->display_name;
                } else if ($user->user_nicename) {
                    $amo_data[0]['name'] = $user->user_nicename;
                } else if ($user->user_login) {
                    $amo_data[0]['name'] = $user->user_login;
                } else {
                    $amo_data[0]['name'] = $user->user_email;
                }

                $amo_data[0]['custom_fields_values'] = array(
                    array(
                        'field_id' => (int)$amoSettings->contacts['email_id'],
                        'values' => array(
                            array(
                                'value' =>  $user->user_email
                            )
                        )
                    )
                );

                $response = amo_curlRequest('/api/v4/contacts', $amo_data);
                $client_id = $response['_embedded']['contacts'][0]['id'];

                if(!$client_id){
                    return;
                }
            }
            else{
                if(isset($check_client['id'])){
                    $client_id = $check_client['id'];
                }
                else{
                    return;
                }
            }

            $wpdb->insert(
                'amo_crm_to_cms_users',
                array(
                    'crm_client_id' => $client_id,
                    'user_id' => $user_id,
                    'is_active' => 1
                )
            );
        }
    }

    if ($client_id) {

        $wpdb->insert(
            'amo_user_cookies',
            array(
                'crm_client_id' => $client_id,
                'ga_id' => $_COOKIE['_ga'] ?? '',
                'fb_id' => $_COOKIE['_fbp'] ?? '',
            )
        );
        $cookie_id = $wpdb->insert_id;
        $_SESSION['crm_cookie_id'] = $cookie_id;
    }

    include_once ABSPATH . 'amoIntegrations/utm-lib.php';

    if (empty($UTM_Array)) {
        $UTM_Array = $_SESSION;
    }
    $headers = getallheaders();
    $insert = array(
        'cookie_id' => $cookie_id,
        'crm_client_id' => $client_id,
        'data' => json_encode(array(
            'utm' => $UTM_Array,
            'url' => "https://smebanking.news" . $_SERVER['REQUEST_URI'],
            'title' => get_the_title(),
        )),
        'headers' => json_encode($headers),
        'page_url' => $url,
    );

    $wpdb->insert(
        'amo_site_visit_tracking',
        $insert
    );
}, 100, 2);


// add_action('user_register', 'user_register_callback', 100, 1);
function user_register_callback(int $user_id)
{
    $user = get_userdata($user_id);

    if (user_can($user_id, 'manage_options')  || wp_doing_ajax()) {
        return;
    }

    include_once ABSPATH . 'amoIntegrations/functions.php';
    $amoSettings = \AmoIntegrations\AmoSettings::getInstance();

    global $wpdb;
    $client = amo_getClientInfoByEmail($user->user_email);
    if ($client) {
        $client_id = $client['id'];
    } else {

        $amo_data = array();

        if (!empty($user->display_name)) {
            $amo_data[0]['name'] = $user->display_name;
        } else if ($user->user_nicename) {
            $amo_data[0]['name'] = $user->user_nicename;
        } else if ($user->user_login) {
            $amo_data[0]['name'] = $user->user_login;
        } else {
            $amo_data[0]['name'] = $user->user_email;
        }

        $amo_data[0]['custom_fields_values'] = array(
            array(
                'field_id' => (int)$amoSettings->contacts['email_id'],
                'values' => array(
                    array(
                        'value' =>  $user->user_email
                    )
                )
            )
        );

        $response = amo_curlRequest('/api/v4/contacts', $amo_data);
        $client_id = $response['_embedded']['contacts'][0]['id'];
        // $amo_client = $response['_embedded']['contacts'];
    }

    if(!$client_id){
        return;
    }
    $wpdb->insert(
        'amo_crm_to_cms_users',
        array(
            'crm_client_id' => $client_id,
            'user_id' => $user_id,
            'is_active' => 1
        )
    );

    $wpdb->insert(
        'amo_user_cookies',
        array(
            'crm_client_id' => $client_id,
            'ga_id' => $_COOKIE['_ga'] ?? '',
            'fb_id' => $_COOKIE['_fbp'] ?? '',
        )
    );
    $cookie_id = $wpds->insert_id;
    if ($cookie_id) {

        amo_startSession();

        $_SESSION['crm_cookie_id'] = $cookie_id;

        include_once ABSPATH . 'amoIntegrations/utm-lib.php';

        if (empty($UTM_Array)) {
            $UTM_Array = $_SESSION;
        }
        $headers = getallheaders();
        $insert = array(
            'cookie_id' => $cookie_id,
            'crm_client_id' => $client_id,
            'data' => json_encode(array(
                'utm' => $UTM_Array,
                'url' => "https://smebanking.news" . $url,
                'title' => get_the_title(),
            )),
            'page_url' => $url,
            'headers' => json_encode($headers),
        );

        $wpdb->insert(
            'amo_site_visit_tracking',
            $insert
        );
    }
}

function filterUrl($url)
{
    $patterns = array(
        '/wp-admin(.*)/m',
        '/wp-login(.*)/m',
        '/wp-ajax(.*)/m',
        '/wp-activate(.*)/m',
        '/wp-json(.*)/m',
        '/feed(.*)/m',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            $url = '';
        }
    }
    return $url;
}

function amo_remove_user($user_id)
{
    global $wpdb;

    $client_id = $wpdb->get_var("SELECT crm_client_id FROM `amo_crm_to_cms_users` WHERE user_id = '$user_id' ");

    if ($client_id) {
        $wpdb->delete(
            'amo_site_visit_tracking',
            array('crm_client_id' => $client_id),
		);
        $wpdb->delete(
            'amo_user_cookies',
            array('crm_client_id' => $client_id),
		);
        $wpdb->delete(
            'amo_crm_to_cms_users',
            array('crm_client_id' => $client_id),
		);
    }
}
// add_action( 'delete_user', 'custom_remove_user', 100);