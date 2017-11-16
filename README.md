# Laravel translations to JSON

Turn Laravel 5 dot.based.translations into JSON-based translations

## Installation

```
composer require hacktivista/laravel-transtojson --dev
```

## Usage

```
php artisan translations:to_json path/to/process/ lang [--debug]
```

It will:
- Replace all `trans('...')` and `__('...')` translations found in files on `path/to/process/` and subfolders with `__("Textual translation strings")`.
- Create a `lang`.json  file in `resources/lang/` with textual translation strings. If file already exists, it will merge results with what's already in the file.

**THIS WILL OVERWRITE ALL FILES IN `path/to/process/` AND SUBDIRECTORIES. BE SURE TO BACKUP FILES PREVIOUS TO RUN THIS COMMAND!**

In order to check results without writing to files run with `--debug` option.

It will NOT delete translations in `resources/lang/<lang>/`, do it manually when you consider appropriate.

## Contributing

If you'd like to contribute to this project please read [CONTRIBUTING.md](CONTRIBUTING.md)
