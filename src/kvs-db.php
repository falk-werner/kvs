<?php

use KeyValueStore\Config as config;

mysqli_report(MYSQLI_REPORT_OFF);

const TABLE_USER = config\DB_TABLE_PREFIX . 'user';

function kvs_db_check() {

    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);

    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }
    
    $result = $conn->query('SELECT * FROM `' . TABLE_USER . '`');
    if ($result === false) {
      echo "failed to access\n";
    }
    

}


?>