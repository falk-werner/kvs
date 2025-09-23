<?php

function http_get_header($name) {
    foreach (getallheaders() as $header => $value) {
        if ($name == strtolower($header)) {
            return $value;
        }
    }

    return "";
}

function kvs_read_value($max_size) {
    $input = fopen('php://input', 'rb');
    $data = fread($input, $max_size);

    // read another character to detect end of file
    $dummy = fread($input, 1);
    if (!feof($input)) {
        fclose($input);
        return false;
    }    
    fclose($input);

    return $data;
}


?>