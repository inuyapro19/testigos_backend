<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\CaseModel;
use App\Jobs\ProcessInvestmentReturn;
use Illuminate\Console\Command;

class ProcessInvestmentReturns extends Command
{
    protected $signature = 'testigo:process-returns';
    protected $description = 'Process investment returns for completed cases';

    public function handle(): int
    {
        $completedCases = CaseModel::where('status', 'completed')
            ->whereHas('investments', function($query) {
                $query->where('status', 'active');
            })
            ->get();

        $this->info("Processing returns for {$completedCases->count()} completed cases...");

        foreach ($completedCases as $case) {
            $activeInvestments = $case->investments()->where('status', 'active')->get();
            
            foreach ($activeInvestments as $investment) {
                // Calcular retorno real basado en el resultado del caso
                $actualReturn = $this->calculateActualReturn($investment, $case);
                
                ProcessInvestmentReturn::dispatch($investment, $actualReturn);
                
                $this->info("Queued return processing for investment {$investment->id}");
            }
        }

        $this->info('Investment returns processing completed!');
        return 0;
    }

    private function calculateActualReturn(Investment $investment, CaseModel $case): float
    {
        // Lógica para calcular el retorno real
        // Por ahora, usamos el retorno esperado como base
        $baseReturn = $investment->expected_return_amount;
        
        // Aplicar factores de éxito del caso
        $successFactor = $case->success_rate / 100;
        
        return $investment->amount + ($baseReturn * $successFactor);
    }
}