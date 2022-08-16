<?php
class Util
{
    public function generatePass(string $p1)
    {
        return md5($p1);
    }
}

abstract class Zend_Db_Adapter_Abstract
{
    protected $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    abstract public function listTables();
}

interface Zend_Session_SaveHandler_Interface
{
    public function open($save_path, $name);
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

    // Zend_Paginator_Adapter_DbSelectTest
    public function testCacheIdentifierIsHashOfAssembledSelect()
    {
        $dbAdapter = $this->getMockForAbstractClass('Zend_Db_Adapter_Abstract', array(''), '', false);
        $this->assertTrue($dbAdapter instanceof Zend_Db_Adapter_Abstract);
    }

    // Zend_Application_Resource_SessionTest
    public function testSetSaveHandler()
    {
        $saveHandler = $this->getMock('Zend_Session_SaveHandler_Interface');
        $this->assertTrue($saveHandler instanceof Zend_Session_SaveHandler_Interface);
    }
}
?>
