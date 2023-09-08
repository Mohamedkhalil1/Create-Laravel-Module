<?php

namespace Loffy\CreateLaravelModule\Modules;

use Exception;
use Illuminate\Support\Facades\File;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;

class ControllerModule
{
    public function __construct(private ModuleDTO $dto) {}

    public static function make(ModuleDTO $dto): static
    {
        return new static($dto);
    }

    public function handle(): void
    {
        $controllerName = "{$this->dto->getBaseModelName()}Controller";
        $controllerDir = base_path("app/Http/Controllers/{$this->dto->getNamespace()}");
        $controller = File::get(__DIR__ . '/../Commands/stubs/DummyController.stub');
        $controller = str_replace('DummyNamespace', $this->dto->getNamespace(), $controller);
        $controller = str_replace('DummyRequest', "{$this->dto->getBaseModelName()}Request", $controller);
        $controller = str_replace('FullyQualifiedDummyModel', $this->dto->getModel(), $controller);
        $controller = str_replace('DummyController', $controllerName, $controller);
        $controller = str_replace('dummies', $this->dto->getSnakeCaseTitle(), $controller);
        $controller = str_replace('Dummies', $this->dto->getTitle(), $controller);
        $controller = str_replace('dummy', $this->dto->getSingularSnakeCaseTitle(), $controller);
        $controller = str_replace('DummyTitle', $this->dto->getTitleSingular(), $controller);
        $controller = str_replace('camelCaseDummy', str($this->dto->getBaseModelName())->camel(), $controller);
        $controller = str_replace('Dummy', $this->dto->getBaseModelName(), $controller);
        if (File::exists($controllerDir . "/$controllerName.php")) {
            throw new Exception("Controller $controllerName already exist in $controllerDir!");
        }
        if (!File::exists($controllerDir)) {
            File::makeDirectory($controllerDir, recursive: true);
        }
        File::put($controllerDir . "/$controllerName.php", $controller);
    }
}
