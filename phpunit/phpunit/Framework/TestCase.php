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
 * A TestCase defines the fixture to run multiple tests.
 *
 * To define a TestCase
 *
 *   1) Implement a subclass of PHPUnit_Framework_TestCase.
 *   2) Define instance variables that store the state of the fixture.
 *   3) Initialize the fixture state by overriding setUp().
 *   4) Clean-up after a test by overriding tearDown().
 *
 * Each test runs in its own fixture so there can be no side effects
 * among test runs.
 *
 * Here is an example:
 *
 * <code>
 * <?php
 * class MathTest extends PHPUnit_Framework_TestCase
 * {
 *     public $value1;
 *     public $value2;
 *
 *     protected function setUp()
 *     {
 *         $this->value1 = 2;
 *         $this->value2 = 3;
 *     }
 * }
 * ?>
 * </code>
 *
 * For each test implement a method which interacts with the fixture.
 * Verify the expected results with assertions specified by calling
 * assert with a boolean.
 *
 * <code>
 * <?php
 * public function testPass()
 * {
 *     $this->assertTrue($this->value1 + $this->value2 == 5);
 * }
 * ?>
 * </code>
 *
 * @since Class available since Release 2.0.0
 */
abstract class PHPUnit_Framework_TestCase extends PHPUnit_Framework_Assert implements PHPUnit_Framework_Test, PHPUnit_Framework_SelfDescribing
{
    /**
     * Enable or disable the backup and restoration of the $GLOBALS array.
     * Overwrite this attribute in a child class of TestCase.
     * Setting this attribute in setUp() has no effect!
     *
     * @var bool
     */
    protected $backupGlobals = null;

    /**
     * Enable or disable the backup and restoration of static attributes.
     * Overwrite this attribute in a child class of TestCase.
     * Setting this attribute in setUp() has no effect!
     *
     * @var bool
     */
    protected $backupStaticAttributes = null;

    /**
     * Whether or not this test is to be run in a separate PHP process.
     *
     * @var bool
     */
    protected $runTestInSeparateProcess = null;

    /**
     * Whether or not this test should preserve the global state when
     * running in a separate PHP process.
     *
     * @var bool
     */
    protected $preserveGlobalState = true;

    /**
     * Whether or not this test is running in a separate PHP process.
     *
     * @var bool
     */
    private $inIsolation = false;

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var string
     */
    private $dataName = '';

    /**
     * @var bool
     */
    private $useErrorHandler = null;

    /**
     * The name of the expected Exception.
     *
     * @var mixed
     */
    private $expectedException = null;

    /**
     * The message of the expected Exception.
     *
     * @var string
     */
    private $expectedExceptionMessage = null;

    /**
     * The regex pattern to validate the expected Exception message.
     *
     * @var string
     */
    private $expectedExceptionMessageRegExp = null;

    /**
     * The code of the expected Exception.
     *
     * @var int
     */
    private $expectedExceptionCode = null;

    /**
     * The name of the test case.
     *
     * @var string
     */
    private $name = null;

    /**
     * @var array
     */
    private $dependencies = array();

    /**
     * @var array
     */
    private $dependencyInput = array();

    /**
     * @var array
     */
    private $iniSettings = array();

    /**
     * @var array
     */
    private $locale = array();

    /**
     * @var array
     */
    private $mockObjects = array();

    /**
     * @var array
     */
    private $mockObjectGenerator = null;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $statusMessage = '';

    /**
     * @var int
     */
    private $numAssertions = 0;

    /**
     * @var PHPUnit_Framework_TestResult
     */
    private $result;

    /**
     * @var mixed
     */
    private $testResult;

    /**
     * @var string
     */
    private $output = '';

    /**
     * @var string
     */
    private $outputExpectedRegex = null;

    /**
     * @var string
     */
    private $outputExpectedString = null;

    /**
     * @var mixed
     */
    private $outputCallback = false;

    /**
     * @var bool
     */
    private $outputBufferingActive = false;

    /**
     * @var int
     */
    private $outputBufferingLevel;

    /**
     * @var bool
     */
    private $disallowChangesToGlobalState = false;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        if ($name !== null) {
            $this->setName($name);
        }

