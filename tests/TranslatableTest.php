<?php

namespace Spatie\NovaTranslatable\Tests;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Text;
use Spatie\NovaTranslatable\Exceptions\InvalidConfiguration;
use Spatie\NovaTranslatable\Translatable;

class TranslatableTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Translatable::defaultLocales(['en', 'fr']);
    }

    /** @test */
    public function it_works_when_passing_no_fields_to_it()
    {
        $translatable = Translatable::make([]);

        $this->assertEquals([], $translatable->data);
    }

    /** @test */
    public function it_will_generate_a_field_per_locale()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ]);

        $this->assertCount(2, $translatable->data);

        $this->assertEquals($translatable->data[0]->name, 'Title (en)');
        $this->assertEquals($translatable->data[1]->name, 'Title (fr)');
    }

    /** @test */
    public function it_accepts_a_closure_to_customize_the_label()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->displayLocalizedNameUsing(function (Field $field, string $locale) {
            return $locale.'-'.$field->name;
        });

        $this->assertCount(2, $translatable->data);

        $this->assertEquals($translatable->data[0]->name, 'en-title');
        $this->assertEquals($translatable->data[1]->name, 'fr-title');
    }

    /** @test */
    public function it_will_can_accept_custom_locales()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->locales(['es', 'it', 'de']);

        $this->assertCount(3, $translatable->data);

        $this->assertEquals($translatable->data[0]->name, 'Title (es)');
        $this->assertEquals($translatable->data[1]->name, 'Title (it)');
        $this->assertEquals($translatable->data[2]->name, 'Title (de)');
    }

    /** @test */
    public function it_accepts_customize_the_labels_globally()
    {
        Translatable::displayLocalizedNameByDefaultUsing(function (Field $field, string $locale) {
            return $locale.'-'.$field->name;
        });

        $translatable = Translatable::make([
            new Text('title'),
        ]);

        $this->assertCount(2, $translatable->data);

        $this->assertEquals($translatable->data[0]->name, 'en-title');
        $this->assertEquals($translatable->data[1]->name, 'fr-title');
    }

    /** @test */
    public function it_will_throw_an_exception_if_default_locales_are_not_set()
    {
        Translatable::defaultLocales([]);

        $this->expectException(InvalidConfiguration::class);

        Translatable::make([]);
    }

    /** @test */
    public function it_accepts_different_rules_for_different_locales()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->rules(['title' => ['en' => 'required', 'fr' => 'min:3']]);

        $this->assertEquals($translatable->data[0]->rules, ['required']);
        $this->assertEquals($translatable->data[1]->rules, ['min:3']);

        $translatable->rulesFor('title', 'en', 'max:3');

        $this->assertEquals($translatable->data[0]->rules, ['max:3']);
    }

    /** @test */
    public function it_accepts_different_creation_rules_for_different_locales()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->creationRules(['title' => ['en' => 'required', 'fr' => 'min:3']]);

        $this->assertEquals($translatable->data[0]->creationRules, ['required']);
        $this->assertEquals($translatable->data[1]->creationRules, ['min:3']);

        $translatable->creationRulesFor('title', 'en', 'max:3');

        $this->assertEquals($translatable->data[0]->creationRules, ['max:3']);
    }

    /** @test */
    public function it_accepts_different_update_rules_for_different_locales()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->updateRules(['title' => ['en' => 'required', 'fr' => 'min:3']]);

        $this->assertEquals($translatable->data[0]->updateRules, ['required']);
        $this->assertEquals($translatable->data[1]->updateRules, ['min:3']);

        $translatable->updateRulesFor('title', 'en', 'max:3');

        $this->assertEquals($translatable->data[0]->updateRules, ['max:3']);
    }
}
