<?php namespace Anzware\Avail\Facades;

use Illuminate\Support\Facades\Facade;

class Avail extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
    */
    protected static function getFacadeAccessor() { return 'avail'; }
}
