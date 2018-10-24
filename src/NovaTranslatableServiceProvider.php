<?php

namespace Spatie\NovaTranslatable;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;

class NovaTranslatableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            // Nova::script('nova-translatable', __DIR__.'/../dist/js/field.js');
            // Nova::style('nova-translatable', __DIR__.'/../dist/css/field.css');
        });
    }
}
