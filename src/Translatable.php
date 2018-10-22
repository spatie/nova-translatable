<?php

namespace Spatie\NovaTranslatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Laravel\Nova\Nova;
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
        if ($this->onIndexPage()) {
            $this->data = $this->originalFields;

            return;
        }

        collect($this->locales)
            ->crossJoin($this->originalFields)
            ->eachSpread(function (string $locale, Field $field) {
                $this->data[] = $this->createTranslatedField($field, $locale);
            });
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $originalAttribute = $translatedField->attribute;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale, $originalAttribute) {
                $translatedField->attribute = 'translations_' . $originalAttribute . '_' . $locale;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->attribute = 'translations';

        $translatedField->name = ucfirst($translatedField->name) . " ({$locale})";

        $translatedField->fillUsing(function($request, $model, $attribute, $requestAttribute) {
            [$_, $key, $locale] = explode('_', $requestAttribute);

            $model->setTranslation($key, $locale, $request->get($requestAttribute));
        });

        return $translatedField;
    }

    protected function onIndexPage(): bool
    {
        $currentController = str_before(request()->route()->getAction()['controller'], '@');

        return $currentController === ResourceIndexController::class;
    }
}
