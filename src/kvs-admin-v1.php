<?php

use KeyValueStore\Config as config;

define('KVS_ADMIN_V1_PREFIX', '/admin/v1/');

function kvs_admin_v1_process_bucket() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $conn = kvs_db_open();
            $buckets = kvs_list_buckets($conn);

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($buckets);
            break;
        case 'PUT':
            // fall-through
        case 'POST':
            $conn = kvs_db_open();
            $bucket = kvs_create_bucket($conn);
            if ($bucket) {
                $response = array(
                    "name" => $bucket
                );
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode($response);
            }
            else {
                http_response_code(500);
            }

            break;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_admin_v1_process_named_bucket($bucket_name) {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $conn = kvs_db_open();
            $bucket = kvs_bucket_by_name($conn, $bucket_name);
            if (!$bucket) {
                http_response_code(404);
                return;
            }

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(array(
                "name" => $bucket->name,
                "comment" => $bucket->comment,
                "max_entries" => $bucket->max_entries
            ));
            break;
        case 'DELETE':
            $conn = kvs_db_open();
            $bucket = kvs_bucket_by_name($conn, $bucket_name);
            if ($bucket) {
                kvs_remove_bucket($conn, $bucket_name);
            }

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(array(
                "name" => $bucket->name
            ));
            return;
        default:
            http_response_code(405);
            break;
    }
}

function kvs_admin_v1_process_bucket_comment($bucket_name) {
    $conn = kvs_db_open();
    $bucket = kvs_bucket_by_name($conn, $bucket_name);
    if (!$bucket) {
        http_response_code(404);
        return;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            http_response_code(200);
            header('Content-Type: text/plain');
            echo $bucket->comment;
            break;
        case 'PUT':
            // fall-through
        case 'POST':
            $comment = kvs_read_value(255);
            kvs_bucket_set_comment($conn, $bucket->id, $comment);
            http_response_code(204);
            return;
        default:
            http_response_code(405);
            break;
    }
}


function kvs_admin_v1_process($path) {
    // Penalty for using admin API.
    // This slows down brute force attacks.
    usleep(100 * 1000);

    $headers = getallheaders();
    $auth = http_get_header("authorization");
    if (!$auth) {
        http_response_code(401);
        return;
    }
    if ($auth != "Bearer " . config\ADMIN_TOKEN) {
        error_log("failed to access admin API: invalid token");
        http_response_code(401);
        return;
    }

    $path = substr($path, strlen(KVS_ADMIN_V1_PREFIX));
    if ($path == "bucket") {
        kvs_admin_v1_process_bucket();
    }
    else if (preg_match("/^bucket\/([^\/]+)$/", $path, $matches)) {
        $bucket_name = $matches[1];
        kvs_admin_v1_process_named_bucket($bucket_name);
    }
    else if (preg_match("/^bucket\/([^\/]+)\/comment$/", $path, $matches)) {
        $bucket_name = $matches[1];
        kvs_admin_v1_process_bucket_comment($bucket_name);
    }
    else {
        http_response_code(404);
    }

}

?>