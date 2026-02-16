<?php

namespace Fazzinipierluigi\JustAGate\Facades;

use Illuminate\Support\Facades\Facade;

class JustAGate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'just_a_gate';
    }
}
