<?php

// require __DIR__ . 'autoload.php';
require 'autoload.php';

//Reading data from spreadsheet.
$client = new \Google_Client();
$client->setApplicationName('Google Sheets and PHP');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/json/credentials.json');
$service = new Google_Service_Sheets($client);


$spreadsheetId = "129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U"; //It is present in your URL
$get_range = "measurementTypes!A1:B9";
/*
Note:  Sheet name is found in the bottom of your sheet and range can be an example
 "A2: B10" or “A2: C50" or “B1: B10" etc.
*/

//Request to get data from spreadsheet.
$response = $service->spreadsheets_values->get($spreadsheetId, $get_range);
$values = $response->getValues();
print_r($values);

/*
https://console.cloud.google.com/iam-admin/analyzer/query;expand=groups,resources;permissions=actions.agent.claimContentProvider,actions.agent.get,actions.agent.update,actions.agentVersions.create,actions.agentVersions.delete,actions.agentVersions.deploy,actions.agentVersions.get,actions.agentVersions.list,aiplatform.artifacts.create,aiplatform.artifacts.delete;scopeResource=positive-guild-374817;scopeResourceType=0?project=positive-guild-374817
*/

?>