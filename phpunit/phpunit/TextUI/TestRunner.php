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
 * A TestRunner for the Command Line Interface (CLI)
 * PHP SAPI Module.
 *
 * @since Class available since Release 2.0.0
 */
class PHPUnit_TextUI_TestRunner extends PHPUnit_Runner_BaseTestRunner
{
    const SUCCESS_EXIT   = 0;
    const FAILURE_EXIT   = 1;
    const EXCEPTION_EXIT = 2;

    /**
     * @var PHPUnit_Runner_TestSuiteLoader
     */
    protected $loader = null;

    /**
     * @var PHPUnit_TextUI_ResultPrinter
     */
    protected $printer = null;

    /**
     * @var bool
     */
    protected static $versionStringPrinted = false;

    /**
     * @var array
     */
    private $missingExtensions = array();

    /**
     * @var Runtime
     */
    private $runtime;

    /**
     * @param PHPUnit_Runner_TestSuiteLoader $loader
     *
     * @since Method available since Release 3.4.0
     */
    public function __construct(PHPUnit_Runner_TestSuiteLoader $loader = null)
    {
        $this->loader             = $loader;
        $this->runtime            = new SebastianBergmann_Environment_Runtime;
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param array                  $arguments
     *
     * @return PHPUnit_Framework_TestResult
     *
     * @throws PHPUnit_Framework_Exception
     */
    public static function run($test, array $arguments = array())
    {
        if ($test instanceof PHPUnit_Framework_Test) {
            $aTestRunner = new self;

            $result = $aTestRunner->doRun(
                $test,
                $arguments
            );
        } else {
            throw new PHPUnit_Framework_Exception(
                'No test case or test suite found.'
            );
        }

        if (defined('__BPC__')) {
            // just exclude else code
        } else {
            if (isset(self::$arguments['bpc'])) {
                // test-files
                PHPUnit_Util_Bpc::saveTestFiles(BPC_RUN_BEFORE_FILES, self::$arguments['bpc']);
                // Makefile
                PHPUnit_Util_Bpc::saveMakefile();

                print "\n\nThe test related files have been generated, you can run make to generate a compile test file\n";
            }
        }

        return $result;
    }

    /**
     * @return PHPUnit_Framework_TestResult
     */
    protected function createTestResult()
    {
        return new PHPUnit_Framework_TestResult;
    }

    private function processSuiteFilters(PHPUnit_Framework_TestSuite $suite, array $arguments)
    {
        if (!$arguments['filter'] &&
            empty($arguments['groups']) &&
            empty($arguments['excludeGroups'])) {
            return;
        }

        $filterFactory = new PHPUnit_Runner_Filter_Factory();

        if (!empty($arguments['excludeGroups'])) {
            $filterFactory->addFilter(
                'PHPUnit_Runner_Filter_Group_Exclude',
                $arguments['excludeGroups']
            );
        }

        if (!empty($arguments['groups'])) {
            $filterFactory->addFilter(
                'PHPUnit_Runner_Filter_Group_Include',
                $arguments['groups']
            );
        }

        if ($arguments['filter']) {
            $filterFactory->addFilter(
                'PHPUnit_Runner_Filter_Test',
                $arguments['filter']
            );
        }
        $suite->injectFilter($filterFactory);
    }

    /**
     * @param PHPUnit_Framework_Test $suite
     * @param array                  $arguments
     *
     * @return PHPUnit_Framework_TestResult
     */
    public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array())
    {
        $this->handleConfiguration($arguments);

        $this->processSuiteFilters($suite, $arguments);

        if (isset($arguments['bootstrap'])) {
            $GLOBALS['__PHPUNIT_BOOTSTRAP'] = $arguments['bootstrap'];
        }

        if ($arguments['backupGlobals'] === false) {
            $suite->setBackupGlobals(false);
        }

        if ($arguments['backupStaticAttributes'] === true) {
            $suite->setBackupStaticAttributes(true);
        }

        if ($arguments['disallowChangesToGlobalState'] === true) {
            $suite->setDisallowChangesToGlobalState(true);
        }

        if (is_integer($arguments['repeat'])) {
            $test = new PHPUnit_Extensions_RepeatedTest(
                $suite,
                $arguments['repeat'],
                $arguments['processIsolation']
            );

            $suite = new PHPUnit_Framework_TestSuite();
            $suite->addTest($test);
        }

        $result = $this->createTestResult();

        if (!$arguments['convertErrorsToExceptions']) {
            $result->convertErrorsToExceptions(false);
        }

        if (!$arguments['convertNoticesToExceptions']) {
            PHPUnit_Framework_Error_Notice::$enabled = false;
        }

        if (!$arguments['convertWarningsToExceptions']) {
            PHPUnit_Framework_Error_Warning::$enabled = false;
        }

        if ($arguments['stopOnError']) {
            $result->stopOnError(true);
        }

        if ($arguments['stopOnFailure']) {
            $result->stopOnFailure(true);
        }

        if ($arguments['stopOnIncomplete']) {
            $result->stopOnIncomplete(true);
        }

        if ($arguments['stopOnRisky']) {
            $result->stopOnRisky(true);
        }

        if ($arguments['stopOnSkipped']) {
            $result->stopOnSkipped(true);
        }

        if ($this->printer === null) {
            if (isset($arguments['printer']) &&
                $arguments['printer'] instanceof PHPUnit_Util_Printer) {
                $this->printer = $arguments['printer'];
            } else {
                $printerClass = 'PHPUnit_TextUI_ResultPrinter';

                if (isset($arguments['printer']) &&
                    is_string($arguments['printer']) &&
                    class_exists($arguments['printer'], false)) {
                    if (is_subclass_of($arguments['printer'], 'PHPUnit_TextUI_ResultPrinter')) {
                        $printerClass = $arguments['printer'];
                    }
                }

                $this->printer = new $printerClass(
                  isset($arguments['stderr']) ? 'php://stderr' : null,
                  $arguments['verbose'],
                  $arguments['colors'],
                  $arguments['debug'],
                  $arguments['columns']
                );
            }
        }

        if ($this->printer instanceof PHPUnit_TextUI_ResultPrinter) {
            $this->printer->write(
                PHPUnit_Runner_Version::getVersionString() . "\n"
            );

            self::$versionStringPrinted = true;

            if ($arguments['verbose']) {
                $this->printer->write(
                    sprintf(
                        "\nRuntime:\t%s",
                        $this->runtime->getNameWithVersion()
                    )
                );

                if ($this->runtime->hasXdebug()) {
                    $this->printer->write(
                        sprintf(
                            ' with Xdebug %s',
                            phpversion('xdebug')
                        )
                    );
                }

                if (isset($arguments['configuration'])) {
                    $this->printer->write(
                        sprintf(
                            "\nConfiguration:\t%s",
                            $arguments['configuration']->getFilename()
                        )
                    );
                }

                $this->printer->write("\n");
            }

            if (isset($arguments['deprecatedStrictModeOption'])) {
                print "Warning:\tDeprecated option \"--strict\" used\n";
            } elseif (isset($arguments['deprecatedStrictModeSetting'])) {
                print "Warning:\tDeprecated configuration setting \"strict\" used\n";
            }

            if (isset($arguments['deprecatedSeleniumConfiguration'])) {
                print "Warning:\tDeprecated configuration setting \"selenium\" used\n";
            }
        }

        foreach ($arguments['listeners'] as $listener) {
            $result->addListener($listener);
        }

        $result->addListener($this->printer);

        if (isset($arguments['testdoxHTMLFile'])) {
            $result->addListener(
                new PHPUnit_Util_TestDox_ResultPrinter_HTML(
                    $arguments['testdoxHTMLFile']
                )
            );
        }

        if (isset($arguments['testdoxTextFile'])) {
            $result->addListener(
                new PHPUnit_Util_TestDox_ResultPrinter_Text(
                    $arguments['testdoxTextFile']
                )
            );
        }

        if ($this->printer instanceof PHPUnit_TextUI_ResultPrinter) {
            $this->printer->write("\n");
        }

        $result->beStrictAboutTestsThatDoNotTestAnything($arguments['reportUselessTests']);
        $result->beStrictAboutOutputDuringTests($arguments['disallowTestOutput']);
        $result->beStrictAboutTodoAnnotatedTests($arguments['disallowTodoAnnotatedTests']);
        $result->beStrictAboutTestSize($arguments['enforceTimeLimit']);
        $result->setTimeoutForSmallTests($arguments['timeoutForSmallTests']);
        $result->setTimeoutForMediumTests($arguments['timeoutForMediumTests']);
        $result->setTimeoutForLargeTests($arguments['timeoutForLargeTests']);

        $suite->run($result);

        unset($suite);
        $result->flushListeners();

        if ($this->printer instanceof PHPUnit_TextUI_ResultPrinter) {
            $this->printer->printResult($result);
        }

        return $result;
    }

