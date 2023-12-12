<?php

class PHPUnit_Framework_Constraint_IsInfinite extends PHPUnit_Framework_Constraint
{
    protected function matches($other)
    {
        return is_infinite($other);
    }

    public function toString()
    {
        return 'is infinite';
    }
}
