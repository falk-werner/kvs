<?php

use KeyValueStore\Config as config;

mysqli_report(MYSQLI_REPORT_OFF);

const SCHEMA_VERSION = 0;

const TABLE_META   = config\DB_TABLE_PREFIX . 'meta';
const TABLE_BUCKET = config\DB_TABLE_PREFIX . 'bucket';
const TABLE_ENTRY  = config\DB_TABLE_PREFIX . 'entry';


function drop_table($conn, $table) {
  $result = $conn->query('DROP TABLE IF EXISTS `' . $table . '`');
  if ($result === false) {
    die('failed to create database: failed to drop table \"' . $table . '\"');
  }
}

function create_table_meta($conn) {
  drop_table($conn, TABLE_META);

  $result = $conn->query('CREATE TABLE `' . TABLE_META . '` (
    schema_version INT UNSIGNED
  )');
  if ($result === false) {
    die('failed to create database: failed to create meta information table');
  }

  $result = $conn->query('INSERT INTO `' . TABLE_META . '` 
    (schema_version) VALUES 
    (' . SCHEMA_VERSION . ')');
  if ($result === false) {
    die('failed to create database: failed to initialize meta information');
  }
}

function create_table_bucket($conn) {
  drop_table($conn, TABLE_BUCKET);

  $result = $conn->query('CREATE TABLE `' . TABLE_BUCKET . '` (
    bucket_id INT UNSIGNED AUTO_INCREMENT,
    name VARCHAR(32) NOT NULL UNIQUE,
    max_entries INT UNSIGNED,
    PRIMARY KEY (bucket_id)
  )');
  if ($result === false) {
    die('failed to create database: failed to create bucket table');
  }

  // ToDo: Remove me
  // create sample bucket
  $result = $conn->query('INSERT INTO `' . TABLE_BUCKET . '`
    (name, max_entries) VALUES (\'foo\', 100)');
  if ($result === false) {
    die('failed to create database: failed to initialize bucket table' . $conn->error);
  }
}

function create_table_entry($conn) {
  drop_table($conn, TABLE_ENTRY);

  $result = $conn->query('CREATE TABLE `' . TABLE_ENTRY . '` (
    entry_id INT UNSIGNED AUTO_INCREMENT,
    bucket_id INT NOT NULL,
    name TEXT(256) NOT NULL,
    content TEXT(10240) NOT NULL,
    PRIMARY KEY (entry_id)
  )');
  if ($result === false) {
    die('failed to create database: failed to create entry table');
  }
}

function kvs_db_open() {
  // Create connection
  $conn = new mysqli(config\DB_HOST, config\DB_USER, config\DB_PASSWORD, config\DB_DATABASE);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  return $conn;
}

function kvs_db_check() {
  $conn = kvs_db_open();
  $recreate_db = true;
  $result = $conn->query('SELECT schema_version FROM `' . TABLE_META . '`');
  if ($result !== false) {
    $row = mysqli_fetch_row($result);
    if ($row) {
      $schema_version = $row[0];
      if ($schema_version == SCHEMA_VERSION) {
        $recreate_db = false;
      }
      else {
        error_log('database: wrong schema detected (' . $schema_version . '): re-create database');
      }
    }
    else {
      error_log('database: missing meta information: re-create database');
    }
  }
  else {
    error_log('database: missing meta information table - re-create database');
  }


  if ($recreate_db) {
    error_log('(re-) create database');
    create_table_bucket($conn);
    create_table_entry($conn);
    create_table_meta($conn);
  }
}



function kvs_bucket_by_name($conn, $name) {
  $result = $conn->query('SELECT bucket_id, name, max_entries FROM `' . TABLE_BUCKET . '`');
  if ($result === false) {
    return false;
  }

  $row = mysqli_fetch_row($result);
  if (!$row) {
    return false;
  }

  $bucket_id = $row[0];
  $bucket_name = $row[1];
  $max_entries = $row[2];

  return new Bucket($bucket_id, $bucket_name, $max_entries);
}


?>