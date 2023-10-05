<?php

namespace Loffy\CreateLaravelModule\Modules\Route;

use Illuminate\Support\Facades\File;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;

class RouteModule
{
    public function __construct(private ModuleDTO $dto)
    {
    }

    public static function make(ModuleDTO $dto): static
    {
        return new static($dto);
    }

    public function handle(): void
    {
        $routes = [
            "Route::resource('{$this->dto->getSingularSnakeCaseTitle()}', \\App\\Http\\Controllers\\{$this->dto->getNamespace()}\\{$this->dto->getBaseModelName()}Controller::class);", ];
        File::append(base_path('routes/api.php'), PHP_EOL.implode(PHP_EOL, $routes));
    }
}
