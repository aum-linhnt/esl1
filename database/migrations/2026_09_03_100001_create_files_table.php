<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique();               // SHA-256 content hash
            $table->string('disk', 20)->default('public');       // filesystem disk
            $table->string('path');                              // storage path (files/ab/cd/hash.ext)
            $table->string('original_name');                     // original filename from first upload
            $table->string('mime_type', 100);                    // application/pdf, audio/mpeg, etc.
            $table->string('extension', 20);                     // pdf, docx, mp3, etc.
            $table->unsignedBigInteger('size');                  // file size in bytes
            $table->unsignedInteger('reference_count')->default(1); // how many places reference this file
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('hash');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
