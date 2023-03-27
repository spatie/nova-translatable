<?php

namespace Spatie\NovaTranslatable\Tests;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
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

        $this->assertEquals('Title (en)', $translatable->data[0]->name);
        $this->assertEquals('Title (fr)', $translatable->data[1]->name);
    }

    /** @test */
    public function it_accepts_a_closure_to_customize_the_label()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->displayLocalizedNameUsing(function (Field $field, string $locale) {
            return $locale . '-' . $field->name;
        });

        $this->assertCount(2, $translatable->data);

        $this->assertEquals('en-title', $translatable->data[0]->name);
        $this->assertEquals('fr-title', $translatable->data[1]->name);
    }

    /** @test */
    public function it_will_can_accept_custom_locales()
    {
        $translatable = Translatable::make([
            new Text('title'),
        ])->locales(['es', 'it', 'de']);

        $this->assertCount(3, $translatable->data);

        $this->assertEquals('Title (es)', $translatable->data[0]->name);
        $this->assertEquals('Title (it)', $translatable->data[1]->name);
        $this->assertEquals('Title (de)', $translatable->data[2]->name);
    }

    /** @test */
    public function it_accepts_customize_the_labels_globally()
    {
        Translatable::displayLocalizedNameByDefaultUsing(function (Field $field, string $locale) {
            return $locale . '-' . $field->name;
        });

        $translatable = Translatable::make([
            new Text('title'),
        ]);

        $this->assertCount(2, $translatable->data);

        $this->assertEquals('en-title', $translatable->data[0]->name);
        $this->assertEquals('fr-title', $translatable->data[1]->name);
    }

    /** @test */
    public function it_will_throw_an_exception_if_default_locales_are_not_set()
    {
        Translatable::defaultLocales([]);

        $this->expectException(InvalidConfiguration::class);

        Translatable::make([]);
    }

    /** @test */
    public function it_creates_an_extra_uploads_field_for_trix()
    {
        $translatable = Translatable::make([
            Trix::make('Description', 'description')
                ->withFiles($disk = 'test-disk', $path = 'test-path'),
        ]);

        $this->assertCount(4, $translatable->data);

        $trixEn = $translatable->data[1];
        $trixFr = $translatable->data[3];

        $this->assertInstanceOf(Trix::class, $trixEn);
        $this->assertInstanceOf(Trix::class, $trixFr);

        $this->assertEquals('Description (en)', $translatable->data[0]->name);
        $this->assertEquals('translations_description_en', $trixEn->name);
        $this->assertEquals('Description (fr)', $translatable->data[2]->name);
        $this->assertEquals('translations_description_fr', $trixFr->name);

        $this->assertTrixField($trixEn, $disk, $path);
        $this->assertTrixField($trixFr, $disk, $path);
    }

    private function assertTrixField(Trix $field, string $disk, string $path)
    {
        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnDetail);
        $this->assertFalse($field->showOnUpdate);
        $this->assertEquals($disk, $field->getStorageDisk());
        $this->assertEquals($path, $field->getStorageDir());
    }
}
