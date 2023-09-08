<?php

namespace Loffy\CreateLaravelModule\Modules;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;

class RequestModule
{
    private $rules;

    private array $currentRules;

    public function __construct(private ModuleDTO $dto)
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
        $request = File::get(__DIR__.'/../Commands/stubs/DummyRequest.stub');
        $request = str_replace('DummyNamespace', $this->dto->getNamespace(), $request);
        $request = str_replace('DummyRequest', "{$this->dto->getBaseModelName()}Request", $request);
        $request = str_replace('Rules', $this->rules, $request);
        if (File::exists($requestDir."/$requestName.php")) {
            throw new Exception("Request $requestName already exist in $requestDir!");
        }
        if (! File::exists($requestDir)) {
            File::makeDirectory($requestDir, recursive: true);
        }
        File::put($requestDir."/$requestName.php", $request);
    }

    private function setRules(): void
    {
        $this->rules = $this->dto->getColumns()->map(function ($column) {
            $this->setColumnRules($column);

            return "            '$column->COLUMN_NAME' => [".implode(', ', $this->currentRules).'],';
        })
            ->join(PHP_EOL);
    }

    private function setColumnRules($column): void
    {
        $this->currentRules = [$column->IS_NULLABLE == 'NO' ? "'required'" : "'nullable'"];

        if (Str::startsWith($column->COLUMN_TYPE, 'varchar')) {
            $this->stringRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'text')) {
            $this->textRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'longtext')) {
            $this->LongTextRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'int') || Str::startsWith($column->COLUMN_TYPE, 'bigint')) {
            $this->integerRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'tinyint')) {
            $this->tinyIntRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'decimal')) {
            $this->decimalRule();
        }
        if (Str::startsWith($column->COLUMN_TYPE, 'date')) {
            $this->dateRule();
        }

        if (Str::startsWith($column->COLUMN_NAME, 'email')) {
            $this->emailRule();
        }
        if (Str::endsWith($column->COLUMN_NAME, '_id')) {
            $this->foreignKeyRule($column);
        }
    }

    private function emailRule(): void
    {
        $this->rules[] = "'email'";
    }

    private function stringRule(): void
    {
        $this->currentRules[] = "'string'";
        $this->currentRules[] = "'max:255'";
    }

    private function textRule()
    {
        $this->currentRules[] = "'string'";
        $this->currentRules[] = "'max:65000'";
    }

    private function LongTextRule(): void
    {
        $this->currentRules[] = "'string'";
        $this->currentRules[] = "'max:4000000000'";
    }

    private function integerRule(): void
    {
        $this->currentRules[] = "'integer'";
        $this->currentRules[] = "'min:0'";
        $this->currentRules[] = "'max:2000000000'";
    }

    private function tinyIntRule(): void
    {
        $this->currentRules[] = "'integer'";
        $this->currentRules[] = "'min:0'";
        $this->currentRules[] = "'max:255'";
    }

    private function decimalRule(): void
    {
        $this->currentRules[] = "'numeric'";
        $this->currentRules[] = "'min:0'";
        $this->currentRules[] = "'max:999999.99'";
    }

    private function dateRule(): void
    {
        $this->currentRules[] = "'date'";
    }

    private function foreignKeyRule($column): void
    {
        $table = Str::plural(Str::replaceLast('_id', '', $column->COLUMN_NAME));
        $this->currentRules[] = "'exists:$table,id'";
    }
}
