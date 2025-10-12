<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    protected function redirectTo(Request $request)
    {
        if ($request->is('api/*')) {
            return null;
        }

        return parent::redirectTo($request);
    }
}