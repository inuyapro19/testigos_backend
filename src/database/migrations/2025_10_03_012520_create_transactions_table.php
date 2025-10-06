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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // Unique transaction identifier

            // Transaction type: investment, commission, lawyer_payment, investor_return, withdrawal, gateway_fee
            $table->enum('type', ['investment', 'platform_commission', 'success_commission', 'lawyer_payment', 'investor_return', 'withdrawal', 'gateway_fee', 'refund']);

            // Related entities
            $table->foreignId('case_id')->nullable()->constrained('cases')->onDelete('cascade');
            $table->foreignId('investment_id')->nullable()->constrained('investments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User involved in transaction

            // Financial details
            $table->decimal('amount', 15, 2); // Transaction amount
            $table->string('currency', 3)->default('CLP'); // Currency code
            $table->enum('direction', ['in', 'out']); // Money in (platform receives) or out (platform pays)

            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');

            // Payment gateway details
            $table->string('payment_gateway')->nullable(); // e.g., 'transbank', 'mercadopago', 'stripe'
            $table->string('gateway_transaction_id')->nullable(); // External transaction ID
            $table->decimal('gateway_fee', 15, 2)->default(0); // Fee charged by payment gateway (2-4%)
            $table->json('gateway_response')->nullable(); // Raw gateway response

            // Metadata
            $table->text('description')->nullable(); // Human-readable description
            $table->json('metadata')->nullable(); // Additional data

            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index('transaction_id');
            $table->index(['case_id', 'type']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
