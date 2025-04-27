<?php

define('KVS_STORE_V1_PREFIX', '/store/v1/');

function kvs_store_v1_process_bucket($bucket) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            http_response_code(200);
            header('Content-Type: text/plain');
            echo "Key1: A\n";
            echo "Key2: A\n";
            echo "Key3: A\n";
            foreach (getallheaders() as $name => $value) {
                echo "$name: $value\n";
            }
            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_store_v1_process_keys($bucket) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            http_response_code(200);
            header('Content-Type: text/plain');
            echo "Key1\n";
            echo "Key2\n";
            echo "Key3\n";
            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_store_v1_process_value($bucket, $key) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            http_response_code(200);
            header('Content-Type: text/plain');
            echo "A\n";
            break;
        case 'PUT':
        case 'POST':
            http_response_code(201);
            break;
        case 'DELETE':
            http_response_code(204);
            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_store_v1_process() {

    $path = $_SERVER['PATH_INFO'];
    if (preg_match('/^\/store\/v1\/bucket\/([^\/]+)$/', $path, $matches)) {
        $bucket = $matches[1];
        kvs_store_v1_process_bucket($bucket);
    }
    else if (preg_match('/^\/store\/v1\/bucket\/([^\/]+)\/keys$/', $path, $match)) {
        $bucket = $matches[1];
        kvs_store_v1_process_keys($bucket);
    }
    else if (preg_match('/^\/store\/v1\/bucket\/([^\/]+)\/value\/([a-zA-Z0-9-_\.]+)$/')) {
        $bucket = $match[1];
        $key = $match[2];
        kvs_store_v1_process_value($bucket, $key);
    }
    else {
        http_response_code(404);
        header("Content-Type: text/plain");
        echo "Connected successfully\n";
        echo $_SERVER['PATH_INFO'] . "\n";      
    }
}

?>