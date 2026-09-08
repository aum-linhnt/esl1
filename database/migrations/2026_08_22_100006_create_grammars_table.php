<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grammars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('set null');
            $table->string('title');
            $table->longText('content');
            $table->enum('level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])->default('A1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grammars');
    }
};
