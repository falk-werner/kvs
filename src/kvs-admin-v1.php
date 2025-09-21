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

function kvs_admin_v1_process() {
    $headers = getallheaders();
    $auth = http_get_header("authorization");
    if (!$auth) {
        http_response_code(401);
        return;
    }
    if ($auth != "Bearer " . config\ADMIN_PASSWORD) {
        error_log("failed to access admin API: invalid token");
        http_response_code(401);
        return;
    }

    $path = substr($_SERVER['PATH_INFO'], strlen(KVS_ADMIN_V1_PREFIX));
    if ($path == "bucket") {
        kvs_admin_v1_process_bucket();
    }
    else if (preg_match("/^bucket\/([^\/]+)$/", $path, $matches)) {
        $bucket = matches[1];
    }

}

?>