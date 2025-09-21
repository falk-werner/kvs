<?php

include_once('kvs-config.php');
if (!defined('KEYVALUESTORE_CONFIG_VALID'))
{
  http_response_code(500);
  header("Content-Type: text/plain");
  echo "missing configuration";
  exit;
}

require_once('kvs-bucket.php');
require_once('kvs-db.php');
require_once('kvs-store-v1.php');

$path = $_SERVER['PATH_INFO'];

kvs_db_check();

if (str_starts_with($path, KVS_STORE_V1_PREFIX)) {
  kvs_store_v1_process();
}
else {
  http_response_code(404);
  header("Content-Type: text/plain");
  echo "404 - Not Found\n";
}


?>