<?php

namespace Loffy\CreateLaravelModule\DTOs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Loffy\CreateLaravelModule\Mappers\DoctrineMapper;

class ModuleDTO
{
    public readonly Collection $columns;

    public readonly Collection $foreignKeys;

    public readonly Collection $indexes;

    public readonly array $newTranslationWords;

    public readonly string $relativeNamespace;

    public function __construct(public readonly Model $model)
    {
        $mapper = DoctrineMapper::make($model->getTable());
        $this->columns = $mapper->getColumns();
        $this->foreignKeys = $mapper->getForeignKeys();
        $this->indexes = $mapper->getIndexes();
        $this->setNamespace();
    }

    private function setNamespace(): void
    {
        $this->relativeNamespace = Str::after($this->model->getMorphClass(), 'App\\Models\\');
    }

    public function getModelName(): string
    {
        return class_basename($this->model);
    }
}
