<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabularies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('set null');
            $table->string('word');
            $table->string('phonetic')->nullable();
            $table->string('meaning');
            $table->text('example')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])->default('A1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
