<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planes "a medida" (Enterprise): no se publica precio en la landing ni se
 * pueden contratar en autoservicio; el interesado nos contacta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_contact_sales')->default(false)->after('is_featured');
        });

        DB::table('plans')->where('slug', 'enterprise')->update(['is_contact_sales' => true]);

        Cache::forget('landing:public');
        Cache::forget('billing:plans');
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_contact_sales');
        });

        Cache::forget('landing:public');
        Cache::forget('billing:plans');
    }
};
