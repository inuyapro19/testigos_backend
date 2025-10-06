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
        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('investor_type', ['individual', 'institutional', 'accredited'])->default('individual');
            $table->decimal('total_invested', 15, 2)->default(0);
            $table->decimal('total_returns', 15, 2)->default(0);
            $table->integer('active_investments')->default(0);
            $table->integer('completed_investments')->default(0);
            $table->decimal('average_return_rate', 5, 2)->default(0);
            $table->json('investment_preferences')->nullable();
            $table->decimal('minimum_investment', 15, 2)->nullable();
            $table->decimal('maximum_investment', 15, 2)->nullable();
            $table->boolean('is_accredited')->default(false);
            $table->timestamp('accredited_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_profiles');
    }
};
