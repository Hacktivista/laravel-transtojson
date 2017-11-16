<?php

namespace Hacktivista\TransToJson\Console;

use Illuminate\Console\Command;

class TranslationsToJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:to_json'
        . ' {path : Path to scan and replace}'
        . ' {lang : Source language in /resources/lang/}'
        . ' {--d|debug : Show results on stdout, no file is written}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Turn dot.based.translations into JSON-based translations";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->matches = [];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->option('debug')) {
            $this->error("WARNING: THIS WILL OVERWRITE ALL FILES IN "
                . $this->argument('path'). " AND ITS SUBDIRECTORIES!");
            $this->comment("In order to check results without writing to files"
                . " run with -d option.");
            $this->question("ARE YOU SURE? (y/N)");

            $confirmation = trim(fgets(STDIN));
            if (strcasecmp($confirmation, 'Y') != 0) {
                $this->comment("Nothing done");
                exit;
            }
        }

        \App::setLocale($this->argument('lang'));
        $this->readDir(rtrim($this->argument('path'), '/'));

        if (!$this->option('debug')) {
            $this->writeJson();
        }
    }

    private function readDir($dir_path)
    {
        $handle = opendir($dir_path);

        while (false !== ($entry = readdir($handle))) {
            $current_file = "$dir_path/$entry";

            if (is_dir($current_file)) {
                if ($entry != '.' && $entry != '..') {
                    $this->readDir($current_file);
                }
            } else {
                $this->processFile($current_file);
            }
        }

        closedir($handle);
    }

    private function processFile($file_path)
    {
        if (is_writeable($file_path)) {
            $file = file_get_contents($file_path);
            $processed_file = preg_replace_callback(
                '/(?:trans|__)\(["\']([^"\']+)["\'](?:, ?)?(\[[^\]]+\])?\)/',
                function ($matches) {
                    $translated_str = trans($matches[1]);
                    $this->matches[$translated_str] = "";

                    if (array_key_exists(2, $matches)) {
                        return '__("'.addcslashes($translated_str, '"').'", '.$matches[2].')';
                    } else {
                        return '__("'.addcslashes($translated_str, '"').'")';
                    }
                },
                $file
            );

            if (!$this->option('debug')) {
                if ($file == $processed_file) {
                    $this->comment("No matches in $file_path");
                } else {
                    file_put_contents($file_path, $processed_file);
                    $this->info("Overwritten $file_path");
                }
            } else {
                if ($file == $processed_file) {
                    $this->comment("\n--- $file_path");
                    $this->line("No matches");
                } else {
                    $this->info("\n+++ $file_path");
                    $this->line($processed_file);
                }
            }
        } else {
            $this->error("$file_path is not writeable");
        }
    }

    private function writeJson()
    {
        $lang_path = 'resources/lang';

        if (is_writeable($lang_path)) {
            $file_path = "$lang_path/".\App::getLocale().'.json';

            // If $file_path already exists, append results to it
            if ($file = @file_get_contents($file_path)) {
                $old_file_array = (array) json_decode($file);
            } else {
                $old_file_array = [];
            }

            file_put_contents(
                $file_path,
                json_encode(
                    array_merge($old_file_array, $this->matches),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );

            $this->info("Wrote $file_path");
        } else {
            $this->error("$lang_path is not writeable");
        }
    }
}
