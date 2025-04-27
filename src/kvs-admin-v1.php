<?php

define('KVS_ADMIN_V1_PREFIX', '/admin/v1/');

function kvs_store_v1_process() {

    $path = substr($_SERVER['PATH_INFO'], strlen(KVS_ADMIN_V1_PREFIX));
    if ($path == "bucket") {

    }
    else if (preg_match("/^bucket\/([^\/]+)$/", $path, $matches)) {
        $bucket = matches[1];
    }

}

?>