<?php

namespace Spatie\TranslatableField;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\TranslatableField\Http\Middleware\Authorize;

class TranslatableFieldServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('nova-translatable-field', __DIR__.'/../dist/js/field.js');
            Nova::style('nova-translatable-field', __DIR__.'/../dist/css/field.css');
        });
    }
}
