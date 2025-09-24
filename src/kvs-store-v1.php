<?php

define('KVS_STORE_V1_PREFIX', '/store/v1/');

function get_allowed_origin_header($bucket) {
    if ($bucket->allowed_origin) {
        if ($bucket->allowed_origin == "*") {
            return "Access-Control-Allow-Origin: *";
        }

        $orign = http_get_header("origin");
        if ($origin == $bucket->allowed_origin) {
            return "Access-Control-Allow-Origin: " . $origin;
        }

        return false;
    }

    return "";
}

function kvs_store_v1_process_bucket($bucket_name) {
    $conn = kvs_db_open();
    $bucket = kvs_bucket_by_name($conn, $bucket_name);
    if (!$bucket) {
        http_response_code(404);
        return;
    }

    $allowed_origin = get_allowed_origin_header($bucket);
    if ($allowed_origin === false) {
        http_response_code(400);
        return;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $entries = kvs_entry_list($conn, $bucket->id);
            http_response_code(200);
            header('Content-Type: application/json');
            kvs_header($allowed_origin);
            echo json_encode($entries, JSON_FORCE_OBJECT);
            break;
        case 'DELETE':
            kvs_entry_remove_all($conn, $bucket->id);
            http_response_code(200);
            kvs_header($allowed_origin);
            break;
        default:
            http_response_code(405);
            kvs_header($allowed_origin);
            break;
    }
}

function kvs_store_v1_process_keys($bucket_name) {
    $conn = kvs_db_open();
    $bucket = kvs_bucket_by_name($conn, $bucket_name);
    if (!$bucket) {
        http_response_code(404);
        return;
    }

    $allowed_origin = get_allowed_origin_header($bucket);
    if ($allowed_origin === false) {
        http_response_code(400);
        return;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $names = kvs_entry_list_keys($conn, $bucket->id);
            http_response_code(200);
            header('Content-Type: application/json');
            kvs_header($allowed_origin);
            echo json_encode($names);
            break;
        default:
            http_response_code(405);
            kvs_header($allowed_origin);
            break;
    }
}

function kvs_store_v1_process_entry($bucket_name, $key) {
    $conn = kvs_db_open();
    $bucket = kvs_bucket_by_name($conn, $bucket_name);
    if (!$bucket) {
        http_response_code(404);
        return;
    }

    $allowed_origin = get_allowed_origin_header($bucket);
    if ($allowed_origin === false) {
        http_response_code(400);
        return;
    }

    if (strlen($key) > 256) {
        http_response_code(404);
        header('Access-Control-Allow-Origin: *');
        return;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $value = kvs_entry_get_value($conn, $bucket->id, $key);
            if ($value === false) {
                http_response_code(404);
                kvs_header($allowed_origin);
                return;
            }
            http_response_code(200);
            header('Content-Type: text/plain');
            kvs_header($allowed_origin);
            echo "$value";
            break;
        case 'PUT':
        case 'POST':
            $value = kvs_read_value(10240);
            $count = kvs_entry_count_keys($conn, $bucket->id);
            if ($count >= $bucket->max_entries) {
                http_response_code(400);
                header('Content-Type: text/plain');
                kvs_header($allowed_origin);
                echo "Too many entries";
                return;
            }
            error_log("count: " . $count);
            $entry_id = kvs_entry_get_id($conn, $bucket->id, $key);
            if ($entry_id) {
                error_log("update");
                $success = kvs_entry_update($conn, $entry_id, $value);
                http_response_code($success ? 200 : 500);
                kvs_header($allowed_origin);
            }
            else {
                $success = kvs_entry_create($conn, $bucket->id, $key, $value);
                http_response_code($success ? 201 : 500);
                kvs_header($allowed_origin);
            }
            break;
        case 'DELETE':
            kvs_entry_remove($conn, $bucket->id, $key);
            http_response_code(204);
            kvs_header($allowed_origin);
            break;
        default:
            http_response_code(405);
            kvs_header($allowed_origin);
            break;
    }
}

function kvs_store_v1_process($path) {

    $path = substr($path, strlen(KVS_ADMIN_V1_PREFIX));
    if (preg_match('/^bucket\/([^\/]+)$/', $path, $matches)) {
        $bucket = $matches[1];
        kvs_store_v1_process_bucket($bucket);
    }
    else if (preg_match('/^bucket\/([^\/]+)\/keys$/', $path, $matches)) {
        $bucket = $matches[1];
        kvs_store_v1_process_keys($bucket);
    }
    else if (preg_match('/^bucket\/([^\/]+)\/entry\/([a-zA-Z0-9-_\.]+)$/', $path, $matches)) {
        $bucket = $matches[1];
        $key = $matches[2];
        kvs_store_v1_process_entry($bucket, $key);
    }
    else {
        http_response_code(404);
    }
}

?>