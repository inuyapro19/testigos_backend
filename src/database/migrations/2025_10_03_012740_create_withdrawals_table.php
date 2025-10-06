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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_id')->unique(); // Unique identifier

            // User requesting withdrawal (typically investor or lawyer)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Optional: Related investment if withdrawing returns from specific investment
            $table->foreignId('investment_id')->nullable()->constrained('investments')->onDelete('set null');

            // Amount details
            $table->decimal('amount', 15, 2); // Amount to withdraw
            $table->decimal('fee', 15, 2)->default(0); // Withdrawal processing fee
            $table->decimal('net_amount', 15, 2); // Amount after fees (amount - fee)
            $table->string('currency', 3)->default('CLP');

            // Status flow: pending → approved → processing → completed (or rejected/cancelled)
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');

            // Payment method details
            $table->enum('payment_method', ['bank_transfer', 'check', 'paypal', 'other'])->default('bank_transfer');
            $table->json('payment_details')->nullable(); // Bank account info, etc.

            // Approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who approved
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Processing tracking
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null'); // Link to transaction record
            $table->string('transfer_reference')->nullable(); // Bank transfer reference number
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Notes
            $table->text('user_notes')->nullable(); // Notes from user
            $table->text('admin_notes')->nullable(); // Internal admin notes

            $table->timestamps();

            // Indexes
            $table->index('withdrawal_id');
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
