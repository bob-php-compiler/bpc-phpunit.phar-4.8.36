<?php
/**
 * @group exception
 */
class ExceptionTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('exception');

    public function testCheckExp()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches('/^found invalid tag (div|p)$/');

        throw new InvalidArgumentException('found invalid tag div');
    }
}
