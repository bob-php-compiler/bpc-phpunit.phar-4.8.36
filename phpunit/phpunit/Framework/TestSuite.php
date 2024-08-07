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
 * A TestSuite is a composite of Tests. It runs a collection of test cases.
 *
 * @since Class available since Release 2.0.0
 */
class PHPUnit_Framework_TestSuite implements PHPUnit_Framework_Test, PHPUnit_Framework_SelfDescribing, IteratorAggregate
{
    /**
     * Last count of tests in this suite.
     *
     * @var int|null
     */
    private $cachedNumTests;

    /**
     * Enable or disable the backup and restoration of the $GLOBALS array.
     *
     * @var bool
     */
    protected $backupGlobals = null;

    /**
     * Enable or disable the backup and restoration of static attributes.
     *
     * @var bool
     */
    protected $backupStaticAttributes = null;

    /**
     * @var bool
     */
    private $disallowChangesToGlobalState = null;

    /**
     * @var bool
     */
    protected $runTestInSeparateProcess = false;

    /**
     * The name of the test suite.
     *
     * @var string
     */
    protected $name = '';

    /**
     * The test groups of the test suite.
     *
     * @var array
     */
    protected $groups = array();

    /**
     * The tests in the test suite.
     *
     * @var array
     */
    protected $tests = array();

    /**
     * The number of tests in the test suite.
     *
     * @var int
     */
    protected $numTests = -1;

    /**
     * @var bool
     */
    protected $testCase = false;

    /**
     * @var array
     */
    protected $foundClasses = array();

    /**
     * @var PHPUnit_Runner_Filter_Factory
     */
    private $iteratorFilter = null;

    /**
     * Constructs a new TestSuite:
     *
     *   - PHPUnit_Framework_TestSuite() constructs an empty TestSuite.
     *
     *   - PHPUnit_Framework_TestSuite(string) constructs a
     *     TestSuite from the given class.
     *
     *   - PHPUnit_Framework_TestSuite(string, String)
     *     constructs a TestSuite from the given class with the given
     *     name.
     *
     *   - PHPUnit_Framework_TestSuite(String) either constructs a
     *     TestSuite from the given class (if the passed string is the
     *     name of an existing class) or constructs an empty TestSuite
     *     with the given name.
     *
     * @param mixed  $theClass
     * @param string $name
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function __construct($theClass = '', $name = '')
    {
        $argumentsValid = false;

        if (is_string($theClass) &&
            $theClass !== '' &&
            class_exists($theClass, false)) {
            $argumentsValid = true;

            if ($name == '') {
                $name = $theClass;
            }
        } elseif (is_string($theClass)) {
            $this->setName($theClass);

            return;
        }

        if (!$argumentsValid) {
            throw new PHPUnit_Framework_Exception;
        }

        if (!is_subclass_of($theClass, 'PHPUnit_Framework_TestCase')) {
            throw new PHPUnit_Framework_Exception(
                'Class "' . $theClass . '" does not extend PHPUnit_Framework_TestCase.'
            );
        }

        $this->setName($name);

        $methods = get_class_methods($theClass);
        foreach ($methods as $method) {
            $this->addTestMethod($theClass, $method);
        }

        if (empty($this->tests)) {
            $this->addTest(
                self::warning(
                    sprintf(
                        'No tests found in class "%s".',
                        $name
                    )
                )
            );
        }

        $this->testCase = true;
    }

    /**
     * Returns a string representation of the test suite.
     *
     * @return string
     */
    public function toString()
    {
        return $this->getName();
    }

    /**
     * Adds a test to the suite.
     *
     * @param PHPUnit_Framework_Test $test
     * @param array                  $groups
     */
    public function addTest(PHPUnit_Framework_Test $test, $groups = array())
    {
        $this->tests[]  = $test;
        $this->numTests = -1;

        if ($test instanceof self &&
            empty($groups)) {
            $groups = $test->getGroups();
        }

        if (empty($groups)) {
            $groups = array('default');
        }

        foreach ($groups as $group) {
            if (!isset($this->groups[$group])) {
                $this->groups[$group] = array($test);
            } else {
                $this->groups[$group][] = $test;
            }
        }
    }

