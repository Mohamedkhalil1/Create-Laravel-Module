<?php

namespace Loffy\CreateLaravelModule\Commands;

use Illuminate\Console\Command;
use Loffy\CreateLaravelModule\DTOs\ModuleDTO;
use Loffy\CreateLaravelModule\Modules\Controller\ControllerModule;
use Loffy\CreateLaravelModule\Modules\MasterModule;
use Loffy\CreateLaravelModule\Modules\Request\RequestModule;
use Loffy\CreateLaravelModule\Modules\Resource\ResourceModule;
use Loffy\CreateLaravelModule\Modules\Route\RouteModule;
use Loffy\CreateLaravelModule\Validators\Validator;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {model}';

    protected $description = 'Make module';
    protected ModuleDTO $dto;

    protected Validator $validator;

    public function handle(): int
    {
        $this->validator = Validator::make();
        $this->dto = new ModuleDTO(resolve($this->validator->getModel($this->argument('model'))));
        if ($this->hasErrors()) {
            return self::FAILURE;
        }
        MasterModule::make($this->dto)->handle();

        $this->info('Module created successfully :)');
        $this->info('Don\'t forget to add columns translations in validation.php');

        return self::SUCCESS;
    }

    private function hasErrors(): bool
    {
        $validator = Validator::make();

        if (!empty($validator->getErrorFiles())) {
            $this->warn('Please make sure the following files exist!');
            $this->line(implode(PHP_EOL, $validator->getErrorFiles()));

            return true;
        }

        if (!$validator->getModel($this->argument('model'))) {
            $this->warn('Model not found!');

            return true;
        }

        return false;
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
