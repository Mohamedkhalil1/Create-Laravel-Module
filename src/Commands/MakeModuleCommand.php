<?php

namespace Loffy\CreateLaravelModule\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {model}';

    protected $description = 'Make module';

    private string $model;

    private string $baseModelName;

    private string $namespace;

    private string $pluralBaseModelName;

    private Collection $columns;

    private array $newTranslationWords = [];

    private Stringable $snakeCaseTitle;

    private Stringable $singularSnakeCaseTitle;

    private Stringable $title;

    private Stringable $titleSingular;

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        if (! $this->checkConfig()) {
            return self::FAILURE;
        }
        $this->model = $this->getModel();
        if (! $this->model) {
            return self::FAILURE;
        }
        $this->baseModelName = class_basename($this->model);
        $this->namespace = $this->getNamespace();
        $this->pluralBaseModelName = Str::plural($this->baseModelName);
        $this->columns = $this->getColumns();
        $this->snakeCaseTitle = str($this->pluralBaseModelName)->snake(' ');
        $this->singularSnakeCaseTitle = str($this->pluralBaseModelName)->snake()->singular();
        $this->title = $this->snakeCaseTitle->headline();
        $this->titleSingular = $this->title->singular();
        $this->makeController();
        $this->makeRequest();
        $this->addRoutes();
        $this->addTranslations();
        $this->info('Module created successfully :)');
        $this->info('Don\'t forget to add columns translations in validation.php');

        return self::SUCCESS;
    }

    private function checkConfig(): bool
    {
        $files = [
            base_path('routes/api.php'),
        ];
        $files = collect($files)
            ->filter(fn ($file) => ! File::exists($file))
            ->map(fn ($file) => ' - '.str_replace(base_path(), '', $file))
            ->all();
        if (empty($files)) {
            return true;
        }
        $this->warn('Please make sure the following files exist!');
        $this->line(implode(PHP_EOL, $files));

        return false;
    }

    /**
     * @throws Exception
     */
    private function getModel(): string
    {
        $model = $this->argument('model');
        $model = str_replace('/', '\\', $model);
        if (! Str::startsWith($model, 'App\\Models\\')) {
            $model = "App\\Models\\$model";
        }
        if (! class_exists($model)) {
            $this->error("Model $model not found");

            return false;
        }

        return $model;
    }

    private function getNamespace(): string
    {
        $model = $this->model;
        $parts = explode('\\', $model);
        $namespace = $parts[count($parts) - 2];

        return $namespace == 'Models' ? '' : $namespace;
    }

    public function getColumns(): Collection
    {
        return DB::table('information_schema.COLUMNS')
            ->select('COLUMN_NAME', 'IS_NULLABLE', 'COLUMN_TYPE')
            ->where('TABLE_SCHEMA', '=', DB::getDatabaseName())
            ->where('TABLE_NAME', '=', (new $this->model)->getTable())
            ->whereNotIn('COLUMN_NAME', ['id', 'created_at', 'updated_at', 'deleted_at'])
            ->orderBy('ORDINAL_POSITION')
            ->get();
    }

    private function makeController(): void
    {
        $controllerName = "{$this->baseModelName}Controller";
        $controllerDir = base_path("app/Http/Controllers/{$this->namespace}");
        $controller = File::get(__DIR__ . '/stubs/DummyController.stub');
        $controller = str_replace('DummyNamespace', $this->namespace, $controller);
        $controller = str_replace('DummyRequest', "{$this->baseModelName}Request", $controller);
        $controller = str_replace('FullyQualifiedDummyModel', $this->model, $controller);
        $controller = str_replace('DummyController', $controllerName, $controller);
        $controller = str_replace('dummies', $this->snakeCaseTitle, $controller);
        $controller = str_replace('Dummies', $this->title, $controller);
        $controller = str_replace('dummy', $this->singularSnakeCaseTitle, $controller);
        $controller = str_replace('DummyTitle', $this->titleSingular, $controller);
        $controller = str_replace('camelCaseDummy', str($this->baseModelName)->camel(), $controller);
        $controller = str_replace('Dummy', $this->baseModelName, $controller);
        if (File::exists($controllerDir."/$controllerName.php")) {
            throw new Exception("Controller $controllerName already exist in $controllerDir!");
        }
        if (! File::exists($controllerDir)) {
            File::makeDirectory($controllerDir, recursive: true);
        }
        File::put($controllerDir."/$controllerName.php", $controller);
    }

    private function makeRequest(): void
    {
        $requestName = "{$this->baseModelName}Request";
        $requestDir = base_path("app/Http/Requests/{$this->namespace}");
        $rules = $this->columns->map(function ($column) {
            $rules = [$column->IS_NULLABLE == 'NO' ? "'required'" : "'nullable'"];
            if ($column->COLUMN_NAME == 'email') {
                $rules[] = "'email'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'varchar')) {
                $rules[] = "'string'";
                $rules[] = "'max:255'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'text')) {
                $rules[] = "'string'";
                $rules[] = "'max:65000'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'longtext')) {
                $rules[] = "'string'";
                $rules[] = "'max:4000000000'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'int') || Str::startsWith($column->COLUMN_TYPE, 'bigint')) {
                $rules[] = "'integer'";
                $rules[] = "'min:0'";
                $rules[] = "'max:2000000000'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'tinyint')) {
                $rules[] = "'integer'";
                $rules[] = "'min:0'";
                $rules[] = "'max:255'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'decimal')) {
                $rules[] = "'numeric'";
                $rules[] = "'min:0'";
                $rules[] = "'max:999999.99'";
            }
            if (Str::startsWith($column->COLUMN_TYPE, 'date')) {
                $rules[] = "'date'";
            }
            if (Str::endsWith($column->COLUMN_NAME, '_id')) {
                $table = Str::plural(Str::replaceLast('_id', '', $column->COLUMN_NAME));
                $rules[] = "'exists:$table,id'";
            }

            return "            '$column->COLUMN_NAME' => [".implode(', ', $rules).'],';
        })
            ->join(PHP_EOL);
        $request = File::get(__DIR__.'/stubs/DummyRequest.stub');
        $request = str_replace('DummyNamespace', $this->namespace, $request);
        $request = str_replace('DummyRequest', "{$this->baseModelName}Request", $request);
        $request = str_replace('Rules', $rules, $request);
        if (File::exists($requestDir."/$requestName.php")) {
            throw new Exception("Request $requestName already exist in $requestDir!");
        }
        if (! File::exists($requestDir)) {
            File::makeDirectory($requestDir, recursive: true);
        }
        File::put($requestDir."/$requestName.php", $request);
    }

    private function addRoutes(): void
    {
        $routes = [
            "Route::resource('{$this->singularSnakeCaseTitle}', \\App\\Http\\Controllers\\{$this->namespace}\\{$this->baseModelName}Controller::class);",];
        File::append(base_path('routes/api.php'), PHP_EOL . implode(PHP_EOL, $routes));
    }

    private function addTranslations(): void
    {
//        $this->newTranslationWords = array_merge($this->newTranslationWords, [
//            $this->title->toString(),
//        ]);
//        $translations = [];
//        $langDir = File::exists(resource_path('lang')) ? resource_path('lang') : base_path('lang');
//        if (File::exists("$langDir/ar.json")) {
//            $translations = json_decode(File::get("$langDir/ar.json"), true);
//        }
//        foreach ($this->newTranslationWords as $word) {
//            if (!array_key_exists($word, $translations)) {
//                $translations[$word] = '';
//            }
//        }
//        File::put("$langDir/ar.json", json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
