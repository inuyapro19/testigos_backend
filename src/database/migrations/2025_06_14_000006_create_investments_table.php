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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('investor_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('expected_return_percentage', 5, 2)->nullable();
            $table->decimal('expected_return_amount', 15, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->json('payment_data')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('actual_return', 15, 2)->nullable();
            $table->text('notes')->nullable();

            // Platform commissions (5-10% on investment + success fee on returns)
            $table->decimal('platform_commission_percentage', 5, 2)->default(7.5); // Default 7.5%
            $table->decimal('platform_commission_amount', 15, 2)->default(0);
            $table->decimal('success_commission_percentage', 5, 2)->nullable(); // Applied on actual returns
            $table->decimal('success_commission_amount', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
