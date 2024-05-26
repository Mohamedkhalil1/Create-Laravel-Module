<?php

namespace Loffy\CreateLaravelModule\Modules\Request\Mapper;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Support\Collection;

class ForeignKeyToRequestMapper
{
    private Collection $currentRules;

    public function __construct(private readonly ForeignKeyConstraint $foreignKey)
    {
        $this->currentRules = new Collection();
    }

    public static function make(ForeignKeyConstraint $foreignKey): ForeignKeyToRequestMapper
    {
        return new self($foreignKey);
    }

    public function handle(): array
    {
        return $this->setRules()->toArray();
    }

    private function setRules(): self
    {
        $this->currentRules->push("Rule::exists('{$this->foreignKey->getForeignTableName()}','{$this->foreignKey->getForeignColumns()[0]}')");

        return $this;
    }

    private function toArray(): array
    {
        return [
            $this->foreignKey->getLocalColumns()[0] => $this->currentRules->filter()->all(),
        ];
    }
}
