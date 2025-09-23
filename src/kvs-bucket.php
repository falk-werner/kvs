<?php

namespace KeyValueStore\Bucket;

require_once('kvs-base64url.php');
use function KeyValueStore\Base64Url\b64url_encode;

class Bucket {
    public $id;
    public $name;
    public $comment;
    public $max_entries;

    public function __construct($id, $name, $comment, $max_entries) {
        $this->id = $id;
        $this->name = $name;
        $this->comment = $comment;
        $this->max_entries = $max_entries;
    }
}

function create_bucket_name() {
    $bytes = random_bytes(24);
    return b64url_encode($bytes);
}

?>