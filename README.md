# Making Nova fields translatable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/nova-translatable.svg?style=flat-square)](https://packagist.org/packages/spatie/nova-translatable)
![CircleCI branch](https://img.shields.io/circleci/project/github/spatie/nova-translatable/master.svg?style=flat-square)
[![StyleCI](https://github.styleci.io/repos/145974148/shield?branch=master)](https://github.styleci.io/repos/145974148)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/nova-translatable.svg?style=flat-square)](https://packagist.org/packages/spatie/nova-translatable)

This package contains a `Translatable` class to you can use to make any Nova field type translatable.

Imagine you have this `fields` function in a `Post` Nova resource:

```php
public function fields(Request $request)
{
    return [
        ID::make()->sortable(),

        Translatable::make([
            Text::make('title'),
            Trix::make('text'),
        ])
    ];
}
```

That `Post` Nova resource will be rendered like this.

![TODO: INSERT SCREENSHOT]( https://spatie.github.io/nova-translatable/screenshot.png)

## Requirements

This Nova field requires MySQL 5.7.8 or higher.

## Installation

First you must install [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) into your Laravel app. In a nutshell this package will store translations for your model in a json field in your table. The package provides many handy functions to store and retrieve translations. Be sure to read [the entire readme of laravel-translatable](https://github.com/spatie/laravel-translatable/blob/master/README.md) before using this Nova package.

Next, you can install this Nova package in to a Laravel app that uses [Nova](https://nova.laravel.com) via composer:

```bash
composer require spatie/nova-translatable
```

## Usage

In order to use the package you must for let `Translatable` know which locales your app is using. Here's quick example. You can put this code in `AppServiceProvider` or a dedictated service provider of your own.

```php
// in a service provider

\Spatie\NovaTranslatable\Translatable::defaultLocales(['en', 'fr']);
```

Next you must prepare your model as explained in the readme of laravel-translatable. In short you must add `json` columns to your table for each field you want to translate. Your model must use the `Spatie\Translatable\HasTranslations` on your model. You must also add a `$translatable` property on your model that holds an array with translatable attribute names.

Now that your model can hold translations you can use `Translatable` in any Nova resource. Any fields that you want to display in a multilingual way can be past as an array. 

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

If you have Nova resource where you want other locales than the globally defined ones, you can call `locales` on `Translatable.

```php
Translatable::make([
    Text::make('title'),
    Trix::make('text'),
])->locales('de', 'es'),
```

These fields will now use the `de` and `es` locales.

### Customizing the label of a translatable

By default translatable fields get ` ($locale)` appended to their label. You can customize this passing a closure the `displayLocaleUsing` function. 

```php
Translatable::make([
    Text::make('title'),
    Trix::make('text'),
])->displayLocaleUsing(function(Field $field, string $locale) {
   return ucfirst($field->name) . " [{$locale}]";
}),
```

With this in place the label will get ` [$locale]` appended.

Of course you can still customize the label of a field as usual.

```php
Translatable::make([
    Text::make('My title', 'title),
    Trix::make('text'),
])->displayLocaleUsing(function(Field $field, string $locale) {
   return ucfirst($field->name) . " [{$locale}]";
}),
```

With the code about the label for the `title` field will be "My title ['en']".

## On customozing the UI

You might wonder why we didn't render the translatable fields in tabs, panels or with magical unicorns displayed next to them. The truth is that everybody wants translations to be displayed a bit different. That's why we opted to keep it very simple for now.

If Nova gains the ability to better structure a long form natively, we'd probably start leveraging that in a new major version of the package.

### Testing

``` bash
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

The Vue components that render the tags are based upon the tag Vue components created by [Adam Wathan](https://twitter.com/adamwathan) as shown in [his excellent Advanced Vue Component Design course](https://adamwathan.me/advanced-vue-component-design/).

## Support us

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

Does your business depend on our contributions? Reach out and support us on [Patreon](https://www.patreon.com/spatie). 
All pledges will be dedicated to allocating workforce on maintenance and new awesome stuff.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
