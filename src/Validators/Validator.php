<?php

namespace Loffy\CreateLaravelModule\Validators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Validator
{
    public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public function getErrorFiles()
    {
        $files = [
            base_path('routes/api.php'),
        ];
        return collect($files)
            ->filter(fn ($file) => !File::exists($file))
            ->map(fn ($file) => ' - ' . str_replace(base_path(), '', $file))
            ->all();
    }

    public function getModel($model): bool|string
    {
        $model = str_replace('/', '\\', $model);
        if (!Str::startsWith($model, 'App\\Models\\')) {
            $model = "App\\Models\\$model";
        }
        if (!class_exists($model)) {
            return false;
        }
        return $model;
    }
}
