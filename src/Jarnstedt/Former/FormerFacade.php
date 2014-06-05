<?php namespace Jarnstedt\Former;

use Illuminate\Support\Facades\Facade;

class FormerFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'former'; }

}
