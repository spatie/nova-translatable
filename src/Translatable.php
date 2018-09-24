<?php

namespace Spatie\TranslatableField;

use Spatie\Tags\Tag;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Translatable extends Field
{
    public $component = 'nova-translatable-field';

    protected static $defaultLocales = [];

    protected $locales = [];

    protected $fields  = [];

    public static function make(array $fields): self
    {
        return new static($fields);
    }

    public function __construct(array $fields = [])
    {
        $this->fields = $fields;

        $this->locales(static::$defaultLocales);
    }

    public function locales(array $locales)
    {
        $this->locales = $locales;

        return $this;
    }

    public static function defaultLocales(array $defaultLocales)
    {
        static::$defaultLocales = $defaultLocales;
    }
}
