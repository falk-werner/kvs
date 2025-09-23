<?php

use KeyValueStore\Config as config;
use function KeyValueStore\Bucket\create_bucket_name;
use KeyValueStore\Bucket\Bucket;

mysqli_report(MYSQLI_REPORT_OFF);

const SCHEMA_VERSION = 2;

const TABLE_META   = config\DB_TABLE_PREFIX . 'meta';
const TABLE_BUCKET = config\DB_TABLE_PREFIX . 'bucket';
const TABLE_ENTRY  = config\DB_TABLE_PREFIX . 'entry';


function drop_table($conn, $table) {
  $result = $conn->query('DROP TABLE IF EXISTS `' . $table . '`');
  if ($result === false) {
    die('failed to create database: failed to drop table \"' . $table . '\": ' . $conn->error);
  }
}

function create_table_meta($conn) {
  drop_table($conn, TABLE_META);

  $result = $conn->query('CREATE TABLE `' . TABLE_META . '` (
    schema_version INT UNSIGNED
  )');
  if ($result === false) {
    die('failed to create database: failed to create meta information table: ' . $conn->error);
  }

  $result = $conn->query('INSERT INTO `' . TABLE_META . '` 
    (schema_version) VALUES 
    (' . SCHEMA_VERSION . ')');
  if ($result === false) {
    die('failed to create database: failed to initialize meta information: ' . $conn->error);
  }
}

function create_table_bucket($conn) {
  drop_table($conn, TABLE_BUCKET);

  $result = $conn->query('CREATE TABLE `' . TABLE_BUCKET . '` (
    bucket_id INT UNSIGNED AUTO_INCREMENT,
    name VARCHAR(32) NOT NULL UNIQUE,
    comment VARCHAR(255) NOT NULL DEFAULT "",
    max_entries INT UNSIGNED,
    PRIMARY KEY (bucket_id)
  )');
  if ($result === false) {
    die('failed to create database: failed to create bucket table: ' . $conn->error);
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
    die('failed to create database: failed to create entry table: ' . $conn->error);
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

function kvs_remove_bucket($conn, $name) {
  $bucket = kvs_bucket_by_name($conn, $name);
  if (!$bucket) {
    return false;
  }
  kvs_entry_remove_all($conn, $bucket->id);

  $stmt = $conn->prepare('DELETE FROM `' . TABLE_BUCKET . '` WHERE name=?');
  $stmt->bind_param("s", $name);
  $result = $stmt->execute();

  return ($result != false);
}

function kvs_bucket_by_name($conn, $name) {
  $stmt = $conn->prepare('SELECT bucket_id, name, comment, max_entries FROM `' . TABLE_BUCKET . '` WHERE name=?');
  $stmt->bind_param("s", $name);
  $result = $stmt->execute();
  if (!$result) {
    return false;
  }

  $stmt->bind_result($bucket_id, $bucket_name, $comment, $max_entries);
  $result = $stmt->fetch();
  if (!$result) {
    return false;
  }

  return new Bucket($bucket_id, $bucket_name, $comment, $max_entries);
}

function kvs_list_buckets($conn) {
  $buckets = array();
  $stmt = $conn->prepare('SELECT name FROM `' . TABLE_BUCKET . '`');
  $result = $stmt->execute();
  if (!$result) {
    return $buckets;
  }

  $stmt->bind_result($bucket_name);

  while ($stmt->fetch()) {
    array_push($buckets, $bucket_name);
  }

  return $buckets;
}

function kvs_create_bucket($conn) {
  $bucket_name = create_bucket_name($conn);
  $max_entries = 100;

  while (kvs_bucket_by_name($conn, $bucket_name)) {
    $bucket_name = create_bucket_name($conn);
  }


  $stmt = $conn->prepare('INSERT INTO `' . TABLE_BUCKET . '` (name, max_entries) VALUES (?, ?)');
  $stmt->bind_param("si", $bucket_name, $max_entries);
  $result = $stmt->execute();
  if (!$result) {
    return false;
  }

  return $bucket_name;
}

function kvs_bucket_set_comment($conn, $bucket_id, $comment)
{
  $stmt = $conn->prepare('UPDATE `' . TABLE_BUCKET . '` SET comment=? WHERE bucket_id=?');
  $stmt->bind_param("si", $comment, $bucket_id);
  $result = $stmt->execute();
  if (!$result) {
    error_log('failed to update entry: '. $conn->error);
    return false;

  }
  return true;
}

function kvs_entry_list($conn, $bucket_id) {
  $entries = array();
  $stmt = $conn->prepare('SELECT name, content FROM `' . TABLE_ENTRY . '` WHERE bucket_id=?');
  $stmt->bind_param("s", $bucket_id);
  $result = $stmt->execute();
  if (!$result) {
    error_log("failed to list entries");
    return $names;
  }

  $stmt->bind_result($name, $content);
  while ($stmt->fetch()) {
    error_log("name: " . $name);
    $entries[$name] = $content;
  }

  return $entries;

}

function kvs_entry_remove_all($conn, $bucket_id) {
  $entries = array();
  $stmt = $conn->prepare('DELETE FROM `' . TABLE_ENTRY . '` WHERE bucket_id=?');
  $stmt->bind_param("s", $bucket_id);
  $result = $stmt->execute();
  return ($result != false);
}


function kvs_entry_list_keys($conn, $bucket_id) {
  $names = array();
  $stmt = $conn->prepare('SELECT name FROM `' . TABLE_ENTRY . '` WHERE bucket_id=?');
  $stmt->bind_param("s", $bucket_id);
  $result = $stmt->execute();
  if (!$result) {
    return $names;
  }

  $stmt->bind_result($name);
  while ($stmt->fetch()) {
    array_push($names, $name);
  }

  return $names;
}

function kvs_entry_count_keys($conn, $bucket_id) {
  $stmt = $conn->prepare('SELECT COUNT(entry_id) FROM `' . TABLE_ENTRY . '` WHERE bucket_id=?');
  $stmt->bind_param("s", $bucket_id);
  $result = $stmt->execute();
  if (!$result) {
    return -1;
  }

  $stmt->bind_result($count);
  if (!$stmt->fetch()) {
    return -1;
  }

  return $count;
}

function kvs_entry_get_id($conn, $bucket_id, $key) {
  $stmt = $conn->prepare('SELECT entry_id FROM `' . TABLE_ENTRY . '` WHERE bucket_id=? AND name=?');
  $stmt->bind_param("ss", $bucket_id, $key);
  $result = $stmt->execute();
  if (!$result) {
    error_log("failed to execute query");
    return false;
  }

  $stmt->bind_result($entry_id);
  if (!$stmt->fetch()) {
    error_log("failed to fetch");
    return false;
  }

  return $entry_id;
}


function kvs_entry_update($conn, $entry_id, $value) {
  $stmt = $conn->prepare('UPDATE `' . TABLE_ENTRY . '` SET content=? WHERE entry_id=?');
  $stmt->bind_param("si", $value, $entry_id);
  $result = $stmt->execute();
  if (!$result) {
    error_log('failed to update entry: '. $conn->error);
    return false;

  }
  return true;
}

function kvs_entry_create($conn, $bucket_id, $key, $value) {
  $stmt = $conn->prepare('INSERT INTO `' . TABLE_ENTRY . '` (bucket_id, name, content) VALUES (?, ?, ?)');
  $stmt->bind_param("sss", $bucket_id, $key, $value);
  $result = $stmt->execute();
  if (!$result) {
    error_log('failed to create entry: '. $conn->error);
    return false;

  }
  return true;
}

function kvs_entry_remove($conn, $bucket_id, $key) {
  $stmt = $conn->prepare('DELETE FROM `' . TABLE_ENTRY . '` WHERE bucket_id=? AND name=?');
  $stmt->bind_param("ss", $bucket_id, $key);
  $stmt->execute();
}

function kvs_entry_get_value($conn, $bucket_id, $key) {
  $stmt = $conn->prepare('SELECT content FROM `' . TABLE_ENTRY . '` WHERE bucket_id=? AND name=?');
  $stmt->bind_param("ss", $bucket_id, $key);
  $result = $stmt->execute();
  if (!$result) {
    return false;
  }

  $stmt->bind_result($value);
  if (!$stmt->fetch()) {
    error_log("failed to fetch");
    return false;
  }

  return $value;
}

?>