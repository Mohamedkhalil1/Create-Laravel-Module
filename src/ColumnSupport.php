<?php

namespace Loffy\CreateLaravelModule;

use Doctrine\DBAL\Schema\Column;

class ColumnSupport
{

    public static function isIgnored(Column $column): bool
    {
        return in_array($column->getName(), ['id', 'created_at', 'updated_at']);
    }
}
