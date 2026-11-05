<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $products = $this->products();

        if ($this->wantsJsonResponse($request)) {
            return response()->json(['data' => $products]);
        }

        return Inertia::render('Stock/Index', [
            'products' => $products,
        ]);
    }

    public function add(AdjustStockRequest $request, StockService $stock): JsonResponse|RedirectResponse
    {
        $quantity = $request->integer('quantity');
        $product = $stock->add($request->string('item_code')->toString(), $quantity);

        return $this->respond($request, $product, "Added {$quantity} to {$product->item_code}. On hand: {$product->quantity}.");
    }

    public function subtract(AdjustStockRequest $request, StockService $stock): JsonResponse|RedirectResponse
    {
        $quantity = $request->integer('quantity');
        $product = $stock->subtract($request->string('item_code')->toString(), $quantity);

        return $this->respond($request, $product, "Subtracted {$quantity} from {$product->item_code}. On hand: {$product->quantity}.");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function products(): array
    {
        return Product::query()
            ->orderBy('item_code')
            ->get()
            ->map(fn (Product $product): array => (new ProductResource($product))->resolve(request()))
            ->all();
    }

    private function respond(Request $request, Product $product, string $status): JsonResponse|RedirectResponse
    {
        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'message' => $status,
                'data' => (new ProductResource($product))->resolve($request),
            ]);
        }

        return back()->with('status', $status);
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->is('api/*') || $request->wantsJson();
    }
}
