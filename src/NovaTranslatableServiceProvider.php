<?php

namespace Spatie\NovaTranslatable;

use Laravel\Nova\Fields\Field;
use Illuminate\Validation\Rule;
use Illuminate\Support\ServiceProvider;

class NovaTranslatableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Field::macro('fallbackLocaleRules', function ($rules) {
            $this->fallbackLocaleRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

            return $this;
        });

        Field::macro('creationFallbackLocaleRules', function ($rules) {
            $this->creationFallbackLocaleRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

            return $this;
        });

        Field::macro('updateFallbackLocaleRules', function ($rules) {
            $this->updateFallbackLocaleRules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

            return $this;
        });
    }
}
