<?php

define('KVS_STORE_V1_PREFIX', '/store/v1/');

function kvs_store_v1_process_bucket($bucket_name) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $conn = kvs_db_open();
            $bucket = kvs_bucket_by_name($conn, $bucket_name);
            if (!$bucket) {
                http_response_code(404);
                return;
            }
            $entries = kvs_entry_list($conn, $bucket->id);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($entries, JSON_FORCE_OBJECT);
            break;
        case 'DELETE':
            $conn = kvs_db_open();
            $bucket = kvs_bucket_by_name($conn, $bucket_name);
            if (!$bucket) {
                http_response_code(404);
                return;
            }
            kvs_entry_remove_all($conn, $bucket->id);
            http_response_code(200);
            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_store_v1_process_keys($bucket_name) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $conn = kvs_db_open();
            $bucket = kvs_bucket_by_name($conn, $bucket_name);
            if (!$bucket) {
                http_response_code(404);
                return;
            }
            $names = kvs_entry_list_keys($conn, $bucket->id);
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($names);
            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_store_v1_process_entry($bucket, $key) {
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
    else if (preg_match('/^\/store\/v1\/bucket\/([^\/]+)\/keys$/', $path, $matches)) {
        $bucket = $matches[1];
        kvs_store_v1_process_keys($bucket);
    }
    else if (preg_match('/^\/store\/v1\/bucket\/([^\/]+)\/entry\/([a-zA-Z0-9-_\.]+)$/', $path, $matches)) {
        $bucket = $matches[1];
        $key = $matches[2];
        kvs_store_v1_process_entry($bucket, $key);
    }
    else {
        http_response_code(404);
        header("Content-Type: text/plain");
        echo "Connected successfully\n";
        echo $_SERVER['PATH_INFO'] . "\n";      
    }
}

?>