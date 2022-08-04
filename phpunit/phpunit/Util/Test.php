<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!function_exists('trait_exists')) {
    function trait_exists($traitname, $autoload = true)
    {
        return false;
    }
}

/**
 * Test helpers.
 *
 * @since Class available since Release 3.0.0
 */
class PHPUnit_Util_Test
{
    const REGEX_DATA_PROVIDER      = '/@dataProvider\s+([a-zA-Z0-9._:-\\\\x7f-\xff]+)/';
    const REGEX_TEST_WITH          = '/@testWith\s+/';
    const REGEX_EXPECTED_EXCEPTION = '(@expectedException\s+([:.\w\\\\x7f-\xff]+)(?:[\t ]+(\S*))?(?:[\t ]+(\S*))?\s*$)m';
    const REGEX_REQUIRES_VERSION   = '/@requires\s+(?P<name>PHP(?:Unit)?)\s+(?P<value>[\d\.-]+(dev|(RC|alpha|beta)[\d\.])?)[ \t]*\r?$/m';
    const REGEX_REQUIRES_OS        = '/@requires\s+OS\s+(?P<value>.+?)[ \t]*\r?$/m';
    const REGEX_REQUIRES           = '/@requires\s+(?P<name>function|extension)\s+(?P<value>([^ ]+?))[ \t]*\r?$/m';

    const UNKNOWN = -1;
    const SMALL   = 0;
    const MEDIUM  = 1;
    const LARGE   = 2;

    private static $annotationCache = array();

    private static $hookMethods = array();

    /**
     * @param PHPUnit_Framework_Test $test
     * @param bool                   $asString
     *
     * @return mixed
     */
    public static function describe(PHPUnit_Framework_Test $test, $asString = true)
    {
        if ($asString) {
            if ($test instanceof PHPUnit_Framework_SelfDescribing) {
                return $test->toString();
            } else {
                return get_class($test);
            }
        } else {
            if ($test instanceof PHPUnit_Framework_TestCase) {
                return array(
                  get_class($test), $test->getName()
                );
            } elseif ($test instanceof PHPUnit_Framework_SelfDescribing) {
                return array('', $test->toString());
            } else {
                return array('', get_class($test));
            }
        }
    }

