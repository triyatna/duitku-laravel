<?php

namespace Triyatna\DuitkuLaravel;

use Illuminate\Support\Facades\Facade;

class DuitkuFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'duitku';
    }
}
