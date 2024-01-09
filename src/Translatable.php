<?php

namespace Spatie\NovaTranslatable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Field;
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

    /** @var array */
    protected $rules = [];

    /** @var array */
    protected $creationRules = [];

    /** @var array */
    protected $updateRules = [];

    /** @var array */
    protected $translatedFieldsByLocale = [];

    /**
     * The field's assigned panel.
     *
     * @var string
     */
    public $panel;

    /**
     * The field's assigned panel.
     *
     * @var \Laravel\Nova\Panel|null
     */
    public $assignedPanel;

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

    public function rules(array $rules)
    {
        $this->rules = $rules;

        $this->createTranslatableFields();

        return $this;
    }

    public function creationRules(array $rules)
    {
        $this->creationRules = $rules;

        $this->createTranslatableFields();

        return $this;
    }

    public function updateRules(array $rules)
    {
        $this->updateRules = $rules;

        $this->createTranslatableFields();

        return $this;
    }

    public function rulesFor(string $field, string $locale, $rules)
    {
        $this->rules[$field][$locale] = $rules;

        $this->createTranslatableFields();

        return $this;
    }

    public function creationRulesFor(string $field, string $locale, $rules)
    {
        $this->creationRules[$field][$locale] = $rules;

        $this->createTranslatableFields();

        return $this;
    }

    public function updateRulesFor(string $field, string $locale, $rules)
    {
        $this->updateRules[$field][$locale] = $rules;

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
                $translatedField = $this->createTranslatedField($field, $locale);

                $this->data[] = $translatedField;
                $this->translatedFieldsByLocale[$locale][] = $translatedField;
            });
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
                $translatedField->panel = $translatedField->panel ?? $this->panel;
                $translatedField->assignedPanel = $translatedField->assignedPanel ?? $this->assignedPanel;

                return $model->translations[$originalAttribute][$locale] ?? '';
            });

        $translatedField->fillUsing(function ($request, $model, $attribute, $requestAttribute) use ($locale, $originalAttribute) {
            $model->setTranslation($originalAttribute, $locale, $request->get($requestAttribute));
        });

        if (isset($this->rules[$originalAttribute][$locale])) {
            $translatedField->rules(
                is_string($this->rules[$originalAttribute][$locale])
                    ? explode('|', $this->rules[$originalAttribute][$locale])
                    : $this->rules[$originalAttribute][$locale]
            );
        }
        if (isset($this->creationRules[$originalAttribute][$locale])) {
            $translatedField->creationRules(
                is_string($this->creationRules[$originalAttribute][$locale])
                    ? explode('|', $this->creationRules[$originalAttribute][$locale])
                    : $this->creationRules[$originalAttribute][$locale]
            );
        }
        if (isset($this->updateRules[$originalAttribute][$locale])) {
            $translatedField->updateRules(
                is_string($this->updateRules[$originalAttribute][$locale])
                    ? explode('|', $this->updateRules[$originalAttribute][$locale])
                    : $this->updateRules[$originalAttribute][$locale]
            );
        }

        return $translatedField;
    }

    protected function onIndexPage(): bool
    {
        if (! request()->route()) {
            return false;
        }

        $currentController = Str::before(request()->route()->getAction()['controller'] ?? '', '@');

        return $currentController === ResourceIndexController::class;
    }
}
