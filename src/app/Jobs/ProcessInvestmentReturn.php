<?php

namespace App\Jobs;

use App\Models\Investment;
use App\Services\InvestmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInvestmentReturn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Investment $investment,
        private float $returnAmount
    ) {}

    public function handle(InvestmentService $investmentService): void
    {
        $investmentService->processInvestmentReturn($this->investment, $this->returnAmount);
    }
}