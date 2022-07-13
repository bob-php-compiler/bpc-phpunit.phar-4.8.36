<?php
/**
 * @group depend
 */
class DependTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('depend');

    public function testEmpty()
    {
        $stack = array();
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * @depends testEmpty
     */
    public function testPush(array $stack)
    {
        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertNotEmpty($stack);

        return $stack;
    }

    public static function dependsTestPush()
    {
        return array('testEmpty');
    }

    /**
     * @depends testPush
     */
    public function testPop(array $stack)
    {
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEmpty($stack);
    }

    public static function dependsTestPop()
    {
        return array('testPush');
    }
}
