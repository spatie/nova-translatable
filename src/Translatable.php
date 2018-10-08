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
        $this->locales(static::$defaultLocales);

        $this->originalFields = $fields;

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
        collect($this->locales)->each(function (string $locale) {
            collect($this->originalFields)->each(function (Field $field) use ($locale) {
                $this->data[] = $this->createTranslatedField($field, $locale);
            });
        });
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale) {
                return $model->getTranslation($translatedField->attribute, $locale);
            });

        $translatedField->attribute = $translatedField->attribute . '_' . $locale;
        $translatedField->name = $translatedField->name . " ({$locale})";

        /*
          $translatedField->fillUsing(function(NovaRequest $request, $requestAttribute, $model, $attribute) {
             // dd('fillusing', $request->all(), $requestAttribute,$model, $attribute);
          });
        */

        return $translatedField;
    }
}
