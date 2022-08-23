<?php

require_once __DIR__ . '/AbstractTest.php';

class BaseTest extends AbstractTest
{
    public function initV()
    {
        $this->v = 1;
    }

    public function testV()
    {
        $this->assertEquals($this->v, 1);
    }
}
