<?php

namespace Loffy\CreateLaravelModule\Modules;

use Exception;
use Illuminate\Support\Facades\File;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;

class ResourceModule
{
    public function __construct(private ModuleDTO $dto)
    {
    }

    public static function make(ModuleDTO $dto): static
    {
        return new static($dto);
    }

    public function handle()
    {
        $resourceName = "{$this->dto->getBaseModelName()}Resource";
        $resourceDir = base_path("app/Http/Resources/{$this->dto->getNamespace()}");
        $thisString = '$this';
        $resources = $this->dto->getColumns()
            ->map(fn ($column) => "            '$column->COLUMN_NAME' => $thisString->$column->COLUMN_NAME,")
            ->join(PHP_EOL);

        $resource = File::get(__DIR__.'/../Commands/stubs/DummyResource.stub');
        $resource = str_replace('DummyNamespace', $this->dto->getNamespace(), $resource);
        $resource = str_replace('DummyResource', "{$this->dto->getBaseModelName()}Resource", $resource);
        $resource = str_replace('ResourcesArray', $resources, $resource);
        $resource = str_replace('DummyModel', $this->dto->getBaseModelName(), $resource);
        if (File::exists($resourceDir."/$resourceName.php")) {
            throw new Exception("Resource $resourceName already exist in $resourceDir!");
        }
        if (! File::exists($resourceDir)) {
            File::makeDirectory($resourceDir, recursive: true);
        }
        File::put($resourceDir."/$resourceName.php", $resource);
    }
}
