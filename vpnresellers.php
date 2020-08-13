<?php

function vpnresellers_ConfigOptions() {

    $configarray = [
        "access_token" => [
            "Type" => "text",
            "Description" => "Access token",
        ],
    ];

    return $configarray;
}

function curlAction($method, $path, $accessToken, array $data) {

    $response = getApiResult([
        'method' => $method,
        'path' => $path,
        'accessToken' => $accessToken,
        'requestData' => $data
    ]);

    if ($response->statusCode === 200 || $response->statusCode === 201) {
        return 'success';
    }
    elseif ($response->statusCode === 422) {
        if (isset($response->result->errors)) {
            foreach ($response->result->errors as $error) {
                if (isset($error[0])) {
                    return $error[0];
                }
            }
        }
        return ' Validation error.';
    }
    elseif ($response->statusCode === 500) {
        return 'Internal error.';
    }
    else {
        return isset($response->result->message) ? $response->result->message : 'Error.';
    }
}

function getApiResult(array $data) {

    $method = $data['method'];
    $path = $data['path'];
    $accessToken = $data['accessToken'];
    $apiVersion = isset($data['apiVersion']) ? $data['apiVersion'] : 3;
    $requestData = isset($data['requestData']) ? $data['requestData'] : [];
    $baseUrl = "https://api.vpnresellers.com/v$apiVersion/";
    $url = $baseUrl . $path;
    if ($method === 'GET' && count($requestData)) {
        $url .= '?'.http_build_query($requestData);
    }
    $curl = curl_init();

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
    }
    elseif ($method === 'PUT' || $method === 'DELETE' || $method === 'GET') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    }
    else {
        return 'Invalid method.';
    }

    if (count($requestData) && $method !== 'GET') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestData));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

    $result = curl_exec($curl);
    if(! $result) {
        return 'Connection error.';
    }
    $info = curl_getinfo($curl);
    $statusCode = $info['http_code'];
    curl_close($curl);
    $result = json_decode($result);

    return (object) [
        'statusCode' => $statusCode,
        'result' => $result,
    ];
}

function vpnresellers_CreateAccount($params) {

    $accessToken = $params['configoption1'];
    $vpnUserName = $params['customfields']['Username'];
    $password = $params['password'];

    $result = curlAction('POST', 'accounts', $accessToken, [
        'username' => $vpnUserName,
        'password' => $password,
    ]);

    return $result;

}

function vpnresellers_TerminateAccount($params) {

    $accessToken = $params['configoption1'];
    $vpnUserName = $params['customfields']['Username'];

    return curlAction('DELETE', 'whmcs/accounts/delete', $accessToken, [
        'username' => $vpnUserName,
    ]);

}

function vpnresellers_SuspendAccount($params) {

    $accessToken = $params['configoption1'];
    $vpnUserName = $params['customfields']['Username'];

    return curlAction('PUT', 'whmcs/accounts/suspend', $accessToken, [
        'username' => $vpnUserName,
    ]);
}

function vpnresellers_UnsuspendAccount($params) {

    $accessToken = $params['configoption1'];
    $vpnUserName = $params['customfields']['Username'];

    return curlAction('PUT', 'whmcs/accounts/unsuspend', $accessToken, [
        'username' => $vpnUserName,
    ]);
}

function vpnresellers_ChangePassword($params) {

    $accessToken = $params['configoption1'];
    $vpnUserName = $params['customfields']['Username'];
    $password = $params['password'];

    return curlAction('PUT', 'whmcs/accounts/change_password', $accessToken, [
        'username' => $vpnUserName,
        'password' => $password,
    ]);
}

function vpnresellers_ClientArea($params) {

    $accessToken = $params['configoption1'];
    $templateFile = 'clientarea';
    $username = $params['customfields']['Username'];

    return [
        'templatefile' => $templateFile,
        'vars' => [
            'serversList' => getServers($accessToken),
            'portsList' => getPorts($accessToken),
            'configRows' => getConfigRows($accessToken),
            'username' => $username,
        ],
    ];
}

function getConfigRows($accessToken)
{
    $configRows = [];

    if (isset($_GET['server_id']) && isset($_GET['port_id'])) {
        $response = getApiResult([
            'method' => 'GET',
            'path' => 'configuration',
            'accessToken' => $accessToken,
            'requestData' => [
                'server_id' => $_GET['server_id'],
                'port_id' => $_GET['port_id'],
            ]
        ]);

        if ($response->statusCode === 200) {
            $configRows = explode("\n", $response->result->data->file_body);
        }
    }

    return $configRows;
}

function getPorts($accessToken) {

    $response = getApiResult([
        'method' => 'GET',
        'path' => 'ports',
        'accessToken' => $accessToken,
    ]);
    $portsList = [];

    foreach ($response->result->data as $port) {
        $portsList[$port->id] = $port->protocol." - ".$port->number;
    }

    return $portsList;
}

function getServers($accessToken) {

    $response = getApiResult([
        'method' => 'GET',
        'path' => 'servers',
        'accessToken' => $accessToken,
        'apiVersion' => 4,
    ]);
    $serversList = [];

    foreach ($response->result->data as $server) {
        $serversList[] = [
            'id' => $server->id,
            'address' => $server->name,
            'location' => $server->location,
            'flag' => $server->flag,
        ];
    }

    return $serversList;
}
?>
