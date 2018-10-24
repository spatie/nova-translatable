<?php

namespace Spatie\NovaTranslatable\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function defaultLocalesNotSet()
    {
        return new static("There are no default locales set. Make sure you call `Spatie\NovaTranslatable\Translatable::defaultLocales` in a service provider and pass it an array of locales.");
    }
}
