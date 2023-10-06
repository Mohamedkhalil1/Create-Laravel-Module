<?php

namespace Loffy\CreateLaravelModule\Modules\Request;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
use Loffy\CreateLaravelModule\Generators\ModuleGenerator;
use Loffy\CreateLaravelModule\Modules\Request\Generator\RequestGenerator;
use Loffy\CreateLaravelModule\Modules\Request\Mapper\ColumnToRequestMapper;
use Loffy\CreateLaravelModule\Modules\Request\Mapper\ForeignKeyToRequestMapper;
use Loffy\CreateLaravelModule\Modules\Request\Mapper\IndexToRequestMapper;

class RequestModule
{
    private Collection $rules;

    private ModuleGenerator $generator;

    public function __construct(private readonly ModuleDTO $dto)
    {
        $this->rules = new Collection();
    }

    public static function make(ModuleDTO $dto): self
    {
        return new self($dto);
    }

    public function handle(): void
    {
        $this
            ->makeRequestCommand()
            ->mapColumns()
            ->mapForeignKeys()
            ->mapIndexes();

        RequestGenerator::make($this->rules)
            ->generate()
            ->addRulesToRequestFile($this->getRequestFile());

    }

    private function makeRequestCommand(): self
    {
        $result = Artisan::call('make:module-request', [
            'name' => "{$this->dto->relativeNamespace}\\{$this->dto->getModelName()}Request",
        ]);

        if ($result !== 0) {
            throw new Exception('Request command failed');
        }

        return $this;
    }

    private function mapColumns(): self
    {
        $this->rules = $this->rules->mergeRecursive($this->dto->columns->mapWithKeys(function (Column $column) {
            return ColumnToRequestMapper::make($column)->handle();
        }));

        return $this;
    }

    private function mapForeignKeys(): self
    {
        $this->rules = $this->rules->mergeRecursive($this->dto->foreignKeys->mapWithKeys(function (ForeignKeyConstraint $foreignKey) {
            return ForeignKeyToRequestMapper::make($foreignKey)->handle();
        }));

        return $this;
    }

    private function mapIndexes(): self
    {
        $this->rules = $this->rules->mergeRecursive($this->dto->indexes->mapWithKeys(function (Index $index) {
            return IndexToRequestMapper::make($index, $this->dto->model->getTable())->handle();
        }));

        return $this;
    }

    private function getRequestFile(): string
    {
        return app_path("Http/Requests/{$this->dto->relativeNamespace}/{$this->dto->getModelName()}Request.php");
    }
}
