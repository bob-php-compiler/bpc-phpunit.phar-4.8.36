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
 * Utility class for blacklisting PHPUnit's own source code files.
 *
 * @since Class available since Release 4.0.0
 */
class PHPUnit_Util_Blacklist
{

    /**
     * @var array
     */
    public static $blacklistedClassNames = array(
        'File_Iterator'                              => 'php-file-iterator/',
        'PHP_Timer'                                  => 'php-timer/',
        'PHPUnit_Framework_TestCase'                 => 'phpunit/',
        'PHPUnit_Extensions_Database_TestCase'       => 'dbunit/Extensions/',
        'PHPUnit_Framework_MockObject_Generator'     => 'phpunit-mock-objects/Framework/',
        'Text_Template'                              => 'php-text-template/',
        'SebastianBergmann_Diff_Diff'                => 'sebastian-diff/',
        'SebastianBergmann_Environment_Runtime'      => 'sebastian-environment/',
        'SebastianBergmann_Comparator_Comparator'    => 'sebastian-comparator/',
        'SebastianBergmann_Exporter_Exporter'        => 'sebastian-exporter/',
        'SebastianBergmann_RecursionContext_Context' => 'sebastian-recursion-context/',
        'SebastianBergmann_Version'                  => 'sebastian-version/'
    );

    /**
     * @var array
     */
    private static $directories;

    /**
     * @return array
     *
     * @since  Method available since Release 4.1.0
     */
    public function getBlacklistedDirectories()
    {
        $this->initialize();

        return self::$directories;
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    public function isBlacklisted($file)
    {
        if (defined('PHPUNIT_TESTSUITE')) {
            return false;
        }

        $this->initialize();

        foreach (self::$directories as $directory) {
            if (strpos($file, $directory) > 0) {
                return true;
            }
        }

        return false;
    }

    private function initialize()
    {
        if (self::$directories === null) {
            self::$directories = array();

            foreach (self::$blacklistedClassNames as $className => $directory) {
                if (!class_exists($className)) {
                    continue;
                }

                self::$directories[] = $directory;
            }
        }
    }
}
