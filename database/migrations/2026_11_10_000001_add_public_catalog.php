<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_public');
            // Set by a moderator: the recipe stays with its owner but leaves the public catalog.
            $table->timestamp('hidden_at')->nullable()->after('published_at');
            $table->foreignId('forked_from_id')->nullable()->after('user_id')->constrained('recipes')->nullOnDelete();
            $table->index(['is_public', 'hidden_at']);
        });

        Schema::create('recipe_saves', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'recipe_id']);
        });

        Schema::create('recipe_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 20);
            $table->text('note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution', 20)->nullable();
            $table->timestamps();

            $table->index(['resolved_at', 'created_at']);
        });

        // Existing accounts get a username so they can have a public profile.
        foreach (DB::table('users')->orderBy('id')->get(['id', 'name']) as $user) {
            $base = Str::slug($user->name, '') ?: 'koki';
            $base = Str::limit($base, 30, '');
            $username = $base;
            $n = 2;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.$n++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        DB::table('recipes')->where('is_public', true)->whereNull('published_at')->update(['published_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_reports');
        Schema::dropIfExists('recipe_saves');
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forked_from_id');
            $table->dropIndex(['is_public', 'hidden_at']);
            $table->dropColumn(['published_at', 'hidden_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'is_admin']);
        });
    }
};
