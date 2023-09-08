<?php


use Illuminate\Support\Facades\Artisan;

it('makes module', function () {
    Artisan::call('make:module', ['model' => 'User']);

    dd(Artisan::output());
});
