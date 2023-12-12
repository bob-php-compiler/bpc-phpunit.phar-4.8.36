<?php

class PHPUnit_Framework_Constraint_IsFinite extends PHPUnit_Framework_Constraint
{
    protected function matches($other)
    {
        return is_finite($other);
    }

    public function toString()
    {
        return 'is finite';
    }
}