        $this->data                = $data;
        $this->dataName            = $dataName;
    }

    /**
     * Returns a string representation of the test case.
     *
     * @return string
     */
    public function toString()
    {
        $buffer = sprintf(
            '%s::%s',
            get_class($this),
            $this->getName(false)
        );

        return $buffer . $this->getDataSetAsString();
    }

    /**
     * Counts the number of test cases executed by run(TestResult result).
     *
     * @return int
     */
    public function count(): int
    {
        return 1;
    }

    /**
     * Returns the annotations for this test.
     *
     * @return array
     *
     * @since Method available since Release 3.4.0
     */
    public function getAnnotations()
    {
        return PHPUnit_Util_Test::parseTestMethodAnnotations(
            get_class($this),
            $this->name
        );
    }

    /**
     * Gets the name of a TestCase.
     *
     * @param bool $withDataSet
     *
     * @return string
     */
    public function getName($withDataSet = true)
    {
        if ($withDataSet) {
            return $this->name . $this->getDataSetAsString(false);
        } else {
            return $this->name;
        }
    }

    /**
     * Returns the size of the test.
     *
     * @return int
     *
     * @since  Method available since Release 3.6.0
     */
    public function getSize()
    {
        return PHPUnit_Util_Test::getSize(
            get_class($this),
            $this->getName(false)
        );
    }

    /**
     * @return string
     *
     * @since  Method available since Release 3.6.0
     */
    public function getActualOutput()
    {
        if (!$this->outputBufferingActive) {
            return $this->output;
        } else {
            return ob_get_contents();
        }
    }

    /**
     * @return bool
     *
     * @since  Method available since Release 3.6.0
     */
    public function hasOutput()
    {
        if (strlen($this->output) === 0) {
            return false;
        }

        if ($this->hasExpectationOnOutput()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $expectedRegex
     *
     * @since Method available since Release 3.6.0
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function expectOutputRegex($expectedRegex)
    {
        if ($this->outputExpectedString !== null) {
            throw new PHPUnit_Framework_Exception;
        }

        if (is_string($expectedRegex) || is_null($expectedRegex)) {
            $this->outputExpectedRegex = $expectedRegex;
        }
    }

    /**
     * @param string $expectedString
     *
     * @since Method available since Release 3.6.0
     */
    public function expectOutputString($expectedString)
    {
        if ($this->outputExpectedRegex !== null) {
            throw new PHPUnit_Framework_Exception;
        }

        if (is_string($expectedString) || is_null($expectedString)) {
            $this->outputExpectedString = $expectedString;
        }
    }

    /**
     * @return bool
     *
     * @since Method available since Release 3.6.5
     * @deprecated
     */
    public function hasPerformedExpectationsOnOutput()
    {
        return $this->hasExpectationOnOutput();
    }

    /**
     * @return bool
     *
     * @since Method available since Release 4.3.3
     */
    public function hasExpectationOnOutput()
    {
        return is_string($this->outputExpectedString) || is_string($this->outputExpectedRegex);
    }

    /**
     * @return string
     *
     * @since  Method available since Release 3.2.0
     */
    public function getExpectedException()
    {
        return $this->expectedException;
    }

    /**
     * @param mixed  $exceptionName
     * @param string $exceptionMessage
     * @param int    $exceptionCode
     *
     * @since  Method available since Release 3.2.0
     */
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        $this->expectedException        = $exceptionName;
        $this->expectedExceptionMessage = $exceptionMessage;
        $this->expectedExceptionCode    = $exceptionCode;
    }

    /**
     * @param mixed  $exceptionName
     * @param string $exceptionMessageRegExp
     * @param int    $exceptionCode
     *
     * @since Method available since Release 4.3.0
     */
    public function setExpectedExceptionRegExp($exceptionName, $exceptionMessageRegExp = '', $exceptionCode = null)
    {
        $this->expectedException              = $exceptionName;
        $this->expectedExceptionMessageRegExp = $exceptionMessageRegExp;
        $this->expectedExceptionCode          = $exceptionCode;
    }

    public function expectException($exceptionName)
    {
        $this->expectedException = $exceptionName;
    }

    public function expectExceptionMessage($message)
    {
        $this->expectedExceptionMessage = $message;
    }

    public function expectExceptionCode($code)
    {
        $this->expectedExceptionCode = (int)$code;
    }

    public function expectExceptionMessageRegExp($regularExpression)
    {
        $this->expectedExceptionMessageRegExp = $regularExpression;
    }

    public function expectExceptionMessageMatches($regularExpression)
    {
        $this->expectedExceptionMessageRegExp = $regularExpression;
    }

    /**
     * @param bool $useErrorHandler
     *
     * @since Method available since Release 3.4.0
     */
    public function setUseErrorHandler($useErrorHandler)
    {
        $this->useErrorHandler = $useErrorHandler;
    }

    /**
     * @since Method available since Release 3.4.0
     */
    protected function setUseErrorHandlerFromAnnotation()
    {
        try {
            $useErrorHandler = PHPUnit_Util_Test::getErrorHandlerSettings(
                get_class($this),
                $this->name
            );

            if ($useErrorHandler !== null) {
                $this->setUseErrorHandler($useErrorHandler);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Returns the status of this test.
     *
     * @return int
     *
     * @since  Method available since Release 3.1.0
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the status message of this test.
     *
     * @return string
     *
     * @since  Method available since Release 3.3.0
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * Returns whether or not this test has failed.
     *
     * @return bool
     *
     * @since  Method available since Release 3.0.0
     */
    public function hasFailed()
    {
        $status = $this->getStatus();

        return $status == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE ||
               $status == PHPUnit_Runner_BaseTestRunner::STATUS_ERROR;
    }

    /**
     * Runs the test case and collects the results in a TestResult object.
     * If no TestResult object is passed a new one will be created.
     *
     * @param PHPUnit_Framework_TestResult $result
     *
     * @return PHPUnit_Framework_TestResult
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        if (!$this instanceof PHPUnit_Framework_Warning) {
            $this->setTestResultObject($result);
            $this->setUseErrorHandlerFromAnnotation();
        }

        if ($this->useErrorHandler !== null) {
            $oldErrorHandlerSetting = $result->getConvertErrorsToExceptions();
            $result->convertErrorsToExceptions($this->useErrorHandler);
        }

        if (!$this instanceof PHPUnit_Framework_Warning && !$this->handleDependencies()) {
            return;
        }

        $result->run($this);

        if ($this->useErrorHandler !== null) {
            $result->convertErrorsToExceptions($oldErrorHandlerSetting);
        }

        $this->result = null;

        return $result;
    }

    /**
     * Runs the bare test sequence.
     */
    public function runBare()
    {
        $this->numAssertions = 0;

        $this->startOutputBuffering();
        clearstatcache();
        $currentWorkingDirectory = getcwd();

        $hookMethods = PHPUnit_Util_Test::getHookMethods(get_class($this));

        try {
            if ($this->inIsolation) {
                foreach ($hookMethods['beforeClass'] as $method) {
                    $this->$method();
                }
            }

            foreach ($hookMethods['before'] as $method) {
                $this->$method();
            }

            $this->assertPreConditions();
            $this->testResult = $this->runTest();
            $this->verifyMockObjects();
            $this->assertPostConditions();

            $this->status = PHPUnit_Runner_BaseTestRunner::STATUS_PASSED;
        } catch (PHPUnit_Framework_IncompleteTest $e) {
            $this->status        = PHPUnit_Runner_BaseTestRunner::STATUS_INCOMPLETE;
            $this->statusMessage = $e->getMessage();
        } catch (PHPUnit_Framework_SkippedTest $e) {
            $this->status        = PHPUnit_Runner_BaseTestRunner::STATUS_SKIPPED;
            $this->statusMessage = $e->getMessage();
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->status        = PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE;
            $this->statusMessage = $e->getMessage();
        } catch (Throwable $_e) {
            $e = $_e;
        } catch (Exception $_e) {
            $e = $_e;
        }

        if (isset($_e)) {
            $this->status        = PHPUnit_Runner_BaseTestRunner::STATUS_ERROR;
            $this->statusMessage = $_e->getMessage();
        }

        // Clean up the mock objects.
        $this->mockObjects = array();

        // Tear down the fixture. An exception raised in tearDown() will be
        // caught and passed on when no exception was raised before.
        try {
            foreach ($hookMethods['after'] as $method) {
                $this->$method();
            }

            if ($this->inIsolation) {
                foreach ($hookMethods['afterClass'] as $method) {
                    $this->$method();
                }
            }
        } catch (Throwable $_e) {
            if (!isset($e)) {
                $e = $_e;
            }
        } catch (Exception $_e) {
            if (!isset($e)) {
                $e = $_e;
            }
        }

        try {
            $this->stopOutputBuffering();
        } catch (PHPUnit_Framework_RiskyTestError $_e) {
            if (!isset($e)) {
                $e = $_e;
            }
        }

        clearstatcache();

        if ($currentWorkingDirectory != getcwd()) {
            chdir($currentWorkingDirectory);
        }

        // Clean up INI settings.
        foreach ($this->iniSettings as $varName => $oldValue) {
            ini_set($varName, $oldValue);
        }

        $this->iniSettings = array();

        // Clean up locale settings.
        foreach ($this->locale as $category => $locale) {
            setlocale($category, $locale);
        }

        // Perform assertion on output.
        if (!isset($e)) {
            try {
                if ($this->outputExpectedRegex !== null) {
                    $this->assertRegExp($this->outputExpectedRegex, $this->output);
                } elseif ($this->outputExpectedString !== null) {
                    $this->assertEquals($this->outputExpectedString, $this->output);
                }
            } catch (Throwable $_e) {
                $e = $_e;
            } catch (Exception $_e) {
                $e = $_e;
            }
        }

        // Workaround for missing "finally".
        if (isset($e)) {
            if (!$e instanceof Exception) {
                // Rethrow Error directly on PHP 7 as onNotSuccessfulTest does not support it
                throw $e;
            }

            $this->onNotSuccessfulTest($e);
        }
    }

    /**
     * Override to run the test and assert its state.
     *
     * @return mixed
     *
     * @throws Exception|PHPUnit_Framework_Exception
     * @throws PHPUnit_Framework_Exception
     */
    protected function runTest()
    {
        if ($this->name === null) {
            throw new PHPUnit_Framework_Exception(
                'PHPUnit_Framework_TestCase::$name must not be null.'
            );
        }

        $methodName = $this->name;
        if (!method_exists($this, $methodName)) {
            $this->fail(
                sprintf(
                    'test method not exist %s::%s.',
                    get_class($this),
                    $methodName
                )
            );
        }

        try {
            $testResult = call_user_func_array(array($this, $methodName), array_values(array_merge($this->data, $this->dependencyInput)));
        } catch (Throwable $_e) {
            $e = $_e;
        } catch (Exception $_e) {
            $e = $_e;
        }

        if (isset($e)) {
            $checkException = false;

            if (!($e instanceof PHPUnit_Framework_SkippedTestError) && (
                  is_string($this->expectedException)
               || is_string($this->expectedExceptionMessage)
               || is_string($this->expectedExceptionMessageRegExp)
               || is_int($this->expectedExceptionCode)
           )) {
                $checkException = true;

                if ($e instanceof PHPUnit_Framework_Exception) {
                    $checkException = false;
                }

                if ($this->expectedException === 'PHPUnit_Framework_Exception' ||
                    $this->expectedException === '\PHPUnit_Framework_Exception' ||
                    is_subclass_of($this->expectedException, 'PHPUnit_Framework_Exception')) {
                    $checkException = true;
                }
            }

            if ($checkException) {
                if (is_string($this->expectedException)) {
                    $this->assertThat(
                        $e,
                        new PHPUnit_Framework_Constraint_Exception(
                            $this->expectedException
                        )
                    );
                }

                if (is_string($this->expectedExceptionMessage) &&
                    !empty($this->expectedExceptionMessage)) {
                    $this->assertThat(
                        $e,
                        new PHPUnit_Framework_Constraint_ExceptionMessage(
                            $this->expectedExceptionMessage
                        )
                    );
                }

                if (is_string($this->expectedExceptionMessageRegExp) &&
                    !empty($this->expectedExceptionMessageRegExp)) {
                    $this->assertThat(
                        $e,
                        new PHPUnit_Framework_Constraint_ExceptionMessageRegExp(
                            $this->expectedExceptionMessageRegExp
                        )
                    );
                }

                if ($this->expectedExceptionCode !== null) {
                    $this->assertThat(
                        $e,
                        new PHPUnit_Framework_Constraint_ExceptionCode(
                            $this->expectedExceptionCode
                        )
                    );
                }

                return;
            } else {
                throw $e;
            }
        }

        if ($this->expectedException !== null) {
            $this->assertThat(
                null,
                new PHPUnit_Framework_Constraint_Exception(
                    $this->expectedException
                )
            );
        }

        return $testResult;
    }

    /**
     * Verifies the mock object expectations.
     *
     * @since Method available since Release 3.5.0
     */
    protected function verifyMockObjects()
    {
        foreach ($this->mockObjects as $mockObject) {
            if ($mockObject->__phpunit_hasMatchers()) {
                $this->numAssertions++;
            }

            $mockObject->__phpunit_verify();
        }
    }

    /**
     * Sets the name of a TestCase.
     *
     * @param  string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the dependencies of a TestCase.
     *
     * @param array $dependencies
     *
     * @since  Method available since Release 3.4.0
     */
    public function setDependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Returns true if the tests has dependencies
     *
     * @return bool
     *
     * @since Method available since Release 4.0.0
     */
    public function hasDependencies()
    {
        return count($this->dependencies) > 0;
    }

    /**
     * Sets
     *
     * @param array $dependencyInput
     *
     * @since  Method available since Release 3.4.0
     */
    public function setDependencyInput(array $dependencyInput)
    {
        $this->dependencyInput = $dependencyInput;
    }

    /**
     * @param bool $disallowChangesToGlobalState
     *
     * @since Method available since Release 4.6.0
     */
    public function setDisallowChangesToGlobalState($disallowChangesToGlobalState)
    {
        $this->disallowChangesToGlobalState = $disallowChangesToGlobalState;
    }

    /**
     * Calling this method in setUp() has no effect!
     *
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
     * Calling this method in setUp() has no effect!
     *
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
     * @param bool $preserveGlobalState
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.4.0
     */
    public function setPreserveGlobalState($preserveGlobalState)
    {
        if (is_bool($preserveGlobalState)) {
            $this->preserveGlobalState = $preserveGlobalState;
        } else {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'boolean');
        }
    }

    /**
     * @param bool $inIsolation
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.4.0
     */
    public function setInIsolation($inIsolation)
    {
        if (is_bool($inIsolation)) {
            $this->inIsolation = $inIsolation;
        } else {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'boolean');
        }
    }

    /**
     * @return bool
     *
     * @since  Method available since Release 4.3.0
     */
    public function isInIsolation()
    {
        return $this->inIsolation;
    }

    /**
     * @return mixed
     *
     * @since  Method available since Release 3.4.0
     */
    public function getResult()
    {
        return $this->testResult;
    }

    /**
     * @param mixed $result
     *
     * @since  Method available since Release 3.4.0
     */
    public function setResult($result)
    {
        $this->testResult = $result;
    }

    /**
     * @param callable $callback
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since Method available since Release 3.6.0
     */
    public function setOutputCallback($callback)
    {
        if (!is_callable($callback)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'callback');
        }

        $this->outputCallback = $callback;
    }

    /**
     * @return PHPUnit_Framework_TestResult
     *
     * @since  Method available since Release 3.5.7
     */
    public function getTestResultObject()
    {
        return $this->result;
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     *
     * @since Method available since Release 3.6.0
     */
    public function setTestResultObject(PHPUnit_Framework_TestResult $result)
    {
        $this->result = $result;
    }

    /**
     * This method is a wrapper for the ini_set() function that automatically
     * resets the modified php.ini setting to its original value after the
     * test is run.
     *
     * @param string $varName
     * @param string $newValue
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.0.0
     */
    protected function iniSet($varName, $newValue)
    {
        if (!is_string($varName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $currentValue = ini_set($varName, $newValue);

        if ($currentValue !== false) {
            $this->iniSettings[$varName] = $currentValue;
        } else {
            throw new PHPUnit_Framework_Exception(
                sprintf(
                    'INI setting "%s" could not be set to "%s".',
                    $varName,
                    $newValue
                )
            );
        }
    }

    /**
     * This method is a wrapper for the setlocale() function that automatically
     * resets the locale to its original value after the test is run.
     *
     * @param int    $category
     * @param string $locale
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.1.0
     */
    protected function setLocale()
    {
        $args = func_get_args();

        if (count($args) < 2) {
            throw new PHPUnit_Framework_Exception;
        }

        $category = $args[0];
        $locale   = $args[1];

        $categories = array(
            LC_ALL, LC_COLLATE, LC_CTYPE, LC_MONETARY, LC_NUMERIC, LC_TIME
        );

        if (defined('LC_MESSAGES')) {
            $categories[] = LC_MESSAGES;
        }

        if (!in_array($category, $categories)) {
            throw new PHPUnit_Framework_Exception;
        }

        if (!is_array($locale) && !is_string($locale)) {
            throw new PHPUnit_Framework_Exception;
        }

        $this->locale[$category] = setlocale($category, null);

        $result = call_user_func_array('setlocale', $args);

        if ($result === false) {
            throw new PHPUnit_Framework_Exception(
                'The locale functionality is not implemented on your platform, ' .
                'the specified locale does not exist or the category name is ' .
                'invalid.'
            );
        }
    }

    protected function createMock($originalClassName)
    {
        return $this->getMockBuilder($originalClassName)
                    ->disableOriginalConstructor()
                    ->disableOriginalClone()
                    ->disableArgumentCloning()
                    ->getMock();
    }

    /**
     * Returns a mock object for the specified class.
     *
     * @param string     $originalClassName       Name of the class to mock.
     * @param array|null $methods                 When provided, only methods whose names are in the array
     *                                            are replaced with a configurable test double. The behavior
     *                                            of the other methods is not changed.
     *                                            Providing null means that no methods will be replaced.
     * @param array      $arguments               Parameters to pass to the original class' constructor.
     * @param string     $mockClassName           Class name for the generated test double class.
     * @param bool       $callOriginalConstructor Can be used to disable the call to the original class' constructor.
     * @param bool       $callOriginalClone       Can be used to disable the call to the original class' clone constructor.
     * @param bool       $callAutoload            Can be used to disable __autoload() during the generation of the test double class.
     * @param bool       $cloneArguments
     * @param bool       $callOriginalMethods
     * @param object     $proxyTarget
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.0.0
     */
    public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false, $callOriginalMethods = false, $proxyTarget = null)
    {
        $mockObject = $this->getMockObjectGenerator()->getMock(
            $originalClassName,
            $methods,
            $arguments,
            $mockClassName,
            $callOriginalConstructor,
            $callOriginalClone,
            $callAutoload,
            $cloneArguments,
            $callOriginalMethods,
            $proxyTarget
        );

        $this->mockObjects[] = $mockObject;

        return $mockObject;
    }

    /**
     * Returns a builder object to create mock objects using a fluent interface.
     *
     * @param string $className
     *
     * @return PHPUnit_Framework_MockObject_MockBuilder
     *
     * @since  Method available since Release 3.5.0
     */
    public function getMockBuilder($className)
    {
        return new PHPUnit_Framework_MockObject_MockBuilder($this, $className);
    }

    /**
     * Mocks the specified class and returns the name of the mocked class.
     *
     * @param string $originalClassName
     * @param array  $methods
     * @param array  $arguments
     * @param string $mockClassName
     * @param bool   $callOriginalConstructor
     * @param bool   $callOriginalClone
     * @param bool   $callAutoload
     * @param bool   $cloneArguments
     *
     * @return string
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.5.0
     */
    protected function getMockClass($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = false, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false)
    {
        $mock = $this->getMock(
            $originalClassName,
            $methods,
            $arguments,
            $mockClassName,
            $callOriginalConstructor,
            $callOriginalClone,
            $callAutoload,
            $cloneArguments
        );

        return get_class($mock);
    }

    /**
     * Returns a mock object for the specified abstract class with all abstract
     * methods of the class mocked. Concrete methods are not mocked by default.
     * To mock concrete methods, use the 7th parameter ($mockedMethods).
     *
     * @param string $originalClassName
     * @param array  $arguments
     * @param string $mockClassName
     * @param bool   $callOriginalConstructor
     * @param bool   $callOriginalClone
     * @param bool   $callAutoload
     * @param array  $mockedMethods
     * @param bool   $cloneArguments
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     *
     * @since  Method available since Release 3.4.0
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function getMockForAbstractClass($originalClassName, array $arguments = array(), $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $mockedMethods = array(), $cloneArguments = false)
    {
        $mockObject = $this->getMockObjectGenerator()->getMockForAbstractClass(
            $originalClassName,
            $arguments,
            $mockClassName,
            $callOriginalConstructor,
            $callOriginalClone,
            $callAutoload,
            $mockedMethods,
            $cloneArguments
        );

        $this->mockObjects[] = $mockObject;

        return $mockObject;
    }

    /**
     * Returns an object for the specified trait.
     *
     * @param string $traitName
     * @param array  $arguments
     * @param string $traitClassName
     * @param bool   $callOriginalConstructor
     * @param bool   $callOriginalClone
     * @param bool   $callAutoload
     * @param bool   $cloneArguments
     *
     * @return object
     *
     * @since  Method available since Release 3.6.0
     *
     * @throws PHPUnit_Framework_Exception
     */
    protected function getObjectForTrait($traitName, array $arguments = array(), $traitClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false)
    {
        return $this->getMockObjectGenerator()->getObjectForTrait(
            $traitName,
            $arguments,
            $traitClassName,
            $callOriginalConstructor,
            $callOriginalClone,
            $callAutoload,
            $cloneArguments
        );
    }

    /**
     * Adds a value to the assertion counter.
     *
     * @param int $count
     *
     * @since Method available since Release 3.3.3
     */
    public function addToAssertionCount($count)
    {
        $this->numAssertions += $count;
    }

    /**
     * Returns the number of assertions performed by this test.
     *
     * @return int
     *
     * @since  Method available since Release 3.3.0
     */
    public function getNumAssertions()
    {
        return $this->numAssertions;
    }

    /**
     * Returns a matcher that matches when the method is executed
     * zero or more times.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    public static function any()
    {
        return new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount;
    }

    /**
     * Returns a matcher that matches when the method is never executed.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    public static function never()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount(0);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at least N times.
     *
     * @param int $requiredInvocations
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastCount
     *
     * @since  Method available since Release 4.2.0
     */
    public static function atLeast($requiredInvocations)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastCount(
            $requiredInvocations
        );
    }

    /**
     * Returns a matcher that matches when the method is executed at least once.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce
     *
     * @since  Method available since Release 3.0.0
     */
    public static function atLeastOnce()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce;
    }

    /**
     * Returns a matcher that matches when the method is executed exactly once.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    public static function once()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount(1);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * exactly $count times.
     *
     * @param int $count
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    public static function exactly($count)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount($count);
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at most N times.
     *
     * @param int $allowedInvocations
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtMostCount
     *
     * @since  Method available since Release 4.2.0
     */
    public static function atMost($allowedInvocations)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtMostCount(
            $allowedInvocations
        );
    }

    /**
     * Returns a matcher that matches when the method is executed
     * at the given index.
     *
     * @param int $index
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtIndex
     *
     * @since  Method available since Release 3.0.0
     */
    public static function at($index)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtIndex($index);
    }

    /**
     * @param mixed $value
     *
     * @return PHPUnit_Framework_MockObject_Stub_Return
     *
     * @since  Method available since Release 3.0.0
     */
    public static function returnValue($value)
    {
        return new PHPUnit_Framework_MockObject_Stub_Return($value);
    }

    /**
     * @param array $valueMap
     *
     * @return PHPUnit_Framework_MockObject_Stub_ReturnValueMap
     *
     * @since  Method available since Release 3.6.0
     */
    public static function returnValueMap(array $valueMap)
    {
        return new PHPUnit_Framework_MockObject_Stub_ReturnValueMap($valueMap);
    }

    /**
     * @param int $argumentIndex
     *
     * @return PHPUnit_Framework_MockObject_Stub_ReturnArgument
     *
     * @since  Method available since Release 3.3.0
     */
    public static function returnArgument($argumentIndex)
    {
        return new PHPUnit_Framework_MockObject_Stub_ReturnArgument(
            $argumentIndex
        );
    }

    /**
     * @param mixed $callback
     *
     * @return PHPUnit_Framework_MockObject_Stub_ReturnCallback
     *
     * @since  Method available since Release 3.3.0
     */
    public static function returnCallback($callback)
    {
        return new PHPUnit_Framework_MockObject_Stub_ReturnCallback($callback);
    }

    /**
     * Returns the current object.
     *
     * This method is useful when mocking a fluent interface.
     *
     * @return PHPUnit_Framework_MockObject_Stub_ReturnSelf
     *
     * @since  Method available since Release 3.6.0
     */
    public static function returnSelf()
    {
        return new PHPUnit_Framework_MockObject_Stub_ReturnSelf();
    }

    /**
     * @param Exception $exception
     *
     * @return PHPUnit_Framework_MockObject_Stub_Exception
     *
     * @since  Method available since Release 3.1.0
     */
    public static function throwException(Exception $exception)
    {
        return new PHPUnit_Framework_MockObject_Stub_Exception($exception);
    }

    /**
     * @param mixed $value, ...
     *
     * @return PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls
     *
     * @since  Method available since Release 3.0.0
     */
    public static function onConsecutiveCalls()
    {
        $args = func_get_args();

        return new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($args);
    }

    /**
     * Gets the data set description of a TestCase.
     *
     * @param bool $includeData
     *
     * @return string
     *
     * @since  Method available since Release 3.3.0
     */
    protected function getDataSetAsString($includeData = true)
    {
        $buffer = '';

        if (!empty($this->data)) {
            if (is_int($this->dataName)) {
                $buffer .= sprintf(' with data set #%d', $this->dataName);
            } else {
                $buffer .= sprintf(' with data set "%s"', $this->dataName);
            }

            $exporter = new SebastianBergmann_Exporter_Exporter;

            if ($includeData) {
                $buffer .= sprintf(' (%s)', $exporter->shortenedRecursiveExport($this->data));
            }
        }

        return $buffer;
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
     * @since Method available since Release 3.5.4
     */
    protected function handleDependencies()
    {
        if (!empty($this->dependencies) && !$this->inIsolation) {
            $className  = get_class($this);
            $passed     = $this->result->passed();
            $passedKeys = array_keys($passed);
            $numKeys    = count($passedKeys);

            for ($i = 0; $i < $numKeys; $i++) {
                $pos = strpos($passedKeys[$i], ' with data set');

                if ($pos !== false) {
                    $passedKeys[$i] = substr($passedKeys[$i], 0, $pos);
                }
            }

            $passedKeys = array_flip(array_unique($passedKeys));

            foreach ($this->dependencies as $dependency) {
                if (strpos($dependency, '::') === false) {
                    $dependency = $className . '::' . $dependency;
                }

                if (!isset($passedKeys[$dependency])) {
                    $this->result->addError(
                        $this,
                        new PHPUnit_Framework_SkippedTestError(
                            sprintf(
                                'This test depends on "%s" to pass.',
                                $dependency
                            )
                        ),
                        0
                    );

                    return false;
                }

                if (isset($passed[$dependency])) {
                    if ($passed[$dependency]['size'] != PHPUnit_Util_Test::UNKNOWN &&
                        $this->getSize() != PHPUnit_Util_Test::UNKNOWN &&
                        $passed[$dependency]['size'] > $this->getSize()) {
                        $this->result->addError(
                            $this,
                            new PHPUnit_Framework_SkippedTestError(
                                'This test depends on a test that is larger than itself.'
                            ),
                            0
                        );

                        return false;
                    }

                    $this->dependencyInput[$dependency] = $passed[$dependency]['result'];
                } else {
                    $this->dependencyInput[$dependency] = null;
                }
            }
        }

        return true;
    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called before the execution of a test starts
     * and after setUp() is called.
     *
     * @since  Method available since Release 3.2.8
     */
    protected function assertPreConditions()
    {
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called before the execution of a test ends
     * and before tearDown() is called.
     *
     * @since  Method available since Release 3.2.8
     */
    protected function assertPostConditions()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass()
    {
    }

    /**
     * This method is called when a test method did not execute successfully.
     *
     * @param Exception $e
     *
     * @since Method available since Release 3.4.0
     *
     * @throws Exception
     */
    protected function onNotSuccessfulTest(Exception $e)
    {
        throw $e;
    }

    /**
     * Performs custom preparations on the process isolation template.
     *
     * @param Text_Template $template
     *
     * @since Method available since Release 3.4.0
     */
    protected function prepareTemplate(Text_Template $template)
    {
    }

    /**
     * Get the mock object generator, creating it if it doesn't exist.
     *
     * @return PHPUnit_Framework_MockObject_Generator
     */
    protected function getMockObjectGenerator()
    {
        if (null === $this->mockObjectGenerator) {
            $this->mockObjectGenerator = new PHPUnit_Framework_MockObject_Generator;
        }

        return $this->mockObjectGenerator;
    }

    /**
     * @since Method available since Release 4.2.0
     */
    private function startOutputBuffering()
    {
        while (!defined('PHPUNIT_TESTSUITE') && ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();

        $this->outputBufferingActive = true;
        $this->outputBufferingLevel  = ob_get_level();
    }

    /**
     * @since Method available since Release 4.2.0
     */
    private function stopOutputBuffering()
    {
        if (ob_get_level() != $this->outputBufferingLevel) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw new PHPUnit_Framework_RiskyTestError(
                'Test code or tested code did not (only) close its own output buffers'
            );
        }

        $output = ob_get_contents();

        if ($this->outputCallback === false) {
            $this->output = $output;
        } else {
            $this->output = call_user_func_array(
                $this->outputCallback,
                array($output)
            );
        }

        ob_end_clean();

        $this->outputBufferingActive = false;
        $this->outputBufferingLevel  = ob_get_level();
    }
}
