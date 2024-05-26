<?php

namespace Loffy\CreateLaravelModule\Support;

use Doctrine\DBAL\Schema\AbstractAsset;

class ColumnSupport
{
    public static function isIgnored(AbstractAsset $column): bool
    {
        return in_array($column->getName(), ['id', 'created_at', 'updated_at']);
    }
}