    /**
     * Adds the tests from the given class to the suite.
     *
     * @param mixed $testClass
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function addTestSuite($testClass)
    {
        if (!is_string($testClass)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'class name'
            );
        }

        if ($testClass instanceof self) {
            $this->addTest($testClass);
        } else {
            $suiteMethod = false;

            if (method_exists($testClass, PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME)) {
                $oldErrorHandler = set_error_handler(
                    array('PHPUnit_Util_ErrorHandler', 'handleError')
                );
                try {
                    $suiteMethodName = PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME;
                    $this->addTest($testClass::$suiteMethodName());
                } catch (PHPUnit_Framework_Error_Deprecated $e) {
                    restore_error_handler();
                    if (substr($e->getMessage(), 0, 17) == 'Non-static method') {
                        throw new PHPUnit_Framework_Exception(
                            'suite() method must be static.'
                        );
                    }
                }

                $suiteMethod = true;
            }

            if (!$suiteMethod) {
                $this->addTest(new self($testClass));
            }
        }
    }

    /**
     * Wraps both <code>addTest()</code> and <code>addTestSuite</code>
     * as well as the separate import statements for the user's convenience.
     *
     * If the named file cannot be read or there are no new tests that can be
     * added, a <code>PHPUnit_Framework_Warning</code> will be created instead,
     * leaving the current test run untouched.
     *
     * @param string $filename
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 2.3.0
     */
    public function addTestFile($filename)
    {
        if (defined('__BPC__')) {
            // just exclude else code
        } else {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        // The given file may contain further stub classes in addition to the
        // test class itself. Figure out the actual test class.
        $classes    = get_declared_classes();
        $filename   = PHPUnit_Util_Fileloader::checkAndLoad($filename);
        $newClasses = array_diff(get_declared_classes(), $classes);

        // The diff is empty in case a parent class (with test methods) is added
        // AFTER a child class that inherited from it. To account for that case,
        // cumulate all discovered classes, so the parent class may be found in
        // a later invocation.
        if ($newClasses) {
            // On the assumption that test classes are defined first in files,
            // process discovered classes in approximate LIFO order, so as to
            // avoid unnecessary reflection.
            $this->foundClasses = array_merge($newClasses, $this->foundClasses);
        }

        // The test class's name must match the filename, either in full, or as
        // a PEAR/PSR-0 prefixed shortname ('NameSpace_ShortName'), or as a
        // PSR-1 local shortname ('NameSpace\ShortName'). The comparison must be
        // anchored to prevent false-positive matches (e.g., 'OtherShortName').
        $shortname      = basename($filename, '.php');
        $shortnameRegEx = '/(?:^|_|\\\\)' . preg_quote($shortname, '/') . '$/';

        foreach ($this->foundClasses as $i => $className) {
            if (preg_match($shortnameRegEx, $className)) {
                $class = new ReflectionClass($className);

                if ($class->getFileName() == $filename) {
                    $newClasses = array($className);
                    unset($this->foundClasses[$i]);
                    break;
                }
            }
        }

        foreach ($newClasses as $className) {
            $class = new ReflectionClass($className);

            if (!$class->isAbstract()) {
                if ($class->hasMethod(PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME)) {
                    $method = $class->getMethod(
                        PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME
                    );

                    if ($method->isStatic()) {
                        $this->addTest($method->invoke(null, $className));
                    }
                } elseif ($class->implementsInterface('PHPUnit_Framework_Test')) {
                    $this->addTestSuite($class->getName());
                }
            }
        }

        $this->numTests = -1;
    }
    }

