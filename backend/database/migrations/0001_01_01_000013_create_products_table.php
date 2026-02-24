<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('main_code', 25);
            $table->string('aux_code', 25)->nullable();
            $table->string('name', 300);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 14, 6)->default(0);
            $table->decimal('cost_price', 14, 6)->nullable();

            // Impuestos
            $table->char('tax_code', 1)->default('2');
            $table->string('tax_percentage_code', 4)->default('4');
            $table->decimal('tax_rate', 5, 2)->default(15.00);
            $table->boolean('has_ice')->default(false);
            $table->string('ice_code', 10)->nullable();

            // Inventario
            $table->boolean('track_inventory')->default(false);
            $table->decimal('current_stock', 14, 4)->default(0);
            $table->decimal('min_stock', 14, 4)->default(0);
            $table->string('unit_of_measure', 50)->default('unidad');

            // Control
            $table->enum('type', ['product', 'service'])->default('product');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->string('barcode', 100)->nullable();
            $table->string('image_path', 500)->nullable();

            // Precios múltiples
            $table->json('prices')->nullable();

            // Stats cache
            $table->decimal('total_sold', 14, 2)->default(0);
            $table->integer('times_sold')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'main_code']);
            $table->index('tenant_id');
            $table->index('barcode');
            $table->index(['tenant_id', 'name']);
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['name', 'description', 'main_code']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
