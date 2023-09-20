# Making Nova fields translatable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/nova-translatable.svg?style=flat-square)](https://packagist.org/packages/spatie/nova-translatable)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/spatie/nova-translatable/run-tests.yml?branch=main&style=flat-square&label=Tests)
![Check & fix styling](https://img.shields.io/github/actions/workflow/status/spatie/nova-translatable/php-cs-fixer.yml?branch=main&style=flat-square&label=Check%20%26%20fix%20styling)
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

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/nova-translatable.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/nova-translatable)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Requirements

This Nova field requires Nova 3 specifically and MySQL 5.7.8 or higher.

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

### Making translations searchable

Every translation of the translated fields should be added into the `$search` array separately.

```php
/**
 * The columns that should be searched.
 *
 * @var array
 */
public static $search = [
    'id', 'name->en', 'name->fr',
];
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

### Customizing the rules of a translatable

You may use the regular Nova functionality to define rules on the fields inside your Translatable fields collection. However, this will apply those rules to all locales. If you wish to define different rules per locale you can do so on the Translatable collection.

```php
Translatable::make([
    Text::make('My title', 'title'),
    Trix::make('text'),
])->rules([
        'title' => ['en' => 'required', 'nl' => 'nullable'],
        'text' => ['en' => 'required|min:10', 'nl' => 'nullable|min:10'],
    ]
),
```

You may also use the more fluent `rulesFor()` method, which allows you to define rules per field per locale.

```php
Translatable::make([
    Text::make('My title', 'title'),
    Trix::make('text'),
])->rulesFor('title', 'en', 'required')
->rulesFor('title', 'nl', 'nullable'),
```

There are also methods for update and creation rules called `creationRules()`, `updateRules()`, `creationRulesFor()` and `updateRulesFor()`. They function in the same way as the `rules()` and `rulesFor()` methods.

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

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

### Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
