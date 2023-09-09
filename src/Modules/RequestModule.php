<?php

namespace Loffy\CreateLaravelModule\Modules;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
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
use Loffy\CreateLaravelModule\ColumnSupport;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
use Loffy\CreateLaravelModule\Generators\ModuleGenerator;

class RequestModule
{
    private Collection $rules;

    private Collection $currentRules;

    private Column $currentColumn;

    private string $columnTypeAsRule;

    private string $generated;

    public function __construct(private readonly ModuleDTO $dto) {}

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
        $this->rules = $this->dto->getColumns()->mapWithKeys(function (Column $column) {
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
        });

        $this->rules = $this->rules->merge(
            $this->dto->foreignKeys->mapWithKeys(fn (ForeignKeyConstraint $key) => [$key->getLocalColumns()[0] => ['required', "exists:{$key->getForeignTableName()},{$key->getForeignColumns()[0]}"]])
        );

        return $this;
    }

    private function setColumnTypeRules(): self
    {
        $this->columnTypeAsRule = match (get_class($this->currentColumn->getType())) {
            BigIntType::class, IntegerType::class, SmallIntType::class                                                                    => 'integer',
            JsonType::class, ArrayType::class, SimpleArrayType::class                                                                     => 'array',
            AsciiStringType::class, StringType::class, BinaryType::class, GuidType::class, TextType::class                                => 'string',
            BooleanType::class                                                                                                            => 'boolean',
            TimeType::class, DateIntervalType::class, DateTimeType::class, DateTimeTzType::class, VarDateTimeType::class, DateType::class => 'date',
            DecimalType::class, FloatType::class                                                                                          => 'numeric',
            default                                                                                                                       => null
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

    private function makeRequestCommand(): self
    {
        $nameSpace = $this->dto->getNamespace() ? $this->dto->getNamespace() . '/' : '';
        $result = Artisan::call('make:request', [
            'name'    => "$nameSpace{$this->dto->getBaseModelName()}Request",
            '--force' => true,
        ]);

        if ($result !== 0) {
            throw new Exception('Request command failed');
        }

        return $this;
    }

    private function generateRules(): self
    {
        $this->generated = ModuleGenerator::make($this->rules)->generateRequestRules();

        return $this;
    }

    private function addRulesToRequestFile(): self
    {
        $requestFile = File::get(app_path("Http/Requests/{$this->dto->getNamespace()}/{$this->dto->getBaseModelName()}Request.php"));

        $requestFile = str_replace(
            'return [',
            "return [\n\t\t\t$this->generated,",
            $requestFile
        );

        File::put(app_path("Http/Requests/{$this->dto->getNamespace()}/{$this->dto->getBaseModelName()}Request.php"), $requestFile);

        return $this;
    }
}
