<?php
class Util
{
    public function generatePass(string $p1)
    {
        return md5($p1);
    }
}
/**
 * @group mock
 */
class MockTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('mock');

    public function testName()
    {
        $mockUtil = $this->getMock('Util', array(), array(), 'Util_FakeName');

        $mockUtil->expects($this->once())
                 ->method('generatePass')
                 ->with($this->equalTo('p1'))
                 ->will($this->returnValue('abcdefghijklmnopqrstuvwxyz123456'));

        $this->assertEquals($mockUtil->generatePass('p1'), 'abcdefghijklmnopqrstuvwxyz123456');
    }
}
?>
