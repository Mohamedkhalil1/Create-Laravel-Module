<?php

namespace Loffy\CreateLaravelModule\Facades;

use Illuminate\Support\Facades\Facade;

class CreateLaravelModule extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CreateLaravelModule::class;
    }
}
