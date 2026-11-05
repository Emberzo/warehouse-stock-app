<?php

namespace App\Console\Commands;

use App\Services\InventoryImporter;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ImportInventoryCommand extends Command
{
    protected $signature = 'inventory:import {path : Text file with one inventory line per row}';

    protected $description = 'Apply inventory lines from a text file';

    public function handle(InventoryImporter $importer): int
    {
        $path = (string) $this->argument('path');

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->error("Cannot read {$path}.");

            return self::FAILURE;
        }

        try {
            $products = $importer->import($contents);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->error(is_string($message) ? $message : 'Import failed.');

            return self::FAILURE;
        }

        $this->info('Imported '.count($products).' line(s).');

        return self::SUCCESS;
    }
}
