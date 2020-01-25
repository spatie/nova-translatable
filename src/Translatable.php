<?php

namespace Spatie\NovaTranslatable;

use Closure;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Spatie\NovaTranslatable\Exceptions\InvalidConfiguration;

class Translatable extends MergeValue
{
    /** @var string[] */
    protected static $defaultLocales = [];

    /** @var string */
    protected static $defaultSortLocale;

    /** @var \Closure|null */
    protected static $displayLocalizedNameByDefaultUsingCallback;

    /** @var string[] */
    protected $locales = [];

    /** @var \Laravel\Nova\Fields\Field[] */
    protected $originalFields;

    /** @var \Closure */
    protected $displayLocalizedNameUsingCallback;

    /** @var string */
    protected $sortLocale;

    /**
     * The field's assigned panel.
     *
     * @var string
     */
    public $panel;

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
        $this->sortLocale = static::$defaultSortLocale;

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

    public static function defaultSortLocale(string $locale)
    {
        static::$defaultSortLocale = $locale;
    }

    public function locales(array $locales)
    {
        $this->locales = $locales;

        $this->createTranslatableFields();

        return $this;
    }

    public function sortLocale(string $locale)
    {
        $this->sortLocale = $locale;

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
        $this->data = [];

        if ($this->onIndexPage()) {
            foreach ($this->originalFields as $field) {
                $this->data[] = $this->createIndexField($field);
            }

            return;
        }

        collect($this->locales)
            ->crossJoin($this->originalFields)
            ->eachSpread(function (string $locale, Field $field) {
                $this->data[] = $this->createTranslatedField($field, $locale);
            });
    }

    protected function createIndexField(Field $field): Field
    {
        if(! $field->sortable){
            return $field;
        }

        $field = clone $field;

        if ($this->sortLocale) {
            $field->meta['sortableUriKey'] = $field->meta['sortableUriKey'] ?? $field->attribute.'->'.$this->sortLocale;
        } else {
            $field->sortable = false;
        }

        return $field;
    }

    protected function createTranslatedField(Field $originalField, string $locale): Field
    {
        $translatedField = clone $originalField;

        $originalAttribute = $translatedField->attribute;

        $translatedField->attribute = 'translations';

        $translatedField->name = (count($this->locales) > 1)
            ? ($this->displayLocalizedNameUsingCallback)($translatedField, $locale)
            : $translatedField->name;

        $translatedField
            ->resolveUsing(function ($value, Model $model) use ($translatedField, $locale, $originalAttribute) {
                $translatedField->attribute = 'translations_'.$originalAttribute.'_'.$locale;
                $translatedField->panel = $this->panel;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute) {
            $model->setTranslation($originalAttribute, $locale, $request->get($requestAttribute));
        });

        return $translatedField;
    }

    protected function onIndexPage(): bool
    {
        if (! request()->route()) {
            return false;
        }

        $currentController = Str::before(request()->route()->getAction()['controller'], '@');

        return $currentController === ResourceIndexController::class;
    }
}
