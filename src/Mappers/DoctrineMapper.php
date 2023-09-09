<?php

namespace Loffy\CreateLaravelModule\Mappers;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DoctrineMapper
{
    private string $tableName;

    private Table $doctrineTableDetails;

    private AbstractSchemaManager $schema;

    protected Collection $columns;

    protected Collection $indexes;

    protected Collection $foreignKeys;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;

        $this->schema = Schema::getConnection()->getDoctrineSchemaManager();

        $this
            ->setDoctrineTableDetails()
            ->initializeMapper();

    }

    public static function make(string $tableName): self
    {
        return new self($tableName);
    }

    private function setDoctrineTableDetails(): self
    {
        $this->doctrineTableDetails = $this->schema->listTableDetails($this->tableName);

        return $this;
    }

    private function initializeMapper(): self
    {
        $this->foreignKeys = collect($this->doctrineTableDetails->getForeignKeys());

        $this->columns = collect($this->doctrineTableDetails->getColumns())
            ->reject(fn ($column) => $this->foreignKeys->contains(fn (ForeignKeyConstraint $foreignKey) => $foreignKey->getLocalColumns()[0] === $column->getName()));

        return $this;
    }

    public function getForeignKeys(): Collection
    {
        return $this->foreignKeys;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }
}
