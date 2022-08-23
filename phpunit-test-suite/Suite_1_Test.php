<?php

class Suite_1_Test extends PHPUnit_Framework_TestCase
{
    public function testCase1()
    {
        $this->assertTrue(true);
    }

    public function testCase2()
    {
        $this->assertFalse(false);
    }
}
