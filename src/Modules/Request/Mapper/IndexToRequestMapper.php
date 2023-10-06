<?php

namespace Loffy\CreateLaravelModule\Modules\Request\Mapper;

use Doctrine\DBAL\Schema\Index;
use Illuminate\Support\Collection;

class IndexToRequestMapper
{
    private Collection $currentRules;

    public function __construct(private readonly Index $index, private readonly string $table)
    {
        $this->currentRules = new Collection();
    }

    public static function make(Index $index, string $table): IndexToRequestMapper
    {
        return new self($index, $table);
    }

    public function handle(): array
    {
        return $this->setRules()->toArray();
    }

    private function setRules(): self
    {
        if ($this->index->isPrimary()) {
            $this->currentRules->push("Rule::exists('{$this->table}' , '{$this->index->getColumns()[0]}')");

            return $this;
        }
        $this->currentRules->push("Rule::unique('{$this->table}' , '{$this->index->getColumns()[0]}')");

        return $this;
    }

    private function toArray(): array
    {
        return [
            $this->index->getColumns()[0] => $this->currentRules->filter()->all(),
        ];
    }
}
