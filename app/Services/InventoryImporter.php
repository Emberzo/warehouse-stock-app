<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryImporter
{
    public function __construct(
        private readonly InventoryLineParser $parser,
        private readonly StockService $stock,
    ) {}

    /**
     * Apply each line in one transaction. Any failure rolls the file back.
     *
     * @return list<Product>
     */
    public function import(string $contents): array
    {
        return DB::transaction(function () use ($contents): array {
            $products = [];

            foreach ($this->lines($contents) as $line) {
                $products[] = $this->importLine($line);
            }

            return $products;
        });
    }

    public function importLine(string $line): Product
    {
        $parsed = $this->parser->parse($line);
        $this->requireFields($parsed, ['action', 'quantity', 'item_code']);

        $quantity = filter_var($parsed['quantity'], FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity < 1) {
            throw new InvalidArgumentException('Quantity must be a positive integer.');
        }

        $itemCode = $parsed['item_code'];

        return DB::transaction(function () use ($parsed, $quantity, $itemCode): Product {
            $exists = Product::query()->where('item_code', $itemCode)->exists();

            if (! $exists) {
                $this->requireFields($parsed, ['product_name']);

                // Created at zero. StockService applies the movement.
                Product::query()->create([
                    'item_code' => $itemCode,
                    'product_name' => $parsed['product_name'],
                    'colour' => $this->optional($parsed, 'colour'),
                    'size' => $this->optional($parsed, 'size'),
                    'quantity' => 0,
                ]);
            }

            return match (strtolower($parsed['action'])) {
                'added', 'add' => $this->stock->add($itemCode, $quantity),
                'subtracted', 'subtract', 'removed', 'remove' => $this->stock->subtract($itemCode, $quantity),
                default => throw new InvalidArgumentException("Unknown inventory action [{$parsed['action']}]. Use Added or Subtracted."),
            };
        });
    }

    /**
     * @param  array<string, string>  $parsed
     * @param  list<string>  $fields
     */
    private function requireFields(array $parsed, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $parsed) || $parsed[$field] === '') {
                throw new InvalidArgumentException("Inventory line is missing {$field}.");
            }
        }
    }

    /**
     * @param  array<string, string>  $parsed
     */
    private function optional(array $parsed, string $field): ?string
    {
        if (! array_key_exists($field, $parsed) || $parsed[$field] === '') {
            return null;
        }

        return $parsed[$field];
    }

    /**
     * @return list<string>
     */
    private function lines(string $contents): array
    {
        $lines = [];

        foreach (preg_split("/\r\n|\n|\r/", $contents) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }
}
