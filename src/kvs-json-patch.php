<?php

// JSON Patch is defined in RFC 6902
// https://datatracker.ietf.org/doc/html/rfc6902


// JSON Pointer is defined in RFC 6901
// https://datatracker.ietf.org/doc/html/rfc6901
function kvs_json_pointer_parse($path) {
    if (!str_starts_with($path, '/')) {
        return [null, "failed to parse json pointer: missing root"];
    }
    $path = substr($path, 1);

    if ("" == $path) {
        return [[], false];
    }

    $result = [];
    foreach (explode('/', $path) as $item) {
        $item = str_replace('~1', '/', $item);
        $item = str_replace('~0', '~', $item);
        array_push($result, $item);
    }

    return [$result, false];
}

function kvs_json_patch_add($target, $entry) {
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    if (!array_key_exists('value', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $value = $entry["value"];

    if ($path == "/") {
        return [$value, false];
    }

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($path as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (($top == "-") && (array_is_list($temp))) {
        array_push($temp, $value);
    }
    else {
        $temp[$top] = $value;
    }

    return [$target, false];
}

function kvs_json_patch_remove($target, $entry) {
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($path as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (!array_key_exists($top, $temp)) {
        return [null, "key not exists"];
    }

    if (array_is_list($temp)) {
        array_splice($temp, $top, 1);
    }
    else {
        unset($temp[$top]);
    }

    return [$target, false];
}

function kvs_json_patch_replace($target, $entry) {
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    if (!array_key_exists('value', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $value = $entry["value"];

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($path as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (!array_key_exists($top, $temp)) {
        return [null, "key not exists"];
    }   

    $temp[$top] = $value;
    return [$target, false];
}

function kvs_json_patch_move($target, $entry) {
    // check and get parameters
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    if (!array_key_exists('from', $entry)) {
        return [null, 'missing required property \"from\"'];
    }
    $from = $entry['from'];

    [$from, $err] = kvs_json_pointer_parse($from);
    if ($err !== false) {
        return [null, $err];
    }

    // get value
    $top = array_pop($from);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($from as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (!array_key_exists($top, $temp)) {
        return [null, "key not exists"];
    }

    $value = $temp[$top];
    unset($temp[$top]);    

    // set vaulue
    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($from as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }
    $temp[$top] = $value;

    return [$target, false];
}

function kvs_json_patch_copy($target, $entry) {
    // check and get parameters
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    if (!array_key_exists('from', $entry)) {
        return [null, 'missing required property \"from\"'];
    }
    $from = $entry['from'];

    [$from, $err] = kvs_json_pointer_parse($from);
    if ($err !== false) {
        return [null, $err];
    }

    // get value
    $top = array_pop($from);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($from as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (!array_key_exists($top, $temp)) {
        return [null, "key not exists"];
    }

    $value = $temp[$top];

    // set vaulue
    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($from as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }
    $temp[$top] = $value;

    return [$target, false];
}

function kvs_json_patch_test($target, $entry) {
    if (!array_key_exists('path', $entry)) {
        return [null, "missing required property \"path\""];
    }
    $path = $entry["path"];

    [$path, $err] = kvs_json_pointer_parse($path);
    if ($err !== false) {
        return [null, $err];
    }

    if (!array_key_exists('value', $entry)) {
        return [null, "missing required property \"value\""];
    }
    $value = $entry["value"];


    $top = array_pop($path);
    if (null == $top) {
        return [null, "element required"];
    }

    $temp = &$target;
    foreach($path as $item) {
        if (!array_key_exists($item, $temp)) {
            return [null, "key not exists"];
        }

        $temp = &$target[$item];
    }

    if (!array_key_exists($top, $temp)) {
        return [null, "key not exists"];
    }

    $other_value = $temp[$top];

    if ($other_value != $value) {
        return [null, "values not equal: "];
    }

    return [$target, false];
}


function kvs_json_patch_entry($value, $entry) {
    if (!array_key_exists('op', $entry)) {
        return [null, "missing required property \"op\""];
    }
    $op = $entry['op'];

    switch ($op) {
        case "add":
            return kvs_json_patch_add($value, $entry);
        case "remove":
            return kvs_json_patch_remove($value, $entry);
        case "replace":
            return kvs_json_patch_replace($value, $entry);
        case "move":
            return kvs_json_patch_move($value, $entry);
        case "copy":
            return kvs_json_patch_copy($value, $entry);
        case "test":
            return kvs_json_patch_test($value, $entry);
        default:
            return [null, "unknown operation: " . $op];
    }
}

function kvs_json_patch($value, $patch) {
    $temp = json_decode($value, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        return [null, "failed to decode value"];
    }
    $result_is_object = str_starts_with($value, '{');

    $patch = json_decode($patch, true);
    if (json_last_error() != JSON_ERROR_NONE) {
        return [null, "failed to decode patch"];
    }

    foreach ($patch as $entry) {
        [$temp, $err] = kvs_json_patch_entry($temp, $entry);
        if ($err !== false) {
            return [null, $err];
        }
    }

    $result = $result_is_object ? json_encode($temp, JSON_FORCE_OBJECT) : json_encode($temp);
    return [$result, false];
}



?>