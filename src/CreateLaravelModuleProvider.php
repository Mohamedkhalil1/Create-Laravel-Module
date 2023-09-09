<?php

namespace Loffy\CreateLaravelModule;

use Loffy\CreateLaravelModule\Commands\MakeModuleCommand;
use Loffy\CreateLaravelModule\Commands\ModuleRequestMakeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CreateLaravelModuleProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('create-laravel-module')
            ->hasConfigFile()
            ->hasCommands([
                MakeModuleCommand::class,
                ModuleRequestMakeCommand::class
            ]);
    }
}