    /**
     * @param PHPUnit_TextUI_ResultPrinter $resultPrinter
     */
    public function setPrinter(PHPUnit_TextUI_ResultPrinter $resultPrinter)
    {
        $this->printer = $resultPrinter;
    }

    /**
     * Override to define how to handle a failed loading of
     * a test suite.
     *
     * @param string $message
     */
    protected function runFailed($message)
    {
        $this->write($message . PHP_EOL);
        exit(self::FAILURE_EXIT);
    }

    /**
     * @param string $buffer
     *
     * @since  Method available since Release 3.1.0
     */
    protected function write($buffer)
    {
        if (PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg') {
            $buffer = htmlspecialchars($buffer);
        }

        if ($this->printer !== null) {
            $this->printer->write($buffer);
        } else {
            print $buffer;
        }
    }

    /**
     * Returns the loader to be used.
     *
     * @return PHPUnit_Runner_TestSuiteLoader
     *
     * @since  Method available since Release 2.2.0
     */
    public function getLoader()
    {
        if (defined('__BPC__')) {
            // bpc not need loader, empty this function
        } else {
            if ($this->loader === null) {
                $this->loader = new PHPUnit_Runner_StandardTestSuiteLoader;
            }

            return $this->loader;
        }
    }

    /**
     * @param array $arguments
     *
     * @since  Method available since Release 3.2.1
     */
    protected function handleConfiguration(array &$arguments)
    {
        $arguments['debug']                              = isset($arguments['debug'])     ? $arguments['debug']     : false;
        $arguments['filter']                             = isset($arguments['filter'])    ? $arguments['filter']    : false;
        $arguments['listeners']                          = isset($arguments['listeners']) ? $arguments['listeners'] : array();
        $arguments['addUncoveredFilesFromWhitelist']     = isset($arguments['addUncoveredFilesFromWhitelist'])     ? $arguments['addUncoveredFilesFromWhitelist']     : true;
        $arguments['processUncoveredFilesFromWhitelist'] = isset($arguments['processUncoveredFilesFromWhitelist']) ? $arguments['processUncoveredFilesFromWhitelist'] : false;
        $arguments['backupGlobals']                      = isset($arguments['backupGlobals'])                      ? $arguments['backupGlobals']                      : null;
        $arguments['backupStaticAttributes']             = isset($arguments['backupStaticAttributes'])             ? $arguments['backupStaticAttributes']             : null;
        $arguments['disallowChangesToGlobalState']       = isset($arguments['disallowChangesToGlobalState'])       ? $arguments['disallowChangesToGlobalState']       : null;
        $arguments['cacheTokens']                        = isset($arguments['cacheTokens'])                        ? $arguments['cacheTokens']                        : false;
        $arguments['columns']                            = isset($arguments['columns'])                            ? $arguments['columns']                            : 80;
        $arguments['colors']                             = isset($arguments['colors'])                             ? $arguments['colors']                             : PHPUnit_TextUI_ResultPrinter::COLOR_DEFAULT;
        $arguments['convertErrorsToExceptions']          = isset($arguments['convertErrorsToExceptions'])          ? $arguments['convertErrorsToExceptions']          : true;
        $arguments['convertNoticesToExceptions']         = isset($arguments['convertNoticesToExceptions'])         ? $arguments['convertNoticesToExceptions']         : true;
        $arguments['convertWarningsToExceptions']        = isset($arguments['convertWarningsToExceptions'])        ? $arguments['convertWarningsToExceptions']        : true;
        $arguments['excludeGroups']                      = isset($arguments['excludeGroups'])                      ? $arguments['excludeGroups']                      : array();
        $arguments['groups']                             = isset($arguments['groups'])                             ? $arguments['groups']                             : array();
        $arguments['logIncompleteSkipped']               = isset($arguments['logIncompleteSkipped'])               ? $arguments['logIncompleteSkipped']               : false;
        $arguments['repeat']                             = isset($arguments['repeat'])                             ? $arguments['repeat']                             : false;
        $arguments['reportHighLowerBound']               = isset($arguments['reportHighLowerBound'])               ? $arguments['reportHighLowerBound']               : 90;
        $arguments['reportLowUpperBound']                = isset($arguments['reportLowUpperBound'])                ? $arguments['reportLowUpperBound']                : 50;
        $arguments['crap4jThreshold']                    = isset($arguments['crap4jThreshold'])                    ? $arguments['crap4jThreshold']                    : 30;
        $arguments['stopOnError']                        = isset($arguments['stopOnError'])                        ? $arguments['stopOnError']                        : false;
        $arguments['stopOnFailure']                      = isset($arguments['stopOnFailure'])                      ? $arguments['stopOnFailure']                      : false;
        $arguments['stopOnIncomplete']                   = isset($arguments['stopOnIncomplete'])                   ? $arguments['stopOnIncomplete']                   : false;
        $arguments['stopOnRisky']                        = isset($arguments['stopOnRisky'])                        ? $arguments['stopOnRisky']                        : false;
        $arguments['stopOnSkipped']                      = isset($arguments['stopOnSkipped'])                      ? $arguments['stopOnSkipped']                      : false;
        $arguments['timeoutForSmallTests']               = isset($arguments['timeoutForSmallTests'])               ? $arguments['timeoutForSmallTests']               : 1;
        $arguments['timeoutForMediumTests']              = isset($arguments['timeoutForMediumTests'])              ? $arguments['timeoutForMediumTests']              : 10;
        $arguments['timeoutForLargeTests']               = isset($arguments['timeoutForLargeTests'])               ? $arguments['timeoutForLargeTests']               : 60;
        $arguments['reportUselessTests']                 = isset($arguments['reportUselessTests'])                 ? $arguments['reportUselessTests']                 : false;
        $arguments['strictCoverage']                     = isset($arguments['strictCoverage'])                     ? $arguments['strictCoverage']                     : false;
        $arguments['disallowTestOutput']                 = isset($arguments['disallowTestOutput'])                 ? $arguments['disallowTestOutput']                 : false;
        $arguments['enforceTimeLimit']                   = isset($arguments['enforceTimeLimit'])                   ? $arguments['enforceTimeLimit']                   : false;
        $arguments['disallowTodoAnnotatedTests']         = isset($arguments['disallowTodoAnnotatedTests'])         ? $arguments['disallowTodoAnnotatedTests']         : false;
        $arguments['verbose']                            = isset($arguments['verbose'])                            ? $arguments['verbose']                            : false;
    }

    /**
     * @param $extension
     * @param string $message
     *
     * @since Method available since Release 4.7.3
     */
    private function showExtensionNotLoadedWarning($extension, $message = '')
    {
        if (isset($this->missingExtensions[$extension])) {
            return;
        }

        $this->write("Warning:\t" . 'The ' . $extension . ' extension is not loaded' . "\n");

        if (!empty($message)) {
            $this->write("\t\t" . $message . "\n");
        }

        $this->missingExtensions[$extension] = true;
    }
}
