<?php

namespace Spatie\NovaTranslatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Fields\Field;
use Spatie\Tags\Tag;

class Translatable extends MergeValue
{
    public $component = 'nova-translatable';

    protected static $defaultLocales = [];

    /** @var array \Laravel\Nova\Fields\Field[] */
    protected $originalFields;

    protected $locales = [];

    public static function make(array $fields): self
    {
        return new static($fields);
    }

    public function __construct(array $fields = [])
    {
        $this->originalFields = $fields;

        $this->locales = static::$defaultLocales;

        $this->createTranslatableFields();
    }

    public static function defaultLocales(array $locales)
    {
        static::$defaultLocales = $locales;
    }

    public function locales(array $locales)
    {
        $this->locales = $locales;

        $this->createTranslatableFields();

        return $this;
    }

    protected function createTranslatableFields()
    {
        collect($this->locales)
            ->crossJoin($this->originalFields)
            ->eachSpread(function (string $locale, Field $field) {
                $this->data[] = $this->createTranslatedField($field, $locale);
            });
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale) {
                return $model->getTranslation($translatedField->attribute, $locale);
            });

        $translatedField->attribute = 'translations.' . $translatedField->attribute . '.' . $locale;

        $translatedField->name = $translatedField->name . " ({$locale})";

        $translatedField->fillUsing(function($request, $model, $attribute, $requestAttribute) {
            [$_, $key, $locale] = explode('.', $attribute);

            $model->setTranslation($key, $locale, $request->get('translations.' . $key . '.' . $locale));
        });

        return $translatedField;
    }
}
