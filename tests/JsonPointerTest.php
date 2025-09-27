<?php

require_once('src/kvs-json-patch.php');

use PHPUnit\Framework\TestCase;

final class JsonPointerTest extends TestCase
{
    public function testParseRoot() {
        [$parsed, $err] = kvs_json_pointer_parse("/");
        $this->assertFalse($err);
        $this->assertSame(0, count($parsed));
    }

    public function testFailParseMissingRoot() {
        [$parsed, $err] = kvs_json_pointer_parse("missing-root");
        $this->assertNotFalse($err);
        $this->assertSame(null, $parsed);
    }

    public function testParsePath() {
        [$parsed, $err] = kvs_json_pointer_parse("/some/path/to/parse");
        $this->assertFalse($err);
        $this->assertSame(4, count($parsed));
        $this->assertSame("some", $parsed[0]);
        $this->assertSame("path", $parsed[1]);
        $this->assertSame("to", $parsed[2]);
        $this->assertSame("parse", $parsed[3]);
    }

    public function testReplaceSlash() {
        [$parsed, $err] = kvs_json_pointer_parse("/some~1path~1to~1parse");
        $this->assertFalse($err);
        $this->assertSame(1, count($parsed));
        $this->assertSame("some/path/to/parse", $parsed[0]);
    }

   public function testReplaceTilde() {
        [$parsed, $err] = kvs_json_pointer_parse("/~0foo");
        $this->assertFalse($err);
        $this->assertSame(1, count($parsed));
        $this->assertSame("~foo", $parsed[0]);
   }

   // RFC 6901 requires to replace ~1 befor ~0 in order
   // to avoid replacing ~01 to ~1 and finally to /.
   public function testHonorRfc6901ReplacementOrder() {
        [$parsed, $err] = kvs_json_pointer_parse("/~01");
        $this->assertFalse($err);
        $this->assertSame(1, count($parsed));
        $this->assertSame("~1", $parsed[0]);
   }

}

?>