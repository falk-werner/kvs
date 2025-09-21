<?php

function http_get_header($name) {
    foreach (getallheaders() as $header => $value) {
        if ($name == strtolower($header)) {
            return $value;
        }
    }

    return "";
}

?>