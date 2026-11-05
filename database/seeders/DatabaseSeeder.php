<?php

namespace Database\Seeders;

use App\Services\InventoryImporter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $path = database_path('data/sample-inventory.txt');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Seed inventory file is missing: {$path}");
        }

        app(InventoryImporter::class)->import($contents);
    }
}
