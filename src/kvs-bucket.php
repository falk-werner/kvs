<?php

class Bucket {
    public $id;
    public $name;
    public $max_entries;

    public function __construct($id, $name, $max_entries) {
        $this->id = $id;
        $this->name = $name;
        $this->max_entries = $max_entries;
    }
}

?>