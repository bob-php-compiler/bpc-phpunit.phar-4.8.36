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
 * Iterator for test suites.
 *
 * @since Class available since Release 3.1.0
 */
class PHPUnit_Util_TestSuiteIterator implements RecursiveIterator
{
    /**
     * @var int
     */
    protected $position;

    /**
     * @var PHPUnit_Framework_Test[]
     */
    protected $tests;

    /**
     * @param PHPUnit_Framework_TestSuite $testSuite
     */
    public function __construct(PHPUnit_Framework_TestSuite $testSuite)
    {
        $this->tests = $testSuite->tests();
    }

    /**
     * Rewinds the Iterator to the first element.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Checks if there is a current element after calls to rewind() or next().
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->position < count($this->tests);
    }

    /**
     * Returns the key of the current element.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    /**
     * Returns the current element.
     *
     * @return PHPUnit_Framework_Test
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->valid() ? $this->tests[$this->position] : null;
    }

    /**
     * Moves forward to next element.
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Returns the sub iterator for the current element.
     *
     * @return PHPUnit_Util_TestSuiteIterator
     */
    public function getChildren(): RecursiveIterator
    {
        return new self(
            $this->tests[$this->position]
        );
    }

    /**
     * Checks whether the current element has children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->tests[$this->position] instanceof PHPUnit_Framework_TestSuite;
    }
}
