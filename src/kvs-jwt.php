<?php

# A minimal JWT implementation, only covering 
# the required HS256 token type.

namespace KeyValueStore\JWT;

require_once('kvs-base64url.php');

use function KeyValueStore\Base64Url\{b64url_encode, b64url_decode};


class JwtException extends \Exception
{
    public function __construct($message, $code = 0, ?Thowable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}


const DEFAULT_LEEWAY = 5 * 60;

function jwt_sign($message, $alg, $key)
{
    if ($alg != "HS256")
    {
        throw new JwtException("algorithm not supported");
    }

    $signature = hash_hmac("SHA256", $message, $key, true);
    return $signature;
}

function jwt_verify($signature, $message, $alg, $key)
{
    if ($alg != "HS256")
    {
        throw new JwtException("algorithm not supported");
    }

    $computed_signature = hash_hmac("SHA256", $message, $key, true);
    $computed_signature = b64url_encode($computed_signature);
    if ($signature !== $computed_signature)
    {
        throw new JwtException("signature invalid");
    }
}

function jwt_decode_header($header)
{
    $header = b64url_decode($header);
    if ($header === false)
    {
        throw new JwtException("invalid header encoding b64");
    }
    $header = json_decode($header, true);
    if (!is_array($header))
    {
        throw new JwtException("invalid header encoding");
    }
    if (!array_key_exists('alg', $header))
    {
        throw new JwtException('missing field alg in header');
    }
    if (!array_key_exists('typ', $header))
    {
        throw new JwtException('missing field typ in header');
    }
    if ($header['typ'] !== 'JWT')
    {
        throw new JwtExcpetion('invalid token type');
    }
    $alg = $header['alg'];
    return $alg;
}

function jwt_decode_payload($payload)
{
    $payload = b64url_decode($payload);
    if ($payload === false)
    {
        throw new JwtException("invalid payload encoding");
    }
    $payload = json_decode($payload, true);
    if (!is_array($payload))
    {
        throw new JwtException("invalid payload encoding");
    }

    return $payload;
}


function jwt_create(
    array $claims,
    string $alg,
    string $key
): string {
    $header = array(
        'alg' => $alg,
        'typ' => 'JWT'
    );
    $header  = b64url_encode(json_encode($header));
    $payload = b64url_encode(json_encode((object) $claims));

    $message = $header . '.' . $payload;
    $signature = b64url_encode(jwt_sign($message, $alg, $key));

    $token = $message . '.' . $signature;
    return $token;
}

function jwt_verify_and_decode(
    $token,
    $type,
    $key,
    $leeway=DEFAULT_LEEWAY,
    $timestamp = null
) {
    $parts = explode('.', $token, 4);
    if (count($parts) != 3)
    {
        throw new JwtException("invalid token format");
    }

    $header = $parts[0];
    $alg = jwt_decode_header($header);

    $payload = $parts[1];
    $message = $header . '.' . $payload;

    $signature = $parts[2];
    jwt_verify($signature, $message, $alg, $key);

    $payload = jwt_decode_payload($payload);

    // ToDo: check nbf, iat, exp

    return $payload;
}

?>