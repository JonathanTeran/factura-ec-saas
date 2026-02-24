<?php

namespace App\Services\AI;

use App\Exceptions\FeatureNotAvailableException;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AICategorizationService
{
    private Tenant $tenant;

    public function forTenant(Tenant $tenant): self
    {
        if (!$tenant->hasFeature('ai_categorization')) {
            throw new FeatureNotAvailableException('ai_categorization');
        }

        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Categorizar un producto usando IA.
     */
    public function categorizeProduct(Product $product): ?Category
    {
        $categories = Category::where('tenant_id', $this->tenant->id)
            ->active()
            ->get(['id', 'name', 'description'])
            ->toArray();

        if (empty($categories)) {
            return null;
        }

        $suggestion = $this->callOpenAI($product, $categories);

        if ($suggestion === null) {
            return null;
        }

        $category = Category::where('tenant_id', $this->tenant->id)
            ->where('id', $suggestion)
            ->first();

        if ($category) {
            $product->update(['category_id' => $category->id]);
        }

        return $category;
    }

    /**
     * Categorizar multiples productos en lote.
     */
    public function categorizeProducts(array $productIds): array
    {
        $products = Product::where('tenant_id', $this->tenant->id)
            ->whereIn('id', $productIds)
            ->whereNull('category_id')
            ->get();

        $results = [];

        foreach ($products as $product) {
            try {
                $category = $this->categorizeProduct($product);
                $results[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_id' => $category?->id,
                    'category_name' => $category?->name,
                    'status' => $category ? 'categorized' : 'no_match',
                ];
            } catch (\Throwable $e) {
                Log::warning('AI categorization failed for product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_id' => null,
                    'category_name' => null,
                    'status' => 'error',
                ];
            }
        }

        return $results;
    }

    /**
     * Sugerir una nueva categoria para un producto.
     */
    public function suggestCategory(Product $product): ?string
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            return null;
        }

        $prompt = "Dado el siguiente producto de una empresa ecuatoriana, sugiere un nombre de categoria apropiado (maximo 3 palabras, en espanol).\n\n"
            . "Producto: {$product->name}\n"
            . "Descripcion: " . ($product->description ?? 'Sin descripcion') . "\n"
            . "Tipo: " . ($product->type ?? 'producto') . "\n\n"
            . "Responde SOLO con el nombre de la categoria sugerida, sin explicaciones.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente de categorizacion de productos para negocios ecuatorianos.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 20,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content'));
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI API error in suggestCategory', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function callOpenAI(Product $product, array $categories): ?int
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            Log::warning('OpenAI API key not configured');
            return null;
        }

        $categoryList = collect($categories)
            ->map(fn($c) => "ID: {$c['id']} - Nombre: {$c['name']}" . ($c['description'] ? " ({$c['description']})" : ''))
            ->implode("\n");

        $prompt = "Dado el siguiente producto, asignale la categoria mas apropiada de la lista.\n\n"
            . "Producto: {$product->name}\n"
            . "Descripcion: " . ($product->description ?? 'Sin descripcion') . "\n"
            . "Codigo: " . ($product->main_code ?? 'N/A') . "\n"
            . "Tipo: " . ($product->type ?? 'producto') . "\n\n"
            . "Categorias disponibles:\n{$categoryList}\n\n"
            . "Responde SOLO con el ID numerico de la categoria mas apropiada. Si ninguna categoria aplica, responde 0.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente de categorizacion de productos. Responde SOLO con el ID numerico.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 10,
                'temperature' => 0.1,
            ]);

            if ($response->successful()) {
                $result = trim($response->json('choices.0.message.content'));
                $categoryId = (int) $result;

                return $categoryId > 0 ? $categoryId : null;
            }

            Log::warning('OpenAI API returned non-successful response', [
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
