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
        Schema::create('lawyer_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('lawyer_id')->constrained('users')->onDelete('cascade');

            // === PROPUESTA ECONÓMICA ===
            $table->decimal('funding_goal_proposed', 15, 2)
                ->comment('Monto total que necesita para el caso');
            $table->decimal('expected_return_percentage', 5, 2)
                ->comment('% de retorno esperado para inversores');

            // Honorarios del abogado
            $table->decimal('lawyer_evaluation_fee', 10, 2)->nullable()
                ->comment('Fee inicial por evaluación del caso');
            $table->decimal('lawyer_success_fee_percentage', 5, 2)->nullable()
                ->comment('% sobre monto recuperado si gana');
            $table->decimal('lawyer_fixed_fee', 10, 2)->nullable()
                ->comment('Fee fijo si gana el caso');

            // === PROPUESTA TÉCNICA ===
            $table->decimal('success_probability', 5, 2)
                ->comment('% probabilidad de ganar el caso (0-100)');
            $table->integer('estimated_duration_months')
                ->comment('Duración estimada en meses');
            $table->text('legal_strategy')
                ->comment('Estrategia legal propuesta (mínimo 200 caracteres)');
            $table->text('experience_summary')
                ->comment('Resumen de experiencia relevante');
            $table->text('why_best_candidate')
                ->comment('Por qué es el mejor candidato para este caso');

            // Casos similares ganados
            $table->integer('similar_cases_won')->default(0);
            $table->text('similar_cases_description')->nullable();

            // === DOCUMENTOS ADJUNTOS ===
            $table->json('attachments')->nullable()
                ->comment('URLs de documentos de soporte (CV, certificados, etc)');

            // === ESTADO DE LA LICITACIÓN ===
            $table->enum('status', [
                'draft',        // Borrador (aún no enviado)
                'submitted',    // Enviada, esperando revisión
                'under_review', // Admin revisando
                'accepted',     // Ganadora - abogado asignado al caso
                'rejected',     // Rechazada por admin
                'withdrawn'     // Retirada por el abogado
            ])->default('submitted');

            // === EVALUACIÓN DEL ADMIN ===
            $table->integer('admin_score')->nullable()
                ->comment('Puntaje del admin (1-10)');
            $table->text('admin_feedback')->nullable()
                ->comment('Comentarios del admin sobre la propuesta');
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            // === TIMESTAMPS ===
            $table->timestamps();
            $table->softDeletes(); // Por si el abogado elimina su licitación

            // === CONSTRAINTS ===
            // Un abogado solo puede licitar UNA VEZ por caso
            $table->unique(['case_id', 'lawyer_id'], 'unique_lawyer_per_case');

            // Indexes para queries frecuentes
            $table->index(['case_id', 'status']);
            $table->index(['lawyer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lawyer_bids');
    }
};
