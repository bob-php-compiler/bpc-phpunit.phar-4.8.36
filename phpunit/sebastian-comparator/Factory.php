<?php
/*
 * This file is part of the Comparator package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Factory for comparators which compare values for equality.
 */
class SebastianBergmann_Comparator_Factory
{
    /**
     * @var Comparator[]
     */
    private $comparators = array();

    /**
     * @var Factory
     */
    private static $instance;

    /**
     * Constructs a new factory.
     */
    public function __construct()
    {
        $this->register(new SebastianBergmann_Comparator_TypeComparator);
        $this->register(new SebastianBergmann_Comparator_ScalarComparator);
        $this->register(new SebastianBergmann_Comparator_NumericComparator);
        $this->register(new SebastianBergmann_Comparator_DoubleComparator);
        $this->register(new SebastianBergmann_Comparator_ArrayComparator);
        $this->register(new SebastianBergmann_Comparator_ResourceComparator);
        $this->register(new SebastianBergmann_Comparator_ObjectComparator);
        $this->register(new SebastianBergmann_Comparator_ExceptionComparator);
        $this->register(new SebastianBergmann_Comparator_SplObjectStorageComparator);
        $this->register(new SebastianBergmann_Comparator_DOMNodeComparator);
        $this->register(new SebastianBergmann_Comparator_MockObjectComparator);
        $this->register(new SebastianBergmann_Comparator_DateTimeComparator);
    }

    /**
     * @return Factory
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Returns the correct comparator for comparing two values.
     *
     * @param  mixed      $expected The first value to compare
     * @param  mixed      $actual   The second value to compare
     * @return Comparator
     */
    public function getComparatorFor($expected, $actual)
    {
        foreach ($this->comparators as $comparator) {
            if ($comparator->accepts($expected, $actual)) {
                return $comparator;
            }
        }
    }

    /**
     * Registers a new comparator.
     *
     * This comparator will be returned by getInstance() if its accept() method
     * returns TRUE for the compared values. It has higher priority than the
     * existing comparators, meaning that its accept() method will be tested
     * before those of the other comparators.
     *
     * @param Comparator $comparator The registered comparator
     */
    public function register(SebastianBergmann_Comparator_Comparator $comparator)
    {
        array_unshift($this->comparators, $comparator);

        $comparator->setFactory($this);
    }

    /**
     * Unregisters a comparator.
     *
     * This comparator will no longer be returned by getInstance().
     *
     * @param Comparator $comparator The unregistered comparator
     */
    public function unregister(SebastianBergmann_Comparator_Comparator $comparator)
    {
        foreach ($this->comparators as $key => $_comparator) {
            if ($comparator === $_comparator) {
                unset($this->comparators[$key]);
            }
        }
    }
}
