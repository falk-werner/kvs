<?php

require('src/kvs-base64url.php');

use PHPUnit\Framework\TestCase;
use function KeyValueStore\Base64Url\{b64url_encode, b64url_decode};

final class Base64UrlTest extends TestCase
{
    public function testEncodeAndDecode()
    {
        $plain = "foo";
        $encoded = b64url_encode($plain);
        $this->assertSame($encoded, "Zm9v");

        $decoded = b64url_decode($encoded);
        $this->assertSame($plain, $decoded);
    }

    public function testEncodeAndDecodeWithOneCharRemainder()
    {
        $plain = "fo";
        $this->assertSame(base64_encode($plain), "Zm8=");

        $encoded = b64url_encode($plain);
        $this->assertSame($encoded, "Zm8");
        
        $decoded = b64url_decode($encoded);
        $this->assertSame($plain, $decoded);
    }

    public function testEncodeAndDecodeWithTwoCharsRemainder()
    {
        $plain = "f";
        $this->assertSame(base64_encode($plain), "Zg==");

        $encoded = b64url_encode($plain);
        $this->assertSame($encoded, "Zg");

        $decoded = b64url_decode($encoded);
        $this->assertSame($plain, $decoded);
    }

    public function testEncodeAndDecodeUrlSafe()
    {
        $plain = "00?00>";
        $this->assertSame(base64_encode($plain), "MDA/MDA+");

        $encoded = b64url_encode($plain);
        $this->assertSame($encoded, "MDA_MDA-");

        $decoded = b64url_decode($encoded);
        $this->assertSame($plain, $decoded);
    }

    public function testFailToDecodeInvalid()
    {
        $encoded = "+";
        $decoded = b64url_decode($encoded);

        $this->assertFalse($decoded);
    }

}

?>