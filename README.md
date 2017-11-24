# Laravel translations to JSON

Turn Laravel 5 dot.based.translations into JSON-based translations

## Installation

```
composer require hacktivista/laravel-transtojson --dev
```

## Usage

```
php artisan translations:to_json path/to/process/ src_lang [dest_lang] [--debug]
```

E.g.
```
php artisan translations:to_json resources/views/ en es
```

It will:
- Replace all `trans('...')` and `__('...')` translations found in files on `path/to/process/` and subfolders with `__("Textual translation strings")`
- Create a JSON file in `resources/lang/` with textual translation strings *

\* If JSON file already exists it will merge results with what's already on it. If there's a match with a phrase already matched, contents on the JSON file will prevail.

**THIS WILL OVERWRITE ALL FILES USING __() AND trans() FUNCTIONS IN `path/to/process/` AND SUBDIRECTORIES. BE SURE TO BACKUP FILES PREVIOUS TO RUN THIS COMMAND!**

In order to check results without writing to files run with `--debug` option.

`dest_lang` is optional, if set, the resulting JSON file will have source language strings associated with destination language strings. If not, only source language strings with empty destination strings.

It will NOT delete translations in `resources/lang/<lang>/`, do it manually when you consider appropriate.

## Contributing

If you'd like to contribute to this project please read [CONTRIBUTING.md](CONTRIBUTING.md)
