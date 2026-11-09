<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 20)->index();
            $table->string('difficulty', 10)->default('mudah');
            $table->unsignedTinyInteger('servings')->default(2);
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->unsignedSmallInteger('cook_minutes')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('cooked_count')->default(0);
            $table->timestamp('last_cooked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('instruction');
            $table->unsignedInteger('timer_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('cook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamp('cooked_at');
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->unsignedTinyInteger('servings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'cooked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cook_logs');
        Schema::dropIfExists('steps');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('recipes');
    }
};
