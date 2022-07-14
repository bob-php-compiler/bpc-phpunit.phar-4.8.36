<?php

class PHPUnit_Util_Bpc
{
    // className => filename
    protected static $testSuiteClasses = array();

    public static function collectTestSuiteClass($className, $filename)
    {
        self::$testSuiteClasses[$className] = $filename;
    }

    public static function generateEntryFile()
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
        foreach (self::$testSuiteClasses as $className => $filename) {
            $map[$className] = substr($filename, $prefixLen);
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

    public static function saveTestFiles($runBeforeFiles, $dirPaths)
    {
        $currentWorkingDir = getcwd();
        $files             = array_diff(get_included_files(), $runBeforeFiles);
        $testFilesPath     = $currentWorkingDir . '/test-files';
        if (file_exists($testFilesPath)) {
            $existFiles = file($testFilesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $files      = array_merge($files, $existFiles);
        }
        $files = array_unique($files);
        $saveFiles = array();
        foreach ($files as $file) {
            foreach ($dirPaths as $path) {
                if (strpos($file, $path) !== false) {
                    $saveFiles[] = $file;
                    break;
                }
            }
        }
        file_put_contents($testFilesPath, implode("\n", $saveFiles));
    }

    public static function saveMakefile()
    {
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

        $makefilePath = getcwd() . '/Makefile';
        if (!file_exists($makefilePath)) {
            file_put_contents($makefilePath, $code);
        }
    }
}
