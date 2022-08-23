<?php

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    protected $v;

    abstract public function initV();

    public function setUp()
    {
        $this->initV();
    }
}
