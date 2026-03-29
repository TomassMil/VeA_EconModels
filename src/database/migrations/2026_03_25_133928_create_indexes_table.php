<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('filters')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('index_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('index_id')->constrained('indexes')->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->boolean('added_manually')->default(false);
            $table->timestamps();

            $table->unique(['index_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_instrument');
        Schema::dropIfExists('indexes');
    }
};
