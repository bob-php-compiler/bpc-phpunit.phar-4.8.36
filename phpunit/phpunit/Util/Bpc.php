<?php

class PHPUnit_Util_Bpc implements PHPUnit_Framework_TestListener
{
    protected $testClass = '';
    protected $testMap = array();
    protected $testParentMap = array();

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {}

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {}

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {}

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {}

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {}

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {}

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {}

    public function startTest(PHPUnit_Framework_Test $test)
    {
        if (!($test instanceof PHPUnit_Framework_TestCase)) {
            return;
        }

        $class = get_class($test);
        if ($class == 'PHPUnit_Framework_Warning') {
            return;
        }

        if ($this->testClass == $class) {
            return;
        }

        $this->testClass = $class;
        $r = new ReflectionClass($class);
        $this->testMap[$class] = $r->getFileName();

        // @see phpunit-test-extends
        // 当TestCase之间有继承关系时,父类也要能保存到最终的test-files里,不然编译时找不到父类
        $cwd = getcwd();
        while (true) {
            $parentR = $r->getParentClass();
            if (!$parentR) {
                break;
            }
            $parentPath = $parentR->getFileName();
            if (strpos($parentPath, $cwd) !== 0) {
                break;
            }
            $this->testParentMap[$parentR->getName()] = $parentPath;
            $r = $parentR;
        }
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {}

    public function generateFiles($runBeforeFiles, $dirPaths)
    {
        $this->generateEntryFile();
        $this->generateTestFiles($runBeforeFiles, $dirPaths);
        $this->generateMakefile();
    }

    protected function generateEntryFile()
    {
        $cwd = getcwd();
        $mapFile = $cwd . '/TESTCASE_LIST.php';
        if (file_exists($mapFile)) {
            $map = include $mapFile;
            $mapFileIncluded = true;
        } else {
            $map = array();
            $mapFileIncluded = false;
        }

        $prefixLen = strlen($cwd) + 1;
        foreach ($this->testMap as $className => $filename) {
            $map[$className] = substr($filename, $prefixLen);
        }

        // @see phpunit-test-suite/SuiteTests.php
        // 由于我们只记录了最终运行的TestCase,要想以suite的方式运行,必须将Suite也加到TESTCASE_LIST里
        $extraMapFile = $cwd . '/TESTCASE_LIST_EXTRA.php';
        if (file_exists($extraMapFile)) {
            $map += include $extraMapFile;
        }

        file_put_contents(
            $mapFile,
            '<?php return ' . var_export($map, true) . ';'
        );
        if (!$mapFileIncluded) {
            include $mapFile;
        }

        file_put_contents(
            $cwd . '/run-test.php',
            "<?php
define('RUN_ROOT_DIR', __DIR__);
define('TESTCASE_LIST', include __DIR__ . '/TESTCASE_LIST.php');
include 'phpunit/loader.php';
PHPUnit_TextUI_Command::main();
"
        );
    }

    public function generateTestFiles($runBeforeFiles, $dirPaths)
    {
        $includeFiles    = array_diff(get_included_files(), $runBeforeFiles);
        $testClassFiles  = array_flip($this->testMap);
        $testParentFiles = array_flip($this->testParentMap);
        $files           = array();
        foreach ($includeFiles as $file) {
            if (   substr($file, -8) != 'Test.php'
                || isset($testClassFiles[$file])
                || isset($testParentFiles[$file])
            ) {
                $files[] = $file;
            }
        }

        $saveFiles = array();
        foreach ($files as $file) {
            foreach ($dirPaths as $path) {
                if (strpos($file, $path) !== false) {
                    $saveFiles[$file] = true;
                    break;
                }
            }
        }

        $testFilesPath = getcwd() . '/test-files';
        if (file_exists($testFilesPath)) {
            $existFiles = file($testFilesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($existFiles as $file) {
                $saveFiles[$file] = true;
            }
        }
        file_put_contents($testFilesPath, implode("\n", array_keys($saveFiles)));
    }

    public function generateMakefile()
    {
        $makefilePath = getcwd() . '/Makefile';
        if (file_exists($makefilePath)) {
            return;
        }

        if (class_exists('PHPUnit_DbUnit_TestCase', false)) {
            $phpunitExt = '-u phpunit-ext ';
        } else {
            $phpunitExt = '';
        }

        $code = <<<MAKEFILECODR
FILES = run-test.php test-files
test: $(FILES)
	bpc -v \
	    -o test \
	    -u phpunit $phpunitExt\
	    -d display_errors=on \
	    run-test.php \
	    --input-file test-files
clean:
	@rm -rf .bpc-build-* md5.map
	@rm -fv $(FILES) TESTCASE_LIST.php test
	@rm -rf MockClassFile
MAKEFILECODR;

        file_put_contents($makefilePath, $code);
    }
}
