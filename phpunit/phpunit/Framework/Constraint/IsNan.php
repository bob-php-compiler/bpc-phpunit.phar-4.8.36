<?php

class PHPUnit_Framework_Constraint_IsNan extends PHPUnit_Framework_Constraint
{
    protected function matches($other)
    {
        return is_nan($other);
    }

    public function toString()
    {
        return 'is nan';
    }
}
