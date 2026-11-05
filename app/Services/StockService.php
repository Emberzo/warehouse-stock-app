<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function add(string $itemCode, int $quantity): Product
    {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($itemCode, $quantity): Product {
            $product = $this->findLocked($itemCode);
            $product->quantity += $quantity;
            $product->save();

            return $product->refresh();
        });
    }

    public function subtract(string $itemCode, int $quantity): Product
    {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($itemCode, $quantity): Product {
            $product = $this->findLocked($itemCode);

            if ($product->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Cannot subtract {$quantity} from {$product->item_code}. Only {$product->quantity} in stock.",
                ]);
            }

            $product->quantity -= $quantity;
            $product->save();

            return $product->refresh();
        });
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }
    }

    private function findLocked(string $itemCode): Product
    {
        $product = Product::query()
            ->where('item_code', $itemCode)
            ->lockForUpdate()
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'item_code' => "No product with item code {$itemCode}.",
            ]);
        }

        return $product;
    }
}
