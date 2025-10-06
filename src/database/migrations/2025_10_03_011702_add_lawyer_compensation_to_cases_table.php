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
            // Lawyer compensation structure
            $table->decimal('lawyer_evaluation_fee', 15, 2)->default(0)->after('evaluation_data'); // Fixed fee for case evaluation
            $table->decimal('lawyer_success_fee_percentage', 5, 2)->nullable()->after('lawyer_evaluation_fee'); // % of recovered amount
            $table->decimal('lawyer_fixed_fee', 15, 2)->nullable()->after('lawyer_success_fee_percentage'); // Fixed fee if case won
            $table->decimal('lawyer_total_compensation', 15, 2)->default(0)->after('lawyer_fixed_fee'); // Total paid to lawyer
            $table->timestamp('lawyer_paid_at')->nullable()->after('lawyer_total_compensation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'lawyer_evaluation_fee',
                'lawyer_success_fee_percentage',
                'lawyer_fixed_fee',
                'lawyer_total_compensation',
                'lawyer_paid_at'
            ]);
        });
    }
};
