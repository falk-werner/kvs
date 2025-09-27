<?php

require_once('src/kvs-json-patch.php');

use PHPUnit\Framework\TestCase;

final class JsonPatchTest extends TestCase
{
    public function testInvalidJsonValue() {
        $value = "invalid";
        $patch = "[]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testInvalidJsonPatch() {
        $value = "{}";
        $patch = "invalid";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testNoChangeWithEmptyPatch() {
        $value = "{}";
        $patch = "[]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{}", $result);
    }

    public function testMissingOperation() {
        $value = "{}";
        $patch = "[{\"path\": \"/\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testMissingPath() {
        $value = "{}";
        $patch = "[{\"op\": \"add\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testUnknownOperation() {
        $value = "{}";
        $patch = "[{\"op\": \"unknown\", \"path\": \"/\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testAddReplaceWholeObject() {
        $value = "{}";
        $patch = "[{\"op\": \"add\", \"path\": \"/\", \"value\": true}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("true", $result);
    }

    public function testAddKey() {
        $value = "{\"a\": {\"foo\": 1}}";
        $patch = "[{\"op\": \"add\", \"path\": \"/a/b\", \"value\": 2}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":{\"foo\":1,\"b\":2}}", $result);
    }

    public function testAddFailWhenPathNotExists() {
        $value = "{\"q\": {\"foo\": 1}}";
        $patch = "[{\"op\": \"add\", \"path\": \"/a/b\", \"value\": 2}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testRemoveKeyFromObject() {
        $value = "{\"a\": 1, \"b\": 2}";
        $patch = "[{\"op\": \"remove\", \"path\": \"/a\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"b\":2}", $result);
    }

}

?>