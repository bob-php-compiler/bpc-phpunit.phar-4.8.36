<?php
/**
 * @group exception
 */
class ExceptionTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('exception');

    public function testCheckAll()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionCode(10);
        $this->expectExceptionMessageMatches('/^found invalid tag (div|p)$/');
        $this->expectExceptionMessageRegExp('/^found invalid tag (div|p)$/');

        throw new InvalidArgumentException('found invalid tag div', 10);
    }

    public function testOnlyCheckException()
    {
        $this->expectException('InvalidArgumentException');

        throw new InvalidArgumentException('found invalid tag div');
    }

    public function testOnlyCheckMessage()
    {
        $this->expectExceptionMessage('found invalid tag div');

        throw new InvalidArgumentException('found invalid tag div');
    }

    public function testOnlyCheckMessageMatches()
    {
        $this->expectExceptionMessageMatches('/^found invalid tag (div|p)$/');

        throw new InvalidArgumentException('found invalid tag div');
    }

    public function testOnlyCheckMessageRegExp()
    {
        $this->expectExceptionMessageRegExp('/^found invalid tag (div|p)$/');

        throw new InvalidArgumentException('found invalid tag div');
    }

    public function testOnlyCheckCode()
    {
        $this->expectExceptionCode(10);

        throw new InvalidArgumentException('found invalid tag div', 10);
    }
}
