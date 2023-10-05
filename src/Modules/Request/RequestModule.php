<?php

namespace Loffy\CreateLaravelModule\Modules\Request;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\AsciiStringType;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\VarDateTimeType;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
use Loffy\CreateLaravelModule\Generators\ModuleGenerator;
use Loffy\CreateLaravelModule\Support\ColumnSupport;

class RequestModule
{
    private Collection $rules;

    private Collection $currentRules;

    private AbstractAsset $currentColumn;

    private string $columnTypeAsRule;
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
            ->setRules()
            ->generateRules()
            ->addRulesToRequestFile();
    }

    private function setRules(): self
    {
        $this
            ->mapColumns()
            ->mapForeignKeys()
            ->mapIndexes();
        return $this;
    }

    private function mapColumns(): self
    {
        $this->rules = $this->rules->merge($this->dto->columns->mapWithKeys(function (Column $column) {
            if (ColumnSupport::isIgnored($column)) {
                return [];
            }

            $this->currentRules = new Collection();
            $this->currentColumn = $column;
            $this
                ->setColumnConstraints()
                ->setColumnTypeRules()
                ->setDefaultRules()
                ->setNameRules();

            return [
                $this->currentColumn->getName() => $this->currentRules->filter()->all(),
            ];
        }));

        return $this;
    }
    private function mapForeignKeys(): self
    {
        $this->rules = $this->rules->mergeRecursive($this->dto->foreignKeys->mapWithKeys(function (ForeignKeyConstraint $foreignKey) {
            $this->currentRules = new Collection();
            $this->currentColumn = $foreignKey;
            $this->setForeignKeyRules();
            return [
                $this->currentColumn->getLocalColumns()[0] => $this->currentRules->filter()->all(),
            ];
        }));
        return $this;
    }
    private function mapIndexes(): self
    {
        $this->rules = $this->rules->mergeRecursive($this->dto->indexes->mapWithKeys(function (Index $index) {
            $this->currentRules = new Collection();
            $this->currentColumn = $index;
            $this->setIndexRules();

            return [
                $this->currentColumn->getColumns()[0] => $this->currentRules->filter()->all(),
            ];
        }));
        return $this;
    }
    private function setColumnTypeRules(): self
    {
        $this->columnTypeAsRule = match (get_class($this->currentColumn->getType())) {
            BigIntType::class, IntegerType::class, SmallIntType::class => 'integer',
            JsonType::class, ArrayType::class, SimpleArrayType::class => 'array',
            AsciiStringType::class, StringType::class, BinaryType::class, GuidType::class, TextType::class => 'string',
            BooleanType::class => 'boolean',
            TimeType::class, DateIntervalType::class, DateTimeType::class, DateTimeTzType::class, VarDateTimeType::class, DateType::class => 'date',
            DecimalType::class, FloatType::class => 'numeric',
            default => null
        };

        $this->currentRules->push($this->columnTypeAsRule);

        return $this;
    }

    private function setColumnConstraints(): self
    {
        $this->currentRules->push($this->currentColumn->getNotnull() ? 'required' : 'nullable');

        return $this;
    }

    private function setDefaultRules(): self
    {
        $columnTypes = config('module.request.defaults');

        $typeRules = $columnTypes[$this->columnTypeAsRule] ?? null;

        if (!$typeRules) {
            return $this;
        }

        foreach ($typeRules as $rule) {
            $this->currentRules->push($rule);
        }

        return $this;
    }

    private function setNameRules(): self
    {
        $columnNames = config('module.request.names');

        $typeRules = $columnNames[$this->currentColumn->getName()] ?? null;

        if (!$typeRules) {
            return $this;
        }

        foreach ($typeRules as $rule) {
            $this->currentRules->push($rule);
        }

        return $this;
    }
    private function setForeignKeyRules(): self
    {
        if (! $this->currentColumn instanceof ForeignKeyConstraint){
            return $this;
        }

        $this->currentRules->push("Rule::exists('{$this->currentColumn->getForeignTableName()}','{$this->currentColumn->getForeignColumns()[0]}')");

        return $this;
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

    private function generateRules(): self
    {
        $this->generator = ModuleGenerator::make($this->rules)->generateRequestRules();

        return $this;
    }

    private function addRulesToRequestFile(): self
    {
        $fullQualifiedRequest = app_path("Http/Requests/{$this->dto->relativeNamespace}/{$this->dto->getModelName()}Request.php");

        $requestFile = File::get($fullQualifiedRequest);

        $requestFile = str_replace(
            'return [',
            "return [\n\t\t\t{$this->generator->getGenerated()},",
            $requestFile
        );

        $requestFile = str_replace(
            'use Illuminate\Foundation\Http\FormRequest;',
            "use Illuminate\Foundation\Http\FormRequest;\n{$this->generator->getImports()}",
            $requestFile
        );

        File::put($fullQualifiedRequest, $requestFile);

        return $this;
    }

    private function setIndexRules(): static
    {
        if (! $this->currentColumn instanceof Index){
            return $this;
        }
        if ($this->currentColumn->isPrimary()){
            $this->currentRules->push("Rule::exists('{$this->dto->model->getTable()}' , '{$this->currentColumn->getColumns()[0]}')");

            return $this;
        }
        $this->currentRules->push("Rule::unique('{$this->dto->model->getTable()}' , '{$this->currentColumn->getColumns()[0]}')");

        return $this;
    }



}
