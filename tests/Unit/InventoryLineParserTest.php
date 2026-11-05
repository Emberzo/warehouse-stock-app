<?php

namespace Tests\Unit;

use App\Services\InventoryLineParser;
use PHPUnit\Framework\TestCase;

class InventoryLineParserTest extends TestCase
{
    public function test_it_parses_a_teemill_inventory_line(): void
    {
        $parsed = (new InventoryLineParser)->parse(
            'action: Added; quantity: 10; item_code: RNA1; product_name: Mens Organic T-shirt'
        );

        $this->assertSame([
            'action' => 'Added',
            'quantity' => '10',
            'item_code' => 'RNA1',
            'product_name' => 'Mens Organic T-shirt',
        ], $parsed);
    }

    public function test_it_trims_keys_and_values_and_skips_junk_segments(): void
    {
        $parsed = (new InventoryLineParser)->parse(
            ' action : Added ; ; not-a-pair ; quantity : 10 '
        );

        $this->assertSame([
            'action' => 'Added',
            'quantity' => '10',
        ], $parsed);
    }

    public function test_it_keeps_colons_inside_a_value(): void
    {
        $parsed = (new InventoryLineParser)->parse('note: size: M; item_code: RNA1');

        $this->assertSame('size: M', $parsed['note']);
        $this->assertSame('RNA1', $parsed['item_code']);
    }

    public function test_an_empty_line_returns_an_empty_array(): void
    {
        $this->assertSame([], (new InventoryLineParser)->parse('   '));
    }
}