    /**
     * Wrapper for addTestFile() that adds multiple test files.
     *
     * @param array|Iterator $filenames
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 2.3.0
     */
    public function addTestFiles($filenames)
    {
        if (!(is_array($filenames) ||
             (is_object($filenames) && $filenames instanceof Iterator))) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array or iterator'
            );
        }

        foreach ($filenames as $filename) {
            $this->addTestFile((string) $filename);
        }
    }

    /**
     * Counts the number of test cases that will be run by this test.
     *
     * @param bool $preferCache Indicates if cache is preferred.
     *
     * @return int
     */
    public function count($preferCache = false): int
    {
        if ($preferCache && $this->cachedNumTests != null) {
            $numTests = $this->cachedNumTests;
        } else {
            $numTests = 0;
            foreach ($this as $test) {
                $numTests += count($test);
            }
            $this->cachedNumTests = $numTests;
        }

        return $numTests;
    }

    /**
     * @param string $theClass
     * @param string $method
     *
     * @return PHPUnit_Framework_Test
     *
     * @throws PHPUnit_Framework_Exception
     */
    public static function createTest($theClass, $method)
    {
        $preserveGlobalState = PHPUnit_Util_Test::getPreserveGlobalStateSettings(
            $theClass,
            $method
        );

        try {
            $data = PHPUnit_Util_Test::getProvidedData(
                $theClass,
                $method
            );
        } catch (PHPUnit_Framework_IncompleteTestError $e) {
            $message = sprintf(
                'Test for %s::%s marked incomplete by data provider',
                $theClass,
                $method
            );

            $_message = $e->getMessage();

            if (!empty($_message)) {
                $message .= "\n" . $_message;
            }

            $data = self::incompleteTest($theClass, $method, $message);
        } catch (PHPUnit_Framework_SkippedTestError $e) {
            $message = sprintf(
                'Test for %s::%s skipped by data provider',
                $theClass,
                $method
            );

            $_message = $e->getMessage();

            if (!empty($_message)) {
                $message .= "\n" . $_message;
            }

            $data = self::skipTest($theClass, $method, $message);
        } catch (Throwable $_t) {
            $t = $_t;
        } catch (Exception $_t) {
            $t = $_t;
        }

        if (isset($t)) {
            $message = sprintf(
                'The data provider specified for %s::%s is invalid.',
                $theClass,
                $method
            );

            $_message = $t->getMessage();

            if (!empty($_message)) {
                $message .= "\n" . $_message;
            }

            $data = self::warning($message);
        }

        // Test method with @dataProvider.
        if (isset($data)) {
            $test = new PHPUnit_Framework_TestSuite_DataProvider(
                $theClass . '::' . $method
            );

            if (empty($data)) {
                $data = self::warning(
                    sprintf(
                        'No tests found in suite "%s".',
                        $test->getName()
                    )
                );
            }

            $groups = PHPUnit_Util_Test::getGroups($theClass, $method);

            if ($data instanceof PHPUnit_Framework_Warning ||
                $data instanceof PHPUnit_Framework_SkippedTestCase ||
                $data instanceof PHPUnit_Framework_IncompleteTestCase) {
                $test->addTest($data, $groups);
            } else {
                foreach ($data as $_dataName => $_data) {
                    $_test = new $theClass($method, $_data, $_dataName);

                    $test->addTest($_test, $groups);
                }
            }
        } else {
            $test = new $theClass;
        }

        if (!isset($test)) {
            throw new PHPUnit_Framework_Exception('No valid test provided.');
        }

        if ($test instanceof PHPUnit_Framework_TestCase) {
            $test->setName($method);
        }

        return $test;
    }

    /**
     * Creates a default TestResult object.
     *
     * @return PHPUnit_Framework_TestResult
     */
    protected function createResult()
    {
        return new PHPUnit_Framework_TestResult;
    }

    /**
     * Returns the name of the suite.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the test groups of the suite.
     *
     * @return array
     *
     * @since  Method available since Release 3.2.0
     */
    public function getGroups()
    {
        return array_keys($this->groups);
    }

    public function getGroupDetails()
    {
        return $this->groups;
    }

    /**
     * Set tests groups of the test case
     *
     * @param array $groups
     *
     * @since Method available since Release 4.0.0
     */
    public function setGroupDetails(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param PHPUnit_Framework_TestResult $result
     *
     * @return PHPUnit_Framework_TestResult
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        if (count($this) == 0) {
            return $result;
        }

        $hookMethods = PHPUnit_Util_Test::getHookMethods($this->name);

        $result->startTestSuite($this);

        try {
            $this->setUp();

            foreach ($hookMethods['beforeClass'] as $beforeClassMethod) {
                if ($this->testCase === true &&
                    class_exists($this->name, false) &&
                    method_exists($this->name, $beforeClassMethod)) {

                    call_user_func(array($this->name, $beforeClassMethod));
                }
            }
        } catch (PHPUnit_Framework_SkippedTestSuiteError $e) {
            $numTests = count($this);

            for ($i = 0; $i < $numTests; $i++) {
                $result->startTest($this);
                $result->addFailure($this, $e, 0);
                $result->endTest($this, 0);
            }

            $this->tearDown();
            $result->endTestSuite($this);

            return $result;
        } catch (Throwable $_t) {
            $t = $_t;
        } catch (Exception $_t) {
            $t = $_t;
        }

        if (isset($t)) {
            $numTests = count($this);

            for ($i = 0; $i < $numTests; $i++) {
                $result->startTest($this);
                $result->addError($this, $t, 0);
                $result->endTest($this, 0);
            }

            $this->tearDown();
            $result->endTestSuite($this);

            return $result;
        }

        foreach ($this as $test) {
            if ($result->shouldStop()) {
                break;
            }

            if ($test instanceof PHPUnit_Framework_TestCase ||
                $test instanceof self) {
                $test->setDisallowChangesToGlobalState($this->disallowChangesToGlobalState);
                $test->setBackupGlobals($this->backupGlobals);
                $test->setBackupStaticAttributes($this->backupStaticAttributes);
            }

            $test->run($result);
        }

        foreach ($hookMethods['afterClass'] as $afterClassMethod) {
            if ($this->testCase === true && class_exists($this->name, false) && method_exists($this->name, $afterClassMethod)) {
                call_user_func(array($this->name, $afterClassMethod));
            }
        }

        $this->tearDown();

        $result->endTestSuite($this);

        return $result;
    }

    /**
     * Runs a test.
     *
     * @deprecated
     *
     * @param PHPUnit_Framework_Test       $test
     * @param PHPUnit_Framework_TestResult $result
     */
    public function runTest(PHPUnit_Framework_Test $test, PHPUnit_Framework_TestResult $result)
    {
        $test->run($result);
    }

    /**
     * Sets the name of the suite.
     *
     * @param  string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the test at the given index.
     *
     * @param  int
     *
     * @return PHPUnit_Framework_Test
     */
    public function testAt($index)
    {
        if (isset($this->tests[$index])) {
            return $this->tests[$index];
        } else {
            return false;
        }
    }

    /**
     * Returns the tests as an enumeration.
     *
     * @return array
     */
    public function tests()
    {
        return $this->tests;
    }

    /**
     * Set tests of the test suite
     *
     * @param array $tests
     *
     * @since Method available since Release 4.0.0
     */
    public function setTests(array $tests)
    {
        $this->tests = $tests;
    }

    /**
     * Mark the test suite as skipped.
     *
     * @param string $message
     *
     * @throws PHPUnit_Framework_SkippedTestSuiteError
     *
     * @since  Method available since Release 3.0.0
     */
    public function markTestSuiteSkipped($message = '')
    {
        throw new PHPUnit_Framework_SkippedTestSuiteError($message);
    }

    /**
     * @param string  $class
     * @param string $method
     */
    protected function addTestMethod($class, $method)
    {
        if (!$this->isTestMethod($method)) {
            return;
        }

        $test = self::createTest($class, $method);

        if ($test instanceof PHPUnit_Framework_TestCase ||
            $test instanceof PHPUnit_Framework_TestSuite_DataProvider) {
            $test->setDependencies(
                PHPUnit_Util_Test::getDependencies($class, $method)
            );
        }

        $this->addTest(
            $test,
            PHPUnit_Util_Test::getGroups($class, $method)
        );
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public static function isTestMethod($method)
    {
        if (strpos($method, 'test') === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $message
     *
     * @return PHPUnit_Framework_Warning
     */
    protected static function warning($message)
    {
        return new PHPUnit_Framework_Warning($message);
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param string $message
     *
     * @return PHPUnit_Framework_SkippedTestCase
     *
     * @since  Method available since Release 4.3.0
     */
    protected static function skipTest($class, $methodName, $message)
    {
        return new PHPUnit_Framework_SkippedTestCase($class, $methodName, $message);
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param string $message
     *
     * @return PHPUnit_Framework_IncompleteTestCase
     *
     * @since  Method available since Release 4.3.0
     */
    protected static function incompleteTest($class, $methodName, $message)
    {
        return new PHPUnit_Framework_IncompleteTestCase($class, $methodName, $message);
    }

    /**
     * @param bool $disallowChangesToGlobalState
     *
     * @since  Method available since Release 4.6.0
     */
    public function setDisallowChangesToGlobalState($disallowChangesToGlobalState)
    {
        if (is_null($this->disallowChangesToGlobalState) && is_bool($disallowChangesToGlobalState)) {
            $this->disallowChangesToGlobalState = $disallowChangesToGlobalState;
        }
    }

    /**
     * @param bool $backupGlobals
     *
     * @since  Method available since Release 3.3.0
     */
    public function setBackupGlobals($backupGlobals)
    {
        if (is_null($this->backupGlobals) && is_bool($backupGlobals)) {
            $this->backupGlobals = $backupGlobals;
        }
    }

    /**
     * @param bool $backupStaticAttributes
     *
     * @since  Method available since Release 3.4.0
     */
    public function setBackupStaticAttributes($backupStaticAttributes)
    {
        if (is_null($this->backupStaticAttributes) &&
            is_bool($backupStaticAttributes)) {
            $this->backupStaticAttributes = $backupStaticAttributes;
        }
    }

    /**
     * Returns an iterator for this test suite.
     *
     * @return RecursiveIteratorIterator
     *
     * @since  Method available since Release 3.1.0
     */
    public function getIterator(): Traversable
    {
        $iterator = new PHPUnit_Util_TestSuiteIterator($this);

        if ($this->iteratorFilter !== null) {
            $iterator = $this->iteratorFilter->factory($iterator, $this);
        }

        return $iterator;
    }

    public function injectFilter(PHPUnit_Runner_Filter_Factory $filter)
    {
        $this->iteratorFilter = $filter;
        foreach ($this as $test) {
            if ($test instanceof self) {
                $test->injectFilter($filter);
            }
        }
    }

    /**
     * Template Method that is called before the tests
     * of this test suite are run.
     *
     * @since  Method available since Release 3.1.0
     */
    protected function setUp()
    {
    }

    /**
     * Template Method that is called after the tests
     * of this test suite have finished running.
     *
     * @since  Method available since Release 3.1.0
     */
    protected function tearDown()
    {
    }
}
