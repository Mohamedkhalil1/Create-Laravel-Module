<?php

namespace Loffy\CreateLaravelModule\Modules\Request\Mapper;

use Doctrine\DBAL\Schema\Column;
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
use Illuminate\Support\Collection;
use Loffy\CreateLaravelModule\Support\ColumnSupport;

class ColumnToRequestMapper
{
    private Collection $currentRules;

    public function __construct(private readonly Column $column)
    {
        $this->currentRules = new Collection();
    }

    public static function make(Column $column): ColumnToRequestMapper
    {
        return new self($column);
    }

    public function handle(): array
    {
        return $this->setRules()->toArray();
    }

    private function setRules(): self
    {
        if (ColumnSupport::isIgnored($this->column)) {
            return $this;
        }
        $this
            ->setColumnConstraints()
            ->setColumnTypeRules()
            ->setDefaultRules()
            ->setNameRules();

        return $this;
    }

    private function toArray(): array
    {
        if (ColumnSupport::isIgnored($this->column)) {
            return [];
        }

        return [
            $this->column->getName() => $this->currentRules->filter()->all(),
        ];
    }

    private function setColumnConstraints(): self
    {
        $this->currentRules->push($this->column->getNotnull() ? 'required' : 'nullable');
        $this->currentRules->push($this->column->getUnsigned() ? 'unsigned' : null);

        return $this;
    }

    private function setColumnTypeRules(): self
    {
        $this->columnTypeAsRule = match (get_class($this->column->getType())) {
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

    private function setDefaultRules(): self
    {
        $columnTypes = config('module.request.defaults');

        $typeRules = $columnTypes[$this->columnTypeAsRule] ?? null;

        if (! $typeRules) {
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

        $typeRules = $columnNames[$this->column->getName()] ?? null;

        if (! $typeRules) {
            return $this;
        }

        foreach ($typeRules as $rule) {
            $this->currentRules->push($rule);
        }

        return $this;
    }
}
