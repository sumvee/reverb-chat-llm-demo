<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * Extends Illuminate\Routing\Controller to restore $this->middleware().
 *
 * Laravel 11 dropped that method from the base controller, but laravel/ui
 * still scaffolds pre-11 style constructors: all six Auth controllers call
 * $this->middleware() and fatal without this. laravel/ui installs cleanly on
 * Laravel 12 -- its composer constraints allow it -- but its generated
 * controllers do not run on the slim skeleton unaided.
 *
 * This is the upgrade path Laravel documents for this case, and it keeps
 * laravel/ui's scaffold untouched so re-running ui:controllers stays safe.
 * Rewriting all six to implement HasMiddleware would fork them from upstream
 * for no gain.
 */
abstract class Controller extends BaseController
{
    //
}
