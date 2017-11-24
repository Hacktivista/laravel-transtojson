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
        . ' {src_lang : Source language in /resources/lang/}'
        . ' {dest_lang? : Destination language in /resources/lang/}'
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
        $this->lang_path = 'resources/lang';
        $this->matches = [];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // If not just debugging, be sure the user knows what's going on
        if (!$this->option('debug')) {
            $this->error("WARNING: THIS WILL OVERWRITE ALL FILES USING __() AND"
                . " trans() FUNCTIONS IN ".$this->argument('path')." AND ITS"
                . " SUBDIRECTORIES!");
            $this->comment("In order to check results without writing to files"
                . " run with -d option.");
            $this->question("ARE YOU SURE? (y/N)");

            $confirmation = trim(fgets(STDIN));
            if (strcasecmp($confirmation, 'Y') != 0) {
                $this->comment("Nothing done");
                exit;
            }
        }

        // Use src_lang as default locale
        \App::setLocale($this->argument('src_lang'));

        // Process files
        $this->readDir(rtrim($this->argument('path'), '/'));
        if (!$this->option('debug')) {
            $this->writeJson();
        }
    }

    private function readDir($dir_path)
    {
        if (is_dir($dir_path)) {
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
        } else {
            $this->processFile($dir_path);
        }
    }

    private function replacementCallback($matches)
    {
        // Use Lang::get(,,,false) so no fallback locale is used
        $src_lang_str = \Lang::get($matches[1], [], null, false);

        // JSON file:
        // Associate src lang with and empty string
        $this->matches[$src_lang_str] = "";

        // But if there's a dest_lang associate it
        if ($this->argument('dest_lang')) {
            $dest_lang_str = \Lang::get(
                $matches[1],
                [],
                $this->argument('dest_lang'),
                false
            );

            // Only if there's a match
            if ($dest_lang_str != $matches[1]) {
                $this->matches[$src_lang_str] = $dest_lang_str;
            }
        }

        // Processed file:
        if (array_key_exists(2, $matches)) {
            return '__("'.addcslashes($src_lang_str, '"').'", '.$matches[2].')';
        } else {
            return '__("'.addcslashes($src_lang_str, '"').'")';
        }
    }

    private function processFile($file_path)
    {
        if (is_writeable($file_path)) {
            $file = file_get_contents($file_path);
            $processed_file = preg_replace_callback(
                '/(?:trans|__)\(["\']([^"\' ]+)["\'](?:, ?)?(\[[^\]]+\])?\)/',
                'self::replacementCallback',
                $file
            );

            // Inform the user what's going on
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

    // If there's a destination lang, consider it as destination file
    private function destinationJsonPath()
    {
        if ($this->argument('dest_lang')) {
            $lang = $this->argument('dest_lang');
        } else {
            $lang = $this->argument('src_lang');
        }
        return "$this->lang_path/$lang.json";
    }

    private function writeJson()
    {
        if (is_writeable($this->lang_path)) {
            $file_path = $this->destinationJsonPath();

            // If $file_path already exists, append results to it
            if ($file = @file_get_contents($file_path)) {
                $old_file_array = (array) json_decode($file);
            } else {
                $old_file_array = [];
            }

            file_put_contents(
                $file_path,
                json_encode(
                    array_merge($this->matches, $old_file_array),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );

            $this->info("Wrote $file_path");
        } else {
            $this->error("$lang_path is not writeable");
        }
    }
}
