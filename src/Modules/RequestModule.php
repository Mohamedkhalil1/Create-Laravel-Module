<?php

namespace Loffy\CreateLaravelModule\Modules;

use Doctrine\DBAL\Schema\Column;
use Exception;
use Illuminate\Support\Facades\File;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
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
use Doctrine\DBAL\Types\ObjectType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\VarDateTimeType;
use Illuminate\Support\Collection;

class RequestModule
{
    private $rules;

    private Collection $currentRules;
    private Column $currentColumn;
    private string $columnTypeAsRule;

    public function __construct(private readonly ModuleDTO $dto)
    {
    }

    public static function make(ModuleDTO $dto): static
    {
        return new static($dto);
    }

    public function handle(): void
    {
        $this->setRules();

        $requestName = "{$this->dto->getBaseModelName()}Request";
        $requestDir = base_path("app/Http/Requests/{$this->dto->getNamespace()}");
        $request = File::get(__DIR__ . '/../Commands/stubs/DummyRequest.stub');
        $request = str_replace('DummyNamespace', $this->dto->getNamespace(), $request);
        $request = str_replace('DummyRequest', "{$this->dto->getBaseModelName()}Request", $request);
        $request = str_replace('Rules', $this->rules, $request);
        if (File::exists($requestDir . "/$requestName.php")) {
            throw new Exception("Request $requestName already exist in $requestDir!");
        }
        if (!File::exists($requestDir)) {
            File::makeDirectory($requestDir, recursive: true);
        }
        File::put($requestDir . "/$requestName.php", $request);
    }

    private function setRules(): void
    {
        $this->rules = $this->dto->getColumns()->mapWithKeys(function (Column $column) {
            $this->currentRules = new Collection();
            $this->currentColumn = $column;
            $this
                ->setColumnTypeRules()
                ->setColumnConstraints()
                ->setDefaultRules();

            return [
                $this->currentColumn->getName() => $this->currentRules->filter()->all()
            ];
        });
        dd($this->rules);
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

        $this->currentRules->push($columnTypes[$this->columnTypeAsRule] ?? null);

        return $this;
    }
}
