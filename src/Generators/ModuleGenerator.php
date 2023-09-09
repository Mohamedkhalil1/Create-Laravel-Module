<?php

namespace Loffy\CreateLaravelModule\Generators;

use Illuminate\Support\Collection;

class ModuleGenerator
{

    private Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public static function make(Collection $data): self
    {
        return new self($data);
    }

    public function generateRequestRules()
    {
        return $this->data
            ->map(fn(array $rules, string $ruleName) => "'$ruleName' => " . "['" . implode("', '", $rules) . "']")
            ->implode(", \n\t\t\t");
    }
}
