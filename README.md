# Making Nova fields translatable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/nova-translatable.svg?style=flat-square)](https://packagist.org/packages/spatie/nova-translatable)
[![StyleCI](https://github.styleci.io/repos/150127712/shield?branch=master)](https://github.styleci.io/repos/150127712)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/nova-translatable.svg?style=flat-square)](https://packagist.org/packages/spatie/nova-translatable)

This package contains a `Translatable` class you can use to make any Nova field type translatable.

Imagine you have this `fields` method in a `Post` Nova resource:

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        Translatable::make([
            Text::make('title'),
            Trix::make('text'),
        ]),
    ];
}
```

That `Post` Nova resource will be rendered like this.

![screenshot]( https://spatie.github.io/nova-translatable/screenshot.png)

## Requirements

This Nova field requires Nova 2 specifically and MySQL 5.7.8 or higher.

## Installation

First you must install [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) into your Laravel app. In a nutshell, this package will store translations for your model in a json column in your table. On top of that, it provides many handy functions to store and retrieve translations. Be sure to read [the entire readme of laravel-translatable](https://github.com/spatie/laravel-translatable/blob/master/README.md) before using this Nova package.

Next, you can install this Nova package into a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require spatie/nova-translatable
```

## Usage

In order to use the package you must first let `Translatable` know which locales your app is using using the `Translatable::defaultLocales()` method. You can put this code in `AppServiceProvider` or a dedicated service provider of your own.

```php
// in any service provider

\Spatie\NovaTranslatable\Translatable::defaultLocales(['en', 'fr']);
```

Next, you must prepare your model [as explained](https://github.com/spatie/laravel-translatable#making-a-model-translatable) in the readme of laravel-translatable. In short: you must add `json` columns to your model's table for each field you want to translate. Your model must use the `Spatie\Translatable\HasTranslations` on your model. Finally, you must also add a `$translatable` property on your model that holds an array with the translatable attribute names.

Now that your model is configured for translations, you can use `Translatable` in the related Nova resource. Any fields you want to display in a multilingual way can be passed as an array to `Translatable. 

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        Translatable::make([
            Text::make('title'),
            Trix::make('text'),
        ]),
    ];
}
```

### Customizing the locales per translatable

If you have a Nova resource where you want different locales than the ones configured globally, you can call the `locales` method on `Translatable`.

```php
Translatable::make([
    Text::make('title'),
    Trix::make('text'),
])->locales(['de', 'es']),
```

These fields will now use the `de` and `es` locales.

### Customizing the name of a translatable

By default translatable fields get ` ($locale)` appended to their name. You can customize this behaviour globally by providing a closure to `displayLocalizedNameByDefaultUsing` on `Translatable`. This callback will be used to render the localized field names.

```php
Translatable::displayLocalizedNameByDefaultUsing(function(Field $field, string $locale) {
   return ucfirst($field->name) . " [{$locale}]";
})
```

With this in place all names of translatable fields will get ` [$locale]` appended.

You can also customize the localized field name per resource by passing a closure the `displayLocalizedNameUsing` function. 

```php
Translatable::make([
    Text::make('title'),
    Trix::make('text'),
])->displayLocalizedNameUsing(function(Field $field, string $locale) {
   return ucfirst($field->name) . " --- {$locale}]";
}),
```

With this in place, the localized field names will be suffixed with ` --- $locale`.

Of course you can still customize the name of a field as usual.

```php
Translatable::make([
    Text::make('My title', 'title'),
    Trix::make('text'),
])->displayLocalizedNameUsing(function(Field $field, string $locale) {
   return ucfirst($field->name) . " [{$locale}]";
}),
```

Using the code about above the name for the `title` field will be "My title ['en']".

### Sorting by translatable fields

Because the translations are stored as a JSON object it can't be used for sorting by default. In the translatable fields are stored as json fields a sort locale can be specified allowing sorting by that language.

```php
Translatable::make([
    Text::make('My title', 'title')->sortable(),
    Trix::make('text'),
])->sortLocale('en'),
``` 

Or can be set globally:

```php
Translatable::defaultSortLocale(config('translatable.fallback_locale'));
``` 

## On customizing the UI

You might wonder why we didn't render the translatable fields in tabs, panels or with magical unicorns displayed next to them. The truth is that everybody wants translations to be displayed a bit different. That's why we opted to keep them very simple for now.

If Nova gains the ability to better structure a long form natively, we'd probably start leveraging that in a new major version of the package.

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie). 
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
