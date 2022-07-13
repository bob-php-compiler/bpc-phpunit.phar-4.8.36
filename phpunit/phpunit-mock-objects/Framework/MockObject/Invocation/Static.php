<?php
/*
 * This file is part of the PHPUnit_MockObject package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a static invocation.
 *
 * @since Class available since Release 1.0.0
 */
class PHPUnit_Framework_MockObject_Invocation_Static implements PHPUnit_Framework_MockObject_Invocation, PHPUnit_Framework_SelfDescribing
{
    /**
     * @var array
     */
    protected static $uncloneableExtensions = array(
      'mysqli'    => true,
      'SQLite'    => true,
      'sqlite3'   => true,
      'tidy'      => true,
      'xmlwriter' => true,
      'xsl'       => true
    );

    /**
     * @var array
     */
    protected static $uncloneableClasses = array(
      'Closure',
      'COMPersistHelper',
      'IteratorIterator',
      'RecursiveIteratorIterator',
      'SplFileObject',
      'PDORow',
      'ZipArchive'
    );

    /**
     * @var string
     */
    public $className;

    /**
     * @var string
     */
    public $methodName;

    /**
     * @var array
     */
    public $parameters;

    /**
     * @param string $className
     * @param string $methodname
     * @param array  $parameters
     * @param bool   $cloneObjects
     */
    public function __construct($className, $methodName, array $parameters, $cloneObjects = false)
    {
        $this->className  = $className;
        $this->methodName = $methodName;
        $this->parameters = $parameters;

        if (!$cloneObjects) {
            return;
        }

        foreach ($this->parameters as $key => $value) {
            if (is_object($value)) {
                $this->parameters[$key] = $this->cloneObject($value);
            }
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        $exporter = new SebastianBergmann_Exporter_Exporter;

        return sprintf(
            '%s::%s(%s)',
            $this->className,
            $this->methodName,
            implode(
                ', ',
                array_map(
                    array($exporter, 'shortenedExport'),
                    $this->parameters
                )
            )
        );
    }

    /**
     * @param  object $original
     * @return object
     */
    protected function cloneObject($original)
    {
        $cloneable = null;

        if ($cloneable === null) {
            foreach (self::$uncloneableClasses as $class) {
                if ($original instanceof $class) {
                    $cloneable = false;
                    break;
                }
            }
        }

        $methods = get_class_methods($original);
        if ($cloneable === null && in_array('__clone', $methods)) {
            $cloneable = true;
        }

        if ($cloneable === null) {
            $cloneable = true;
        }

        if ($cloneable) {
            try {
                return clone $original;
            } catch (Exception $e) {
                return $original;
            }
        } else {
            return $original;
        }
    }
}
