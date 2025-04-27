<?php

# URL safe Base64 encoding as specifien in RFC4648 section 5
# https://datatracker.ietf.org/doc/html/rfc4648#section-5

namespace KeyValueStore\Base64Url;

function b64url_encode(string $data): string
{
    $encoded = base64_encode($data);
    $encoded = strtr($encoded, '+/', '-_');
    $encoded = str_replace('=', '', $encoded);

    return $encoded;
}

function b64url_decode(string $data): string | false
{
    $encoded = strtr($data, '-_', '+/');
    $remainder = strlen($encoded) % 4;
    if ($remainder > 0)
    {
        $encoded .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode($encoded, true);
}


?>