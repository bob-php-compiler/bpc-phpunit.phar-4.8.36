<?php

require_once __DIR__ . '/Suite_1_Test.php';
require_once __DIR__ . '/Suite_2_Test.php';

class SuiteTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Suite Tests');
        $suite->addTestSuite('Suite_1_Test');
        $suite->addTestSuite('Suite_2_Test');
        return $suite;
    }
}
