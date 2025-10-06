<?php

namespace App\Console\Commands;

use App\Models\LawyerProfile;
use App\Models\InvestorProfile;
use Illuminate\Console\Command;

class UpdateCaseStatistics extends Command
{
    protected $signature = 'testigo:update-statistics';
    protected $description = 'Update user statistics and profiles';

    public function handle(): int
    {
        $this->info('Updating lawyer statistics...');
        $this->updateLawyerStatistics();

        $this->info('Updating investor statistics...');
        $this->updateInvestorStatistics();

        $this->info('Statistics updated successfully!');
        return 0;
    }

    private function updateLawyerStatistics(): void
    {
        LawyerProfile::chunk(100, function($lawyers) {
            foreach ($lawyers as $lawyer) {
                $lawyer->updateStatistics();
            }
        });
    }

    private function updateInvestorStatistics(): void
    {
        InvestorProfile::chunk(100, function($investors) {
            foreach ($investors as $investor) {
                $investor->updateStatistics();
            }
        });
    }
}