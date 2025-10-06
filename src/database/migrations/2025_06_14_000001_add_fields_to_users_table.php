<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rut')->unique()->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('password');
            $table->text('address')->nullable()->after('birth_date');
            $table->string('phone')->nullable()->after('address');
            $table->enum('role', ['admin', 'victim', 'lawyer', 'investor'])->default('victim')->after('phone');
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rut', 'birth_date', 'address', 'phone', 'role', 'avatar', 'is_active', 'last_login_at']);
        });
    }
};
