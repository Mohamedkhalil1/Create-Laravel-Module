<?php

namespace Loffy\CreateLaravelModule\Modules\Request\Commands;

use Illuminate\Console\GeneratorCommand;

class RequestMakeCommand extends GeneratorCommand
{
    protected $name = 'make:module-request';

    protected function getStub(): string
    {
        return __DIR__ . '/../Stub/request.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Requests';
    }
}
