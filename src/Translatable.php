<?php

namespace Spatie\NovaTranslatable;

use Closure;
use Laravel\Nova\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Spatie\NovaTranslatable\Exceptions\InvalidConfiguration;

class Translatable extends MergeValue
{
    /** @var string[] */
    protected static $defaultLocales = [];

    /** @var \Closure|null */
    protected static $displayLocalizedNameByDefaultUsingCallback;

    /** @var string[] */
    protected $locales = [];

    /** @var \Laravel\Nova\Fields\Field[] */
    protected $originalFields;

    /** @var \Closure */
    protected $displayLocalizedNameUsingCallback;

    public static function make(array $fields): self
    {
        return new static($fields);
    }

    public function __construct(array $fields = [])
    {
        if (! count(static::$defaultLocales)) {
            throw InvalidConfiguration::defaultLocalesNotSet();
        }

        $this->locales = static::$defaultLocales;

        $this->originalFields = $fields;

        $this->displayLocalizedNameUsingCallback = self::$displayLocalizedNameByDefaultUsingCallback ?? function (Field $field, string $locale) {
            return ucfirst($field->name)." ({$locale})";
        };

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

    public static function displayLocalizedNameByDefaultUsing(Closure $displayLocalizedNameByDefaultUsingCallback)
    {
        static::$displayLocalizedNameByDefaultUsingCallback = $displayLocalizedNameByDefaultUsingCallback;
    }

    public function displayLocalizedNameUsing(Closure $displayLocalizedNameUsingCallback)
    {
        $this->displayLocalizedNameUsingCallback = $displayLocalizedNameUsingCallback;

        $this->createTranslatableFields();

        return $this;
    }

    protected function createTranslatableFields()
    {
        if ($this->onIndexPage()) {
            $this->data = $this->originalFields;

            return;
        }

        $this->data = [];

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
                $translatedField->attribute = 'translations_'.$originalAttribute.'_'.$locale;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->attribute = 'translations';

        $translatedField->name = (count($this->locales) > 1)
            ? ($this->displayLocalizedNameUsingCallback)($translatedField, $locale)
            : $translatedField->name;

        $translatedField->fillUsing(function ($request, $model, $attribute, $requestAttribute) {
            $requestAttributeParts = explode('_', $requestAttribute);
            $locale = array_last($requestAttributeParts);

            array_shift($requestAttributeParts);
            array_pop($requestAttributeParts);

            $key = implode('_', $requestAttributeParts);

            $model->setTranslation($key, $locale, $request->get($requestAttribute));
        });

        return $translatedField;
    }

    protected function onIndexPage(): bool
    {
        if (! request()->route()) {
            return false;
        }

        $currentController = str_before(request()->route()->getAction()['controller'], '@');

        return $currentController === ResourceIndexController::class;
    }
}
