<?php

$configPath = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))).'/config/config.inc.php';

if (file_exists($configPath)) {
    require_once($configPath);
} else {
    exit;
}

function response_error($msg) {
    echo json_encode(array('status'=> 'error', 'message'=> $msg));
}

function response($data) {
    $response = array('status'=> 'success', 'data'=> $data);
    $json = json_encode($response);
    if (json_last_error() != JSON_ERROR_NONE) {
        response_error('Could not encode response json.');
    } else {
        echo $json;
    }
}

$module = Module::getInstanceByName('fyndiqmerchant');

$username = Configuration::get($module->config_name.'_username');
$api_token = Configuration::get($module->config_name.'_api_token');

try {
    $result = FyndiqAPI::call($module->user_agent, $username, $api_token, array());
    if ($result[0] == 200) {
        response($result[1]);
    } else {
        response_error('Unexpected error: API return code not 200');
    }
} catch (Exception $e) {
    response_error('Unexpected error when calling API: '.get_class($e).': '.$e->getMessage());
}