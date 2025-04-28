<?php

require_once('src/kvs-jwt.php');

use PHPUnit\Framework\TestCase;
use function KeyValueStore\JwT\{jwt_create, jwt_verify_and_decode, jwt_sign};
use KeyValueStore\JWT\JwtException;
use function KeyValueStore\Base64Url\{b64url_encode, b64url_decode};

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

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(0, count($payload));
    }

    public function testFailToDecodeInvalidPassword()
    {        
        $this->expectException(JwtException::class);
        $token = jwt_create(array(), 'HS256', 'secret-key');

        jwt_verify_and_decode($token, 'HS256', 'wrong-key');
    }


    public function testFailToDecodeInvalidToken()
    {
        $this->expectException(JwtException::class);
        $token = "invalid";

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testFailToDecodeInvalidType()
    {
        $this->expectException(JwtException::class);

        $header  = b64url_encode(json_encode(array(
            'alg' => 'HS256',
            'typ' => 'invalid'
        )));
        $payload = b64url_encode(json_encode((object) array()));
        $message = "$header.$payload";
        $signature = jwt_sign($message, 'HS256', 'secret-key');
        $token = "$message.$signature";

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testFailToDecodeUnknownAlgorithm()
    {
        $this->expectException(JwtException::class);

        $header  = b64url_encode(json_encode(array(
            'alg' => 'unknown',
            'typ' => 'JWT'
        )));
        $payload = b64url_encode(json_encode((object) array()));
        $message = "$header.$payload";
        $signature = jwt_sign($message, 'HS256', 'secret-key');
        $token = "$message.$signature";

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testDecodeUnexpired()
    {
        $exp = time() + 60; # expires in 60 seconds
        $token = jwt_create(array(
            'exp' => $exp
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('exp', $payload));
        $this->assertSame($exp, $payload['exp']);
    }

    public function testFailDecodeExpiredWithinLeeway()
    {
        $exp = time() - 60; # alreay expired, but still in leeway
        $token = jwt_create(array(
            'exp' => $exp
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('exp', $payload));
        $this->assertSame($exp, $payload['exp']);
    }

    public function testDecodeExpiredWithinCustomLeeway()
    {
        $leeway = 10 * 60;
        $exp = time() - $leeway + 60; # alreay expired, but still in leeway
        $token = jwt_create(array(
            'exp' => $exp
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key', $leeway);
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('exp', $payload));
        $this->assertSame($exp, $payload['exp']);
    }

    public function testFailDecodeExpired()
    {
        $this->expectException(JwtException::class);

        $exp = time() - 600; # alreay expired, clearly not in leeway
        $token = jwt_create(array(
            'exp' => $exp
        ), 'HS256', 'secret-key');

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testDecodeAlreadyValid()
    {
        $nbf = time() - 60; # valid since 60 seconds
        $token = jwt_create(array(
            'nbf' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('nbf', $payload));
        $this->assertSame($nbf, $payload['nbf']);
    }

    public function testDecodeValidWithinLeeway()
    {
        $nbf = time() + 60; # not valid yet, but in default leeway
        $token = jwt_create(array(
            'nbf' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('nbf', $payload));
        $this->assertSame($nbf, $payload['nbf']);
    }

    public function testDecodeValidWithinCustomLeeway()
    {
        $nbf = time() + (9 * 60); # not valid yet, but in custom leeway
        $token = jwt_create(array(
            'nbf' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key', 600);
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('nbf', $payload));
        $this->assertSame($nbf, $payload['nbf']);
    }

    public function testFailDecodeNotValidYet()
    {
        $this->expectException(JwtException::class);

        $exp = time() + 600; # not valid yet, clearly not in leeway
        $token = jwt_create(array(
            'nbf' => $exp
        ), 'HS256', 'secret-key');

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testDecodeIgnoreIatIfNbfPresent()
    {
        $exp = time() + 600; # not valid yet, clearly not in leeway
        $token = jwt_create(array(
            'nbf' => time(),
            'iat' => $exp
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame($exp, $payload['iat']);
    }

    public function testFailDecodeNotValidYetFallbackToIat()
    {
        $this->expectException(JwtException::class);

        $exp = time() + 600; # not valid yet, clearly not in leeway
        $token = jwt_create(array(
            'iat' => $exp,
        ), 'HS256', 'secret-key');

        jwt_verify_and_decode($token, 'HS256', 'secret-key');
    }

    public function testDecodeAlreadyValidFromIat()
    {
        $nbf = time() - 60; # valid since 60 seconds
        $token = jwt_create(array(
            'iat' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('iat', $payload));
        $this->assertSame($nbf, $payload['iat']);
    }

    public function testDecodeValidFromIatWithinLeeway()
    {
        $nbf = time() + 60; # not valid yet, but in default leeway
        $token = jwt_create(array(
            'iat' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key');
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('iat', $payload));
        $this->assertSame($nbf, $payload['iat']);
    }

    public function testDecodeValidFromIatWithinCustomLeeway()
    {
        $nbf = time() + (9 * 60); # not valid yet, but in custom leeway
        $token = jwt_create(array(
            'iat' => $nbf
        ), 'HS256', 'secret-key');

        $payload = jwt_verify_and_decode($token, 'HS256', 'secret-key', 600);
        $this->assertSame(1, count($payload));
        $this->assertTrue(array_key_exists('iat', $payload));
        $this->assertSame($nbf, $payload['iat']);
    }


}

?>