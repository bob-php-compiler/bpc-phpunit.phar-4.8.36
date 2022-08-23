<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base class for all test runners.
 *
 * @since Class available since Release 2.0.0
 */
abstract class PHPUnit_Runner_BaseTestRunner
{
    const STATUS_PASSED     = 0;
    const STATUS_SKIPPED    = 1;
    const STATUS_INCOMPLETE = 2;
    const STATUS_FAILURE    = 3;
    const STATUS_ERROR      = 4;
    const STATUS_RISKY      = 5;
    const SUITE_METHODNAME  = 'suite';

    /**
     * Returns the loader to be used.
     *
     * @return PHPUnit_Runner_TestSuiteLoader
     */
    public function getLoader()
    {
        if (defined('__BPC__')) {
            // bpc not need loader, empty this function
        } else {
            return new PHPUnit_Runner_StandardTestSuiteLoader;
        }
    }

    /**
     * Returns the Test corresponding to the given suite.
     * This is a template method, subclasses override
     * the runFailed() and clearStatus() methods.
     *
     * @param string $suiteClassName
     * @param string $suiteClassFile
     * @param mixed  $suffixes
     *
     * @return PHPUnit_Framework_Test
     */
    public function getTest($suiteClassName, $suiteClassFile = '', $suffixes = '')
    {
        // phpunit.php
        // 1. 如果以 phpunit tests-dir 方式运行
        //      $suiteClassName = tests-dir
        //      $suiteClassFile = 空
        // 2. 如果以 phpunit TestSuiteFile.php 方式运行
        //      $suiteClassName = TestSuiteFile
        //      $suiteClassFile = /path/to/TestSuiteFile.php
        // 3. 如果以 phpunit tests-dir TestSuiteFile.php 方式运行
        //      $suiteClassFile = tests-dir
        //      $suiteClassFile = /path/to/TestSuiteFile.php
        // 对于1来说,就是 $suite->addTestFiles($files)
        // 对于2,3来说,loadSuiteClass()返回了$testClass
        //
        // run-test.php
        //  只有当第1个或第2个参数以.php结尾时,
        //      $suiteClassName = path/to/TestSuiteFile.php
        //      $suiteClassFile = 空
        //  其它情况下
        //      $suiteClassName = BPC_TEST_LOAD_ALL
        //      $suiteClassFile = 空
        if (defined('TESTCASE_LIST')) {
            $suite = new PHPUnit_Framework_TestSuite($suiteClassName);
            try {
                if ($suiteClassName == 'BPC_TEST_LOAD_ALL') {
                    foreach (TESTCASE_LIST as $className => $filename) {
                        include_once RUN_ROOT_DIR . '/' . $filename;
                        $suite->addTestSuite($className);
                    }
                } else {
                    $className = array_search($suiteClassName, TESTCASE_LIST, true);
                    if ($className) {
                        include_once RUN_ROOT_DIR . '/' . $suiteClassName;
                        $suite->addTestSuite($className);
                    }
                }
                return $suite;
            } catch (PHPUnit_Framework_Exception $e) {
                $this->runFailed($e->getMessage());
                return;
            }
        } else {
        if (defined('__BPC__')) {
            // just exclude else code
        } else {
        if (is_dir($suiteClassName) &&
            !is_file($suiteClassName . '.php') && empty($suiteClassFile)) {
            $facade = new File_Iterator_Facade;
            $files  = $facade->getFilesAsArray(
                $suiteClassName,
                $suffixes
            );

            $suite = new PHPUnit_Framework_TestSuite($suiteClassName);
            $suite->addTestFiles($files);

            return $suite;
        }

        try {
            $testClass = $this->loadSuiteClass(
                $suiteClassName,
                $suiteClassFile
            );
        } catch (PHPUnit_Framework_Exception $e) {
            $this->runFailed($e->getMessage());

            return;
        }

        try {
            $suiteMethod = $testClass->getMethod(self::SUITE_METHODNAME);

            if (!$suiteMethod->isStatic()) {
                $this->runFailed(
                    'suite() method must be static.'
                );

                return;
            }

            try {
                $test = $suiteMethod->invoke(null, $testClass->getName());
            } catch (ReflectionException $e) {
                $this->runFailed(
                    sprintf(
                        "Failed to invoke suite() method.\n%s",
                        $e->getMessage()
                    )
                );

                return;
            }
        } catch (ReflectionException $e) {
            try {
                $test = new PHPUnit_Framework_TestSuite($testClass->getName());
            } catch (PHPUnit_Framework_Exception $e) {
                $test = new PHPUnit_Framework_TestSuite;
                $test->setName($suiteClassName);
            }
        }

        $this->clearStatus();

        return $test;
        }
        }
    }

    /**
     * Returns the loaded ReflectionClass for a suite name.
     *
     * @param string $suiteClassName
     * @param string $suiteClassFile
     *
     * @return ReflectionClass
     */
    protected function loadSuiteClass($suiteClassName, $suiteClassFile = '')
    {
        $loader = $this->getLoader();

        return $loader->load($suiteClassName, $suiteClassFile);
    }

    /**
     * Clears the status message.
     */
    protected function clearStatus()
    {
    }

    /**
     * Override to define how to handle a failed loading of
     * a test suite.
     *
     * @param string $message
     */
    abstract protected function runFailed($message);
}
