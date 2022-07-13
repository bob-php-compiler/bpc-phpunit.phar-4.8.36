<?php
class Stack {
    public static $classData = array();
    public static $methodData = array();
}
/**
 * @group hook
 */
class HookTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('hook');

    /**
     * @beforeClass
     */
    public static function classPush()
    {
        array_push(Stack::$classData, 'classData');
    }
    public function beforeClassMethodClassPush() {}

    /**
     * @before
     */
    public function methodPush1()
    {
        array_push(Stack::$methodData, 'd1');
    }
    public function beforeMethodMethodPush1() {}

    /**
     * @afterClass
     */
    public static function classPop()
    {
        if (Stack::$classData != array('classData')) {
            throw new Exception('after class failed');
        }
    }
    public function afterClassMethodClassPop() {}

    /**
     * @after
     */
    public function methodPush2()
    {
        array_push(Stack::$methodData, 'd2');
    }
    public function afterMethodMethodPush2() {}




    public function testPush()
    {
        $this->assertEquals(array('classData'), Stack::$classData);
        $this->assertEquals(array('d1'), Stack::$methodData);
    }

    public function testPop()
    {
        $this->assertEquals(array('classData'), Stack::$classData);
        $this->assertEquals(array('d1', 'd2', 'd1'), Stack::$methodData);
    }
}
