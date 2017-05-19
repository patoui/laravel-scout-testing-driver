# Testing Driver for Laravel Scout - Laravel 5.3/5.4

This package makes it easy to test your laravel scout code without loading external drivers with Laravel 5.3/5.4.

**NOTE: This is not meant to be used as a production solution, please see https://laravel.com/docs/master/scout for viable options.**

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

Add the following to your composer.json:

```javascript
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/patoui/laravel-scout-testing-driver"
    }
],
.
.
.
"require-dev": {
    "patoui/laravel-scout-testing-driver": "^1.0"
},
```

Run composer update:

``` bash
composer update patoui/laravel-scout-testing-driver
```

Add the following to your AppServicerProvider@register method:

```php
if ($this->app->environment('testing')) {
    $this->app->register(\PatOui\Scout\TestingScoutServiceProvider::class);
}
```

Add `SCOUT_DRIVER=testing` to your `.env.testing` (or phpunit.xml `<env name="SCOUT_DRIVER" value="testing"/>`) file

## Usage

Your test should run without problem:

`Post::search('My First Post')->get();`

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
