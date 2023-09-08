<?php

namespace Loffy\CreateLaravelModule\DTOs;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Loffy\CreateLaravelModule\Mappers\DoctrineMapper;

class ModuleDTO
{
    private string $model;

    private string $baseModelName;

    private string $namespace;

    private string $pluralBaseModelName;

    public readonly Collection $columns;
    public readonly Collection $foreignKeys;

    private array $newTranslationWords = [];

    private Stringable $snakeCaseTitle;

    private Stringable $singularSnakeCaseTitle;

    private Stringable $title;

    private Stringable $titleSingular;

    public function setAttributes($model): void
    {
        $this->model = $model;
        $this->baseModelName = class_basename($this->model);
        $this->setNamespace();
        $this->pluralBaseModelName = Str::plural($this->baseModelName);
        $this->snakeCaseTitle = str($this->pluralBaseModelName)->snake(' ');
        $this->singularSnakeCaseTitle = str($this->pluralBaseModelName)->snake()->singular();
        $this->title = $this->snakeCaseTitle->headline();
        $this->titleSingular = $this->title->singular();
        $mapper = DoctrineMapper::make(Str::snake($this->snakeCaseTitle->toString()));
        $this->columns = $mapper->getColumns();
        $this->foreignKeys = $mapper->getForeignKeys();
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getBaseModelName(): string
    {
        return $this->baseModelName;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }
    private function setNamespace(): void
    {
        $model = $this->model;
        $parts = explode('\\', $model);
        $this->namespace = $parts[count($parts) - 2];
    }

    public function getPluralBaseModelName(): string
    {
        return $this->pluralBaseModelName;
    }

    public function getNewTranslationWords(): array
    {
        return $this->newTranslationWords;
    }

    public function getSnakeCaseTitle(): Stringable
    {
        return $this->snakeCaseTitle;
    }

    public function getSingularSnakeCaseTitle(): Stringable
    {
        return $this->singularSnakeCaseTitle;
    }

    public function getTitle(): Stringable
    {
        return $this->title;
    }

    public function getTitleSingular(): Stringable
    {
        return $this->titleSingular;
    }
}
