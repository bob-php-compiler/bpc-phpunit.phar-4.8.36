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
 * Compares doubles for equality.
 */
class SebastianBergmann_Comparator_DoubleComparator extends SebastianBergmann_Comparator_NumericComparator
{
    /**
     * Smallest value available in PHP.
     *
     * @var float
     */
    const EPSILON = 0.0000000001;

    /**
     * Returns whether the comparator can compare two values.
     *
     * @param  mixed $expected The first value to compare
     * @param  mixed $actual   The second value to compare
     * @return bool
     */
    public function accepts($expected, $actual)
    {
        return (is_double($expected) || is_double($actual)) && is_numeric($expected) && is_numeric($actual);
    }

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected     First value to compare
     * @param mixed $actual       Second value to compare
     * @param float $delta        Allowed numerical distance between two values to consider them equal
     * @param bool  $canonicalize Arrays are sorted before comparison when set to true
     * @param bool  $ignoreCase   Case is ignored when set to true
     *
     * @throws ComparisonFailure
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false, array &$processed = array())
    {
        if ($delta == 0) {
            $delta = self::EPSILON;
        }

        parent::assertEquals($expected, $actual, $delta, $canonicalize, $ignoreCase);
    }
}
