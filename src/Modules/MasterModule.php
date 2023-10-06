<?php

namespace Loffy\CreateLaravelModule\Modules;

use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
use Loffy\CreateLaravelModule\Modules\Controller\ControllerModule;
use Loffy\CreateLaravelModule\Modules\Request\RequestModule;
use Loffy\CreateLaravelModule\Modules\Resource\ResourceModule;
use Loffy\CreateLaravelModule\Modules\Route\RouteModule;

class MasterModule
{
    private static array $options;

    public function __construct(private readonly ModuleDTO $dto)
    {
        self::$options = config('module.options');
    }

    public static function make(ModuleDTO $dto): self
    {
        return new static($dto);
    }

    public function handle(): void
    {
        foreach (self::$options as $option => $value) {
            if ($value) {
                match ($option) {
                    'route' => $this->createRoutes(),
                    'controller' => $this->createController(),
                    'request' => $this->createRequest(),
                    'resource' => $this->createResource(),
                };
            }
        }
    }

    public function createRoutes(): self
    {
        RouteModule::make($this->dto)->handle();

        return $this;
    }

    public function createController(): self
    {
        ControllerModule::make($this->dto)->handle();

        return $this;
    }

    public function createRequest(): self
    {
        RequestModule::make($this->dto)->handle();

        return $this;
    }

    public function createResource(): self
    {
        ResourceModule::make($this->dto)->handle();

        return $this;
    }
}
