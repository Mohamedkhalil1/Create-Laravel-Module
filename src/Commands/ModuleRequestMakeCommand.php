<?php

namespace Loffy\CreateLaravelModule\Commands;

use Illuminate\Console\GeneratorCommand;

class ModuleRequestMakeCommand extends GeneratorCommand
{
    protected $name = 'make:module-request';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/request.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Requests';
    }
}
