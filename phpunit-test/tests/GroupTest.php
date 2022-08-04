<?php
/**
 * @group group
 */
class GroupTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('group');
    public $foo = '';

    public function setUp()
    {
        $this->foo = include __DIR__ . '/fixture.php';
    }

    static $groupsTestRoute = array('group001', 'route');
    /**
     * @group group001
     * @group route
     */
    public function testRoute()
    {
        $stack = array();
        $this->assertEmpty($stack);
        $this->assertEquals('foo', $this->foo);

        return $stack;
    }

    static $groupsTestInterview = array('group002', 'interview');
    /**
     * @group group002
     * @group interview
     */
    public function testInterview()
    {
        $stack = array();
        $this->assertEmpty($stack);

        return $stack;
    }
}
