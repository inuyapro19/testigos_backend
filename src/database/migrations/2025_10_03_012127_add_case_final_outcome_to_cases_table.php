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
        Schema::table('cases', function (Blueprint $table) {
            // Case final outcome
            $table->enum('outcome', ['won', 'lost', 'settled', 'dismissed'])->nullable()->after('lawyer_paid_at');
            $table->decimal('amount_recovered', 15, 2)->nullable()->after('outcome'); // Actual amount recovered from lawsuit
            $table->decimal('legal_costs', 15, 2)->default(0)->after('amount_recovered'); // Total legal costs incurred
            $table->text('outcome_description')->nullable()->after('legal_costs'); // Detailed outcome description
            $table->date('resolution_date')->nullable()->after('outcome_description'); // Date case was resolved
            $table->timestamp('closed_at')->nullable()->after('resolution_date'); // When case was officially closed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'outcome',
                'amount_recovered',
                'legal_costs',
                'outcome_description',
                'resolution_date',
                'closed_at'
            ]);
        });
    }
};
