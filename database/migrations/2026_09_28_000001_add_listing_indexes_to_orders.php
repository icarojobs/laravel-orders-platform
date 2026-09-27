<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['placed_at', 'id']);
            $table->index(['status', 'placed_at']);
            $table->index(['customer_id', 'placed_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'placed_at']);
            $table->dropIndex(['status', 'placed_at']);
            $table->dropIndex(['placed_at', 'id']);
        });
    }
};
