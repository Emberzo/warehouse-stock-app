<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\InventoryImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_line_creates_the_product_and_applies_the_quantity(): void
    {
        $product = app(InventoryImporter::class)->importLine(
            'action: Added; quantity: 10; item_code: RNA1; product_name: Mens Organic T-shirt; colour: Black; size: M'
        );

        $this->assertSame('RNA1', $product->item_code);
        $this->assertSame(10, $product->quantity);
        $this->assertSame('Mens Organic T-shirt', $product->product_name);
    }

    public function test_a_failed_line_rolls_the_whole_file_back(): void
    {
        Product::factory()->create([
            'item_code' => 'RNA1',
            'quantity' => 10,
        ]);

        $contents = <<<'TXT'
action: Added; quantity: 5; item_code: RNA1; product_name: Mens Organic T-shirt
action: Subtracted; quantity: 100; item_code: RNA1; product_name: Mens Organic T-shirt
TXT;

        try {
            app(InventoryImporter::class)->import($contents);
            $this->fail('The second line should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertSame(10, Product::query()->where('item_code', 'RNA1')->value('quantity'));
    }

    public function test_subtracting_a_new_product_does_not_leave_a_zero_row(): void
    {
        try {
            app(InventoryImporter::class)->importLine(
                'action: Subtracted; quantity: 5; item_code: RNA9; product_name: Organic Cap; colour: Sand; size: One'
            );
            $this->fail('Subtracting from empty stock should fail.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertDatabaseMissing('products', ['item_code' => 'RNA9']);
    }

    public function test_the_import_command_applies_a_file(): void
    {
        Product::factory()->create([
            'item_code' => 'RNA1',
            'product_name' => 'Mens Organic T-shirt',
            'quantity' => 10,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'inv');
        $this->assertNotFalse($path);

        file_put_contents($path, "action: Added; quantity: 4; item_code: RNA1; product_name: Mens Organic T-shirt\n");

        $this->artisan('inventory:import', ['path' => $path])->assertSuccessful();

        $this->assertSame(14, Product::query()->where('item_code', 'RNA1')->value('quantity'));

        unlink($path);
    }

    public function test_an_unknown_action_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(InventoryImporter::class)->importLine(
            'action: Counted; quantity: 1; item_code: RNA1; product_name: Mens Organic T-shirt'
        );
    }
}
