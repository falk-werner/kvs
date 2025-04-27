<?php

require_once('src/kvs-jwt.php');

use PHPUnit\Framework\TestCase;
use function KeyValueStore\JwT\{jwt_create, jwt_verify_and_decode};
use KeyValueStore\JWT\JwtException;
use function KeyValueStore\Base64Url\b64url_decode;

final class JwtTest extends TestCase
{
    public function testTokenHasHeaderPayloadAndSignature()
    {
        $token = jwt_create(array(), "HS256", "secret-key");
        $parts = explode('.', $token, 4);

        $this->assertSame(3, count($parts));
    }

    public function testHeaderContainsAlgAndType()
    {
        $token = jwt_create(array(), "HS256", "secret-key");
        $parts = explode('.', $token, 4);
        $this->assertSame(3, count($parts));
        $header = json_decode(b64url_decode($parts[0]), true);
        
        $this->assertSame(2, count($header));
        $this->assertSame('HS256', $header['alg']);
        $this->assertSame('JWT', $header['typ']);
    }

    public function testPayloadIsProvided()
    {
        $token = jwt_create(array(
            'iss' => 'me',
            'sub' => 'token'
        ), "HS256", "secret-key");
        $parts = explode('.', $token, 4);
        $this->assertSame(3, count($parts));
        $payload = json_decode(b64url_decode($parts[1]), true);
        
        $this->assertSame(2, count($payload));
        $this->assertSame('me', $payload['iss']);
        $this->assertSame('token', $payload['sub']);
    }

    public function testEncodeAndDecode()
    {
        $token = jwt_create(array(), "HS256", "secret-key");
        echo $token;
        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(0, count($payload));
    }

    public function testFailToDecodeInvalidToken()
    {
        $this->expectException(JwtException::class);
        $token = "invalid";

        jwt_verify_and_decode($token, 'HS256', 'secret');
    }

    public function testFailToDecodeInvalidType()
    {
        $this->expectException(JwtException::class);
        $token = "invalid";

        jwt_verify_and_decode($token, 'HS256', 'secret');
    }

}

?>