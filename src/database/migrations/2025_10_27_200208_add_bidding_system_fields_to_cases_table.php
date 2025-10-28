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
            // Control de visibilidad en marketplace público
            $table->boolean('is_public_marketplace')->default(false)
                ->after('status')
                ->comment('Visible en marketplace público (sin autenticación)');

            // Fecha límite para recibir licitaciones
            $table->timestamp('bid_deadline')->nullable()->after('deadline');

            // Notas del admin sobre el caso
            $table->text('admin_review_notes')->nullable()->after('evaluation_data');

            // Admin que revisó/aprobó el caso
            $table->foreignId('reviewed_by')->nullable()
                ->after('admin_review_notes')
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            // Admin que publicó para inversores
            $table->foreignId('published_by')->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('published_at')->nullable()->after('published_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['published_by']);

            $table->dropColumn([
                'is_public_marketplace',
                'bid_deadline',
                'admin_review_notes',
                'reviewed_by',
                'reviewed_at',
                'published_by',
                'published_at'
            ]);
        });
    }
};
