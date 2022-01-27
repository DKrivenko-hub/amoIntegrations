<?php

// var_dump($_SERVER);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

use \AmoIntegrations\Models\Contacts;
use AmoIntegrations\Models\Leads;

$MContacts = new Contacts();

// $contacts = $MContacts->find('3801111111');
// $contacts = $MContacts->findById(597643);
// $contacts = $MContacts->add([

//     'name' => 'Test3 Testovich',
//     'phone' => '3802222333'

// ]);
$MLeads = new Leads();
$leads = $MLeads->find();
// $leads = $MLeads->update(578809, [
//     'lead_price'=>1111,
//     'name'=>'MyFirstLead',
//     'pipeline_name'=>'default',
//     'phone' => '3802222333'
// ]);

// $leads = $MLeads->add([
//     'lead_price'=>1111,
//     'name'=>'MySecondLead',
//     'pipeline_name'=>'default',
//     'phone' => '3802222333'
// ]);
echo '<pre>';
var_dump(json_decode($leads['response'], true));

