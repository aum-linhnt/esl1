<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('phone');
            $table->string('gender', 20)->nullable()->after('birthday');
            $table->string('address', 255)->nullable()->after('gender');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('school_workplace', 255)->nullable()->after('city');
            $table->string('target_level', 20)->default('B1')->after('school_workplace');
            $table->text('bio')->nullable()->after('target_level');
            $table->string('facebook_url', 255)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'birthday', 'gender', 'address', 'city',
                'school_workplace', 'target_level', 'bio', 'facebook_url',
            ]);
        });
    }
};
