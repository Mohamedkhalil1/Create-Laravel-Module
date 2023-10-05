<?php

namespace Loffy\CreateLaravelModule\Generators;

use Illuminate\Support\Collection;

class ModuleGenerator
{
    private Collection $data;

    private Collection $imports;

    private string $generated;

    public function __construct(Collection $data)
    {
        $this->data = $data;
        $this->imports = new Collection();
    }

    public static function make(Collection $data): self
    {
        return new self($data);
    }

    public function generateRequestRules(): self
    {
        $this->generated = $this->data
            ->map(fn (array $rules, string $ruleName) => $this->generateRule($rules, $ruleName))
            ->implode(", \n\t\t\t");

        return $this;
    }

    private function generateRule(array $rules, string $ruleName): string
    {
        $rules = collect($rules)->map(function (string $rule) {
            if (str_contains($rule, 'Rule::')) {
                $this->imports->push('use Illuminate\Validation\Rule;');

                return $rule;
            }

            return "'$rule'";
        })->all();

        return "'$ruleName' => ".'['.implode(', ', $rules).']';
    }

    public function getGenerated(): string
    {
        return $this->generated;
    }

    public function getImports(): string
    {
        return $this->imports->unique()->implode("\n");
    }
}
