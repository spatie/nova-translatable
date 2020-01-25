<?php

namespace Spatie\NovaTranslatable\Tests;

use Illuminate\Routing\Route;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Field;
use Spatie\NovaTranslatable\Translatable;
use Laravel\Nova\Http\Controllers\ResourceIndexController;
use Spatie\NovaTranslatable\Exceptions\InvalidConfiguration;

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
    public function it_will_remove_sortable_attribute_if_no_sort_locale_specified()
    {
        $this->mockIndexAction();

        $translatable = Translatable::make([
            Text::make('title')->sortable(),
        ]);

        $this->assertCount(1, $translatable->data);
        $this->assertFalse($translatable->data[0]->jsonSerialize()['sortable']);
    }

    /** @test */
    public function it_will_change_sort_key_if_sort_locale_is_set()
    {
        Translatable::defaultSortLocale('en');

        $this->mockIndexAction();

        $translatable = Translatable::make([
            Text::make('title')->sortable(),
        ]);

        $this->assertCount(1, $translatable->data);
        $this->assertEquals('title->en', $translatable->data[0]->jsonSerialize()['sortableUriKey']);
    }

    /** @test */
    public function it_will_change_sort_key_to_specified_locale()
    {
        Translatable::defaultSortLocale('en');

        $this->mockIndexAction();

        $translatable = Translatable::make([
            Text::make('title')->sortable(),
        ])->sortLocale('fr');

        $this->assertCount(1, $translatable->data);
        $this->assertEquals('title->fr', $translatable->data[0]->jsonSerialize()['sortableUriKey']);
    }

    /** @test */
    public function it_will_not_change_sort_key_if_already_specified()
    {
        Translatable::defaultSortLocale('en');

        $this->mockIndexAction();

        $translatable = Translatable::make([
            Text::make('title')->sortable()->withMeta(['sortableUriKey' => 'random']),
        ]);

        $this->assertCount(1, $translatable->data);
        $this->assertEquals('random', $translatable->data[0]->jsonSerialize()['sortableUriKey']);
    }

    /** @test */
    public function it_will_not_change_a_non_sortable_field_on_index_page()
    {
        $this->mockIndexAction();

        $translatable = Translatable::make([
            $original = Text::make('title'),
        ]);

        $this->assertCount(1, $translatable->data);
        $this->assertSame($original, $translatable->data[0]);
    }

    protected function mockIndexAction(): void
    {
        request()->setRouteResolver(function () {
            $route = \Mockery::mock(Route::class);
            $route->shouldReceive('getAction')->andReturn(['controller' => ResourceIndexController::class]);
            $route->shouldIgnoreMissing();

            return $route;
        });
    }
}
