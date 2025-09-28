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

    public function testAddFailsWithMissingValue() {
        $value = "{}";
        $patch = "[{\"op\": \"add\", \"path\": \"/\"}]";

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

    public function testAddToEmptyObject() {
        $value = "{}";
        $patch = "[{\"op\": \"add\", \"path\": \"/a\", \"value\": 42}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":42}", $result);
    }


    public function testAddKeyToObject() {
        $value = "{\"a\": {\"foo\": 1}}";
        $patch = "[{\"op\": \"add\", \"path\": \"/a/b\", \"value\": 2}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":{\"foo\":1,\"b\":2}}", $result);
    }

    public function testAddReplacesArrayItemAtFront() {
        $value = "[\"apple\"]";
        $patch = "[{\"op\": \"add\", \"path\": \"/0\", \"value\": \"orange\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("[\"orange\"]", $result);
    }

    public function testAddReplacesArrayItemAtSpecificIndex() {
        $value = "[\"apple\", \"orange\", \"banana\"]";
        $patch = "[{\"op\": \"add\", \"path\": \"/1\", \"value\": \"pepper\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("[\"apple\",\"pepper\",\"banana\"]", $result);
    }

    public function testAddNewArrayItem() {
        $value = "[\"apple\"]";
        $patch = "[{\"op\": \"add\", \"path\": \"/-\", \"value\": \"orange\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("[\"apple\",\"orange\"]", $result);
    }

    public function testAddFailWhenPathNotExists() {
        $value = "{\"q\": {\"foo\": 1}}";
        $patch = "[{\"op\": \"add\", \"path\": \"/a/b\", \"value\": 2}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertNotFalse($err);
        $this->assertSame(null, $result);
    }

    public function testRemoveKeyFromObject() {
        $value = "[\"apple\", \"orange\", \"banana\"]";
        $patch = "[{\"op\": \"remove\", \"path\": \"/1\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("[\"apple\",\"banana\"]", $result);
    }

    public function testRemoveItemFromArray() {
        $value = "{\"a\": 1, \"b\": 2}";
        $patch = "[{\"op\": \"remove\", \"path\": \"/a\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"b\":2}", $result);
    }

    public function testReplace() {
        $value = "{\"a\": 1}";
        $patch = "[{\"op\": \"replace\", \"path\": \"/a\", \"value\": 2}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":2}", $result);
    }

    public function testMove() {
        $value = "{\"a\": 1}";
        $patch = "[{\"op\": \"move\", \"from\": \"/a\", \"path\": \"/b\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"b\":1}", $result);
    }

    public function testCopy() {
        $value = "{\"a\": 1}";
        $patch = "[{\"op\": \"copy\", \"from\": \"/a\", \"path\": \"/b\"}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":1,\"b\":1}", $result);
    }

    public function testTest() {
        $value = "{\"a\": 1}";
        $patch = "[{\"op\": \"test\", \"path\": \"/a\", \"value\": 1}]";

        [$result, $err] = kvs_json_patch($value, $patch);
        $this->assertFalse($err);
        $this->assertSame("{\"a\":1}", $result);
    }

}

?>