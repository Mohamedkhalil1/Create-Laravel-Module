<?php

namespace Loffy\CreateLaravelModule\Modules\Request\Generator;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class RequestGenerator
{
    private Collection $data;
    private Collection $imports;
    private readonly string $generated;

    public function __construct(Collection $data)
    {
        $this->data = $data;
        $this->imports = new Collection();
    }

    public static function make(Collection $data): self
    {
        return new self($data);
    }

    public function generate(): self
    {
        $this->generated = $this->data
            ->map(fn (array $rules, string $ruleName) => $this->generateRule($rules, $ruleName))
            ->implode(", \n\t\t\t");

        return $this;
    }

    private function generateRule(array $rules, string $ruleName): string
    {
        $rules = collect($rules)->map(function (string $rule){
            if (str_contains($rule, 'Rule::')) {
                $this->imports->push('use Illuminate\Validation\Rule;');
                return $rule;
            }
            return "'$rule'";
        })->all();

        return "'$ruleName' => "."[" . implode(", ", $rules)."]";
    }

    public function addRulesToRequestFile(string $fullQualifiedRequest): self
    {
        $requestFile = File::get($fullQualifiedRequest);

        $requestFile = str_replace(
            'return [',
            "return [\n\t\t\t$this->generated,",
            $requestFile
        );

        $requestFile = str_replace(
            'use Illuminate\Foundation\Http\FormRequest;',
            "use Illuminate\Foundation\Http\FormRequest;\n{$this->imports->unique()->implode("\n")}",
            $requestFile
        );

        File::put($fullQualifiedRequest, $requestFile);

        return $this;
    }

}
