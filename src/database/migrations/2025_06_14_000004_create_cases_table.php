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
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('victim_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lawyer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['submitted', 'under_review', 'approved', 'published', 'funded', 'in_progress', 'completed', 'rejected'])->default('submitted');
            $table->string('category');
            $table->string('company')->nullable();
            $table->decimal('funding_goal', 15, 2)->default(0);
            $table->decimal('current_funding', 15, 2)->default(0);
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->decimal('expected_return', 5, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->text('legal_analysis')->nullable();
            $table->json('evaluation_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
