<?php
/**
 * @group dataProvider
 */
class DataProviderTest extends PHPUnit_Framework_TestCase
{
    static $classGroups = array('dataProvider');

    /**
     * @dataProvider dataProviderTestAdd
     */
    public function testAdd($a, $b, $expected)
    {
        $this->assertEquals($expected, $a + $b);
    }

    public function dataProviderTestAdd()
    {
        return array(
          array(0, 0, 0),
          array(0, 1, 1),
          array(1, 0, 1),
          array(1, 1, 2)
        );
    }
}
