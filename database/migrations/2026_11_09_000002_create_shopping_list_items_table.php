<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 30)->nullable();
            // Recipe titles this item came from, e.g. ["Nasi Goreng Kampung", "Sayur Asem"].
            $table->json('sources')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
