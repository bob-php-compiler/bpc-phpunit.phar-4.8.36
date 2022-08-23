<?php

require_once __DIR__ . '/BaseTest.php';

class ExtendsTest extends BaseTest
{
    public function initV()
    {
        $this->v = 'V';
    }

    public function testV()
    {
        $this->assertEquals($this->v, 'V');
    }
}
