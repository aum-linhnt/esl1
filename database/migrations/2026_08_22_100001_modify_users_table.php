<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->enum('role', ['student', 'teacher', 'admin'])->default('student')->after('email');
            $table->enum('status', ['active', 'trial_expired', 'blocked'])->default('active')->after('role');
            $table->string('avatar')->nullable()->after('status');
            $table->integer('coins')->default(24)->after('avatar');
            $table->string('current_level')->default('A1')->after('coins');
            $table->timestamp('trial_ends_at')->nullable()->after('current_level');
            $table->string('phone')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'role', 'status', 'avatar',
                'coins', 'current_level', 'trial_ends_at', 'phone',
            ]);
        });
    }
};
