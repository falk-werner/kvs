<?php

require('src/kvs-bucket.php');

use PHPUnit\Framework\TestCase;
use function KeyValueStore\Bucket\create_bucket_name;

function is_allowed_char($char) {
    if (('0' <= $char) && ($char <= '9')) {
        return true;
    }
    if (('A' <= $char) && ($char <= 'Z')) {
        return true;
    }
    if (('a' <= $char) && ($char <= 'z')) {
        return true;
    }
    if (('_' == $char) || ('-' == $char)) {
        return true;
    }

    return false;
}

final class BucketNamedTest extends TestCase
{

    public function testBucketNameSize() {
        $bucket_id = create_bucket_name();
        $this->assertSame(32, strlen($bucket_id));
    }

    public function testBucketNameAllowedChars() {
        $bucket_id = create_bucket_name();
        foreach(str_split($bucket_id) as $char) {
            $this->assertTrue(is_allowed_char($char));

        }
    }

    public function testBucketNameIsRandom() {
        $bucket_id_a = create_bucket_name();
        $bucket_id_b = create_bucket_name();

        $this->assertFalse($bucket_id_a == $bucket_id_b);
    }

}

?>