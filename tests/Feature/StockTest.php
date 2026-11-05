<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_redirects_to_stock(): void
    {
        $this->get('/')->assertRedirect('/stock');
    }

    public function test_the_stock_page_lists_products(): void
    {
        $product = Product::factory()->create([
            'item_code' => 'RNA1',
            'product_name' => 'Mens Organic T-shirt',
            'quantity' => 48,
        ]);

        $this->get('/stock')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Stock/Index')
                ->has('products', 1)
                ->where('products.0.item_code', $product->item_code)
                ->where('products.0.quantity', 48)
            );
    }

    public function test_stock_can_be_added_and_subtracted(): void
    {
        Product::factory()->create([
            'item_code' => 'RNA1',
            'quantity' => 10,
        ]);

        $this->from('/stock')->post('/stock/add', [
            'item_code' => 'RNA1',
            'quantity' => 5,
        ])->assertRedirect('/stock')->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'item_code' => 'RNA1',
            'quantity' => 15,
        ]);

        $this->from('/stock')->post('/stock/subtract', [
            'item_code' => 'RNA1',
            'quantity' => 4,
        ])->assertRedirect('/stock');

        $this->assertSame(11, Product::query()->where('item_code', 'RNA1')->value('quantity'));
    }

    public function test_subtract_does_not_go_below_zero(): void
    {
        Product::factory()->create([
            'item_code' => 'RNA1',
            'quantity' => 3,
        ]);

        $this->from('/stock')->post('/stock/subtract', [
            'item_code' => 'RNA1',
            'quantity' => 4,
        ])->assertRedirect('/stock')->assertSessionHasErrors('quantity');

        $this->assertSame(3, Product::query()->where('item_code', 'RNA1')->value('quantity'));
    }

    public function test_an_unknown_item_code_is_rejected(): void
    {
        $this->from('/stock')->post('/stock/add', [
            'item_code' => 'NOPE',
            'quantity' => 1,
        ])->assertRedirect('/stock')->assertSessionHasErrors('item_code');
    }

    public function test_the_json_api_lists_adds_and_subtracts(): void
    {
        Product::factory()->create([
            'item_code' => 'RNA1',
            'quantity' => 10,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.item_code', 'RNA1')
            ->assertJsonPath('data.0.quantity', 10);

        $this->postJson('/api/stock/add', [
            'item_code' => 'RNA1',
            'quantity' => 2,
        ])->assertOk()->assertJsonPath('data.quantity', 12);

        $this->postJson('/api/stock/subtract', [
            'item_code' => 'RNA1',
            'quantity' => 100,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');

        $this->assertSame(12, Product::query()->where('item_code', 'RNA1')->value('quantity'));
    }

    public function test_the_seed_file_loads_teemill_style_products(): void
    {
        $this->seed();

        $this->assertDatabaseHas('products', [
            'item_code' => 'RNA1',
            'product_name' => 'Mens Organic T-shirt',
            'colour' => 'Black',
            'size' => 'M',
            'quantity' => 48,
        ]);

        $this->assertSame(8, Product::query()->count());
    }
}