    /**
     * Returns the provided data for a method.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return array|Iterator when a data provider is specified and exists
     *                        null           when no data provider is specified
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.2.0
     */
    public static function getProvidedData($className, $methodName)
    {
        $dataProviderMethodName = 'dataProvider' . ucwords($methodName);
        $methods = get_class_methods($className);
        if (in_array($dataProviderMethodName, $methods)) {
            $object = new $className;
            $data   = $object->$dataProviderMethodName();
        } else {
            $data = null;
        }

        if (is_array($data) && empty($data)) {
            throw new PHPUnit_Framework_SkippedTestError;
        }

        if ($data !== null) {
            if (is_object($data)) {
                $data = iterator_to_array($data);
            }

            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    throw new PHPUnit_Framework_Exception(
                        sprintf(
                            'Data set %s is invalid.',
                            is_int($key) ? '#' . $key : '"' . $key . '"'
                        )
                    );
                }
            }
        }

        return $data;
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @return array
     *
     * @throws ReflectionException
     *
     * @since  Method available since Release 3.4.0
     */
    public static function parseTestMethodAnnotations($className, $methodName = '')
    {
        if (defined('__BPC__')) {
            return null;
        } else {
            if (!isset(self::$annotationCache[$className])) {
                $class                             = new ReflectionClass($className);
                self::$annotationCache[$className] = self::parseAnnotations($class->getDocComment());
            }

            if (!empty($methodName) && !isset(self::$annotationCache[$className . '::' . $methodName])) {
                try {
                    $method      = new ReflectionMethod($className, $methodName);
                    $annotations = self::parseAnnotations($method->getDocComment());
                } catch (ReflectionException $e) {
                    $annotations = array();
                }
                self::$annotationCache[$className . '::' . $methodName] = $annotations;
            }

            return array(
              'class'  => self::$annotationCache[$className],
              'method' => !empty($methodName) ? self::$annotationCache[$className . '::' . $methodName] : array()
            );
        }
    }

    /**
     * @param string $docblock
     *
     * @return array
     *
     * @since  Method available since Release 3.4.0
     */
    private static function parseAnnotations($docblock)
    {
        $annotations = array();
        // Strip away the docblock header and footer to ease parsing of one line annotations
        $docblock = substr($docblock, 3, -2);

        if (preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; ++$i) {
                $annotations[$matches['name'][$i]][] = $matches['value'][$i];
            }
        }

        return $annotations;
    }

    /**
     * Returns the dependencies for a test class or method.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return array
     *
     * @since  Method available since Release 3.4.0
     */
    public static function getDependencies($className, $methodName)
    {
        $dependencies = array();
        // class depends为类中的 static $classDepends = array('methodName');
        if (property_exists($className, 'classDepends')) {
            $dependencies = $className::$classDepends;
        }

        // method depends为类中的 static的名为 'depends' + $methodName的变量, 为一个数组, 包含依赖的方法名;
        $dependsMethodName = 'depends' . ucwords($methodName);
        if (property_exists($className, $dependsMethodName)) {
            $dependencies = array_merge(
                $dependencies,
                $className::$$dependsMethodName
            );
        }

        return array_unique($dependencies);
    }

    /**
     * Returns the error handler settings for a test.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return bool
     *
     * @since  Method available since Release 3.4.0
     */
    public static function getErrorHandlerSettings($className, $methodName)
    {
        if (defined('__BPC__')) {
            return null;
        } else {
            return self::getBooleanAnnotationSetting(
                $className,
                $methodName,
                'errorHandler'
            );
        }
    }

    /**
     * Returns the groups for a test class or method.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return array
     *
     * @since  Method available since Release 3.2.0
     */
    public static function getGroups($className, $methodName = '')
    {
        $groups = array();
        // class groups为类中的 static $classGroups = array('groupName');
        if (property_exists($className, 'classGroups')) {
            $groups = $className::$classGroups;
        }

        // method groups为类中的 static的名为 'group' + $methodName的变量, 为一个数组, 包含依赖的方法名;
        $groupMethodName = 'groups' . ucwords($methodName);
        if (property_exists($className, $groupMethodName)) {
            $groups = array_merge(
                $groups,
                $className::$$groupMethodName
            );
        }

        return array_unique($groups);
    }

    /**
     * Returns the size of the test.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return int
     *
     * @since  Method available since Release 3.6.0
     */
    public static function getSize($className, $methodName)
    {
        $groups = array_flip(self::getGroups($className, $methodName));
        $size   = self::UNKNOWN;

        if (isset($groups['large']) ||
            (class_exists('PHPUnit_Extensions_Database_TestCase', false) &&
             is_subclass_of($className, 'PHPUnit_Extensions_Database_TestCase'))) {
            $size = self::LARGE;
        } elseif (isset($groups['medium'])) {
            $size = self::MEDIUM;
        } elseif (isset($groups['small'])) {
            $size = self::SMALL;
        }

        return $size;
    }


    /**
     * Returns the preserve global state settings for a test.
     *
     * @param string $className
     * @param string $methodName
     *
     * @return bool
     *
     * @since  Method available since Release 3.4.0
     */
    public static function getPreserveGlobalStateSettings($className, $methodName)
    {
        if (defined('__BPC__')) {
            return null;
        } else {
            return self::getBooleanAnnotationSetting(
                $className,
                $methodName,
                'preserveGlobalState'
            );
        }
    }

    /**
     * @param string $className
     *
     * @return array
     *
     * @since  Method available since Release 4.0.8
     */
    public static function getHookMethods($className)
    {
        if (!class_exists($className, false)) {
            return self::emptyHookMethodsArray();
        }

        if (!isset(self::$hookMethods[$className])) {
            self::$hookMethods[$className] = self::emptyHookMethodsArray();

            try {
                $methods = get_class_methods($className);
                foreach ($methods as $method) {
                    if (self::isBeforeClassMethod($method, $className)) {
                        self::$hookMethods[$className]['beforeClass'][] = $method;
                    }

                    if (self::isBeforeMethod($method, $className)) {
                        self::$hookMethods[$className]['before'][] = $method;
                    }

                    if (self::isAfterMethod($method, $className)) {
                        self::$hookMethods[$className]['after'][] = $method;
                    }

                    if (self::isAfterClassMethod($method, $className)) {
                        self::$hookMethods[$className]['afterClass'][] = $method;
                    }
                }
            } catch (Exception $e) {
            }
        }

        return self::$hookMethods[$className];
    }

    /**
     * @return array
     *
     * @since  Method available since Release 4.0.9
     */
    private static function emptyHookMethodsArray()
    {
        return array(
            'beforeClass' => array('setUpBeforeClass'),
            'before'      => array('setUp'),
            'after'       => array('tearDown'),
            'afterClass'  => array('tearDownAfterClass')
        );
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param string $settingName
     *
     * @return bool
     *
     * @since  Method available since Release 3.4.0
     */
    private static function getBooleanAnnotationSetting($className, $methodName, $settingName)
    {
        $annotations = self::parseTestMethodAnnotations(
            $className,
            $methodName
        );

        $result = null;

        if (isset($annotations['class'][$settingName])) {
            if ($annotations['class'][$settingName][0] == 'enabled') {
                $result = true;
            } elseif ($annotations['class'][$settingName][0] == 'disabled') {
                $result = false;
            }
        }

        if (isset($annotations['method'][$settingName])) {
            if ($annotations['method'][$settingName][0] == 'enabled') {
                $result = true;
            } elseif ($annotations['method'][$settingName][0] == 'disabled') {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param string $method
     * @param string $class
     *
     * @return bool
     *
     * @since  Method available since Release 4.0.8
     */
    private static function isBeforeClassMethod($method, $class)
    {
        return method_exists($class, 'beforeClassMethod' . ucwords($method));
    }

    /**
     * @param string $method
     * @param string $class
     *
     * @return bool
     *
     * @since  Method available since Release 4.0.8
     */
    private static function isBeforeMethod($method, $class)
    {
        return method_exists($class, 'beforeMethod' . ucwords($method));
    }

    /**
     * @param string $method
     * @param string $class
     *
     * @return bool
     *
     * @since  Method available since Release 4.0.8
     */
    private static function isAfterClassMethod($method, $class)
    {
        return method_exists($class, 'afterClassMethod' . ucwords($method));
    }

    /**
     * @param string $method
     * @param string $class
     *
     * @return bool
     *
     * @since  Method available since Release 4.0.8
     */
    private static function isAfterMethod($method, $class)
    {
        return method_exists($class, 'afterMethod' . ucwords($method));
    }
}
