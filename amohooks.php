<?php

add_filter("wpcf7_feedback_response", function ($response, $result) {

    $data = array();
    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();
    $form = WPCF7_ContactForm::get_current();
    // var_dump($form);
    if ($submission) {
        $title = $form->title;
        $posted_data = $submission->get_posted_data();
        // var_dump($posted_data);
        $from_url = wp_get_raw_referer();
        $page_title = get_the_title((url_to_postid($from_url)));
        $data = array(
            'form_name' => $title,
            'formId' => $form->id(),
            'source_name' => 'FromSite',
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
        );
        $data['textfields']['from_page'] = $from_url;
        $data['textfields']['page_title'] = $page_title;
        if (isset($posted_data['top_selling'])) {
            $data['textfields']['top_selling'] = $posted_data['top_selling'];
        }

        if ($posted_data) {
            foreach ($posted_data as $key => $item) {
                switch ($key) {
                    case 'client-name':
                        $data['name'] = $item;
                        break;
                    case 'client-phone':
                        $data['phone'] = $item;
                        break;
                    case 'messenger':
                        $data['messanger_tag'] = $item[0];
                        break;
                }
            }
        }

        include_once ABSPATH . '/amoIntegrations/utm-lib.php';
        if ($_SESSION['sp_utm_date']) {
            $data['utm'] = $_SESSION;
        } else {
            $data['utm'] = parseUtm($from_url);
        }
        $data['textfields']['gaId'] = ($posted_data['GaClientID'] ?? '');
        if (empty($data['textfields']['gaId'])) {
            if (isset($_COOKIE['_ga'])) {
                try {
                    $tmp = explode('.', $_COOKIE['_ga']);
                    if (count($tmp) == 4) {
                        list($version, $domainDepth, $cid1, $cid2) = $tmp;
                        $data['textfields']['gaId'] = $cid1 . '.' . $cid2;
                    }
                } catch (Exception $e) {
                }
            }
        }
    }
    $response['my_response'] = json_encode($data);
    // var_dump($response['my_response']);
    $res = "\r\n-------------------------------------" . date('d.m.Y H:i:s') . "-------------------------------------------\r\n\r\n";
    $res .= 'data: ' . $response['my_response'];
    file_put_contents(ABSPATH . 'amoIntegrations/forms.log', $res, FILE_APPEND);
    return $response;
}, 10, 2);


add_action('wp_enqueue_scripts', 'amoIntegrationsScript');
function amoIntegrationsScript()
{
    wp_enqueue_script('amoIntegrations-script', '/amoIntegrations/assets/js/amoIntegrations.js', array('jquery', 'contact-form-7'), null, true);
}


add_action('comment_post', 'comment_post_callback', 10, 3);

function comment_post_callback($comment_id, $comment_approved, $comment_data)
{

    $data = array(
        'form_name' => 'Комментарий',
        'formId' => 1000,
        'source_name' => 'FromSite',
        'remote_addr' => $comment_data['comment_as_submitted']['comment_author_IP'],
        'comment' => $comment_data['comment_as_submitted']['comment_content'],

        'email' => $comment_data['comment_as_submitted']['comment_author_email'],
        'name' => $comment_data['comment_as_submitted']['comment_author'],
        'textfields' => array(
            'from_page' => $comment_data['comment_as_submitted']['permalink']
        )
    );

    AmoRequest($data);
}

function AmoRequest($data)
{
    if ($data) {
        include_once ABSPATH . '/amoIntegrations/utm-lib.php';
        if (empty($UTM_Array)) {
            $UTM_Array = $_SESSION;
        }
        $data['utm'] = $UTM_Array;

        $data['textfields']['gaId'] = '';
        if (isset($_COOKIE['_ga'])) {
            try {
                $tmp = explode('.', $_COOKIE['_ga']);
                if (count($tmp) == 4) {
                    list($version, $domainDepth, $cid1, $cid2) = $tmp;
                    $data['textfields']['gaId'] = $cid1 . '.' . $cid2;
                }
            } catch (Exception $e) {
            }
        }


        $data1 = json_encode($data);

        //Disconnecting from a user and running script in the background
        $url = "https://sotalan.com/amoIntegrations/ajaxhandler.php";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data1);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
    }
}

add_filter('cron_schedules', 'cron_add_fifteen_min');
function cron_add_fifteen_min($schedules)
{
    $schedules['fifteen_min'] = array(
        'interval' => 60 * 15,
        'display' => 'Раз в  15минут'
    );
    return $schedules;
}

add_action('wp', 'amo_activation_cron');
function my_activation_cron()
{
    // wp_clear_scheduled_hook( 'amo_refreshToken' );
    if (!wp_next_scheduled('amo_refreshToken')) {
        wp_schedule_event(time(), 'fifteen_min', 'amo_refreshToken');
    }
}

add_action('amo_refreshToken', 'amo_refreshToken_callback');
function amo_refreshToken_callback()
{

    include_once __DIR__ . '/functions.php';
    amo_getAccessToken();
}
